/**
 * DTPS (Duckietown Postal Service) client for the dashboard.
 *
 * Talks to the robot's DTPS switchboard through the device proxy (`/dtps/` on the robot's
 * port 80, the same origin as the dashboard), so the dashboard works regardless of which ROS
 * stack (if any) is running on the robot.
 *
 *   let dtps = DTPS.client("<robot_name>");
 *   let sub = dtps.subscribe("sensor/imu/base/all", (msg) => {...}, {maxHz: 10});
 *   await dtps.publish("actuator/wheels/base/pwm", {header: DTPS.header(), left: 0.2, right: 0.2});
 *   let value = await dtps.get("sensor/wheel_encoder/left/ticks");
 *   sub.close();
 *
 * Messages are `duckietown_messages` objects encoded as CBOR (requires `cbor.min.js`).
 * Streams are read from the topic's `:events/?send_data=1` websocket: a `ChannelInfo` frame,
 * then for each message a `DataReady` frame followed by one or more `Chunk` frames.
 */
(function () {
    "use strict";
    if (window.DTPS !== undefined) return;  // already loaded

    const RECONNECT_MIN_MS = 500;
    const RECONNECT_MAX_MS = 5000;

    function _strip(path) {
        return String(path).replace(/^\/+|\/+$/g, "");
    }

    function _decode(contentType, bytes) {
        const type = (contentType || "").split(";")[0].trim();
        if (type === "application/cbor") {
            return CBOR.decode(bytes.buffer.slice(bytes.byteOffset, bytes.byteOffset + bytes.byteLength));
        }
        if (type === "application/json") {
            return JSON.parse(new TextDecoder("utf-8").decode(bytes));
        }
        if (type.startsWith("text/")) {
            return new TextDecoder("utf-8").decode(bytes);
        }
        return bytes;
    }

    function _toBytes(value) {
        if (value instanceof Uint8Array) return value;
        if (value instanceof ArrayBuffer) return new Uint8Array(value);
        if (Array.isArray(value)) return new Uint8Array(value);
        return new Uint8Array(0);
    }

    /** A `duckietown_messages` Header with the current time. */
    function header(frame = null) {
        return {version: "1.0", frame: frame, txt: null, timestamp: Date.now() / 1000.0};
    }

    /**
     * Shows a stream of CompressedImage messages without flickering.
     *
     *   const view = DTPS.imageView((url) => element.style.backgroundImage = `url(${url})`);
     *   const sub = dtps.subscribe("sensor/camera/front_center/jpeg", (msg) => view.show(msg));
     *   ...
     *   sub.close(); view.close();
     *
     * Swapping an <img> src or a CSS background to an image that is not decoded yet makes the
     * browser paint an empty frame in between. Here each frame is decoded off-screen first and
     * handed to `apply(url)` only once it is ready; the previous frame is released afterwards.
     * Frames that arrive while one is decoding replace each other (the newest wins), so a slow
     * browser drops frames instead of falling behind.
     */
    function imageView(apply) {
        let shown = null;       // {url, image} currently on screen
        let waiting = null;     // newest message, not decoded yet
        let decoding = false;
        let closed = false;

        async function drain() {
            decoding = true;
            while (waiting !== null && !closed) {
                const message = waiting;
                waiting = null;
                const format = message.format === "png" ? "png" : "jpeg";
                const url = URL.createObjectURL(new Blob([_toBytes(message.data)], {type: "image/" + format}));
                const image = new Image();
                image.src = url;
                try {
                    await image.decode();
                } catch (error) {
                    URL.revokeObjectURL(url);  // corrupted or truncated frame: skip it
                    continue;
                }
                if (closed) {
                    URL.revokeObjectURL(url);
                    break;
                }
                apply(url);
                const previous = shown;
                // keep a reference to the decoded image while it is on screen
                shown = {url: url, image: image};
                if (previous) setTimeout(() => URL.revokeObjectURL(previous.url), 1000);
            }
            decoding = false;
        }

        return {
            show: function (message) {
                if (closed || !message) return;
                waiting = message;
                if (!decoding) drain();
            },
            close: function () {
                closed = true;
                waiting = null;
                if (shown) URL.revokeObjectURL(shown.url);
                shown = null;
            },
        };
    }

    class Subscription {
        /**
         * @param client     the DTPSClient
         * @param path       topic path relative to the robot, e.g. "sensor/imu/base/all"
         * @param callback   called with (message, meta) for every message
         * @param options    {maxHz: drop messages arriving faster than this rate,
         *                    onStatus: called with "connected" / "disconnected"}
         *
         * NOTE: on connection the switchboard first sends the topic's current value (if any);
         *       `meta.index` (increasing per topic) tells messages apart.
         */
        constructor(client, path, callback, options = {}) {
            this._client = client;
            this._path = _strip(path);
            this._callback = callback;
            this._maxHz = options.maxHz || 0;
            this._onStatus = options.onStatus || null;
            this._closed = false;
            this._ws = null;
            this._delay = RECONNECT_MIN_MS;
            this._timer = null;
            this._lastDelivery = 0;
            this._pending = null;   // message being assembled from chunks
            this._connect();
        }

        get path() {
            return this._path;
        }

        close() {
            this._closed = true;
            clearTimeout(this._timer);
            if (this._ws !== null) {
                this._ws.onclose = null;
                this._ws.close();
                this._ws = null;
            }
        }

        _status(status) {
            if (this._onStatus) {
                try { this._onStatus(status); } catch (e) { console.error("dtps: onStatus failed", e); }
            }
        }

        _connect() {
            if (this._closed) return;
            const ws = new WebSocket(this._client.wsURL(this._path));
            ws.binaryType = "arraybuffer";
            this._ws = ws;
            this._pending = null;
            ws.onopen = () => {
                this._delay = RECONNECT_MIN_MS;
                this._status("connected");
            };
            ws.onmessage = (event) => this._onFrame(event.data);
            ws.onerror = () => {};
            ws.onclose = () => {
                this._status("disconnected");
                if (this._closed || this._ws !== ws) return;
                this._timer = setTimeout(() => this._connect(), this._delay);
                this._delay = Math.min(this._delay * 2, RECONNECT_MAX_MS);
            };
        }

        _onFrame(data) {
            let frame;
            try {
                frame = CBOR.decode(data instanceof ArrayBuffer ? data : new TextEncoder().encode(data).buffer);
            } catch (e) {
                console.warn("dtps: cannot decode frame on " + this._path, e);
                return;
            }
            if (frame.ChannelInfo !== undefined) {
                return;
            }
            if (frame.DataReady !== undefined) {
                const ready = frame.DataReady;
                this._pending = {
                    index: ready.index,
                    digest: ready.digest,
                    contentType: ready.content_type,
                    length: ready.content_length,
                    expected: ready.chunks_arriving,
                    chunks: [],
                    received: 0,
                };
                if (ready.chunks_arriving === 0) {
                    // the data was not sent inline: fetch it
                    this._client.get(this._path).then((message) => {
                        if (message !== null) this._deliver(message, {index: ready.index});
                    });
                    this._pending = null;
                }
                return;
            }
            if (frame.Chunk !== undefined) {
                const chunk = frame.Chunk;
                const pending = this._pending;
                if (pending === null || chunk.digest !== pending.digest) return;
                pending.chunks[chunk.i] = _toBytes(chunk.data);
                pending.received += 1;
                if (pending.received < chunk.n) return;
                // all chunks received: concatenate and decode
                const total = pending.chunks.reduce((sum, c) => sum + c.length, 0);
                const bytes = new Uint8Array(total);
                let offset = 0;
                for (const c of pending.chunks) {
                    bytes.set(c, offset);
                    offset += c.length;
                }
                this._pending = null;
                let message;
                try {
                    message = _decode(pending.contentType, bytes);
                } catch (e) {
                    console.warn("dtps: cannot decode message on " + this._path, e);
                    return;
                }
                this._deliver(message, {index: pending.index});
            }
        }

        _deliver(message, meta) {
            if (this._maxHz > 0) {
                const now = performance.now();
                if (now - this._lastDelivery < 1000.0 / this._maxHz) return;
                this._lastDelivery = now;
            }
            try {
                this._callback(message, meta);
            } catch (e) {
                console.error("dtps: subscriber callback failed on " + this._path, e);
            }
        }
    }

    class DTPSClient {
        /**
         * @param robotName  the robot's name (first segment of every switchboard topic)
         * @param baseURL    the switchboard's URL, by default `/dtps` on the dashboard's origin
         */
        constructor(robotName, baseURL = window.location.origin + "/dtps") {
            this.robotName = robotName;
            this.baseURL = String(baseURL).replace(/\/+$/, "");
        }

        url(path) {
            const p = _strip(path);
            return this.baseURL + "/" + this.robotName + (p.length ? "/" + p : "") + "/";
        }

        wsURL(path) {
            return this.url(path).replace(/^http/, "ws") + ":events/?send_data=1";
        }

        /** Latest value of a topic, or null if it has none. */
        async get(path) {
            const response = await fetch(this.url(path), {cache: "no-store"});
            if (response.status === 204) return null;
            if (!response.ok) throw new Error("GET " + path + " failed: HTTP " + response.status);
            const bytes = new Uint8Array(await response.arrayBuffer());
            return _decode(response.headers.get("content-type"), bytes);
        }

        /** Publish a message (a plain object, encoded as CBOR) to a topic. */
        async publish(path, message) {
            const response = await fetch(this.url(path), {
                method: "POST",
                headers: {"content-type": "application/cbor"},
                body: new Uint8Array(CBOR.encode(message)),
            });
            if (!response.ok) throw new Error("POST " + path + " failed: HTTP " + response.status);
        }

        subscribe(path, callback, options = {}) {
            return new Subscription(this, path, callback, options);
        }

        /** The switchboard index: {topics: {"<robot>/<path>": {...}}}. */
        async index() {
            const response = await fetch(this.baseURL + "/", {
                cache: "no-store",
                headers: {"accept": "application/json"},
            });
            if (!response.ok) throw new Error("index failed: HTTP " + response.status);
            return await response.json();
        }
    }

    const clients = {};

    window.DTPS = {
        Client: DTPSClient,
        /** A shared client for a robot. */
        client: function (robotName, baseURL) {
            const key = robotName + "|" + (baseURL || "");
            if (!clients.hasOwnProperty(key)) clients[key] = new DTPSClient(robotName, baseURL);
            return clients[key];
        },
        header: header,
        imageView: imageView,
    };
})();
