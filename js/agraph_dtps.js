/**
 * Architecture graph built from the robot's DTPS switchboard (no ROS needed).
 *
 * Produces the same structure the ROS HTTP API (`/ros/graph`) used to return, so the rest of the
 * Architecture tab (filters, layout, inspector) is unchanged:
 *   - nodes:  the DTPS nodes (`<robot>/node/<name>/...`), e.g. the drivers;
 *   - topics: the queues they expose, under their public name when they have one
 *             (`sensor/...`, `actuator/...`, matched through the queue's `unique_id`);
 *   - edges:  node -> topic for outputs (`out/...`, `.../out`), topic -> node for inputs.
 *
 * Requires: dtps_client.js
 */
(function () {
    "use strict";
    if (window.agraph_dtps_graph !== undefined) return;  // already loaded

    // queues that are part of every node's plumbing, not of the robot's architecture
    const SKIP = /^(dtps\/|switch$|config$|passthrough-\d+\/)/;

    function _isOutput(path) {
        return path.startsWith("out/") || /(^|\/)out$/.test(path);
    }

    function _nodeType(name) {
        return /driver/.test(name) ? "DRIVER" : "GENERIC";
    }

    async function agraph_dtps_graph(robotName) {
        const index = await DTPS.client(robotName).index();
        const prefix = robotName + "/";
        const names = Object.keys(index.topics)
            .filter((name) => name.startsWith(prefix))
            .map((name) => name.slice(prefix.length));
        const isLeaf = (name) => !names.some((other) => other.startsWith(name + "/"));
        const leaves = names.filter(isLeaf);
        const entry = (name) => index.topics[prefix + name] || {};

        // public names (sensor/..., actuator/...) of the queues, by unique id
        const publicName = {};
        for (const name of leaves) {
            if (/^(sensor|actuator)\//.test(name) && entry(name).unique_id) {
                publicName[entry(name).unique_id] = name;
            }
        }

        const api = {
            graph: {nodes: [], edges: {node_to_node: [], node_to_topic: [], topic_to_topic: [], topic_to_node: []}},
            nodes: {},
            topics: {},
        };
        const topicsIn = {}, topicsOut = {};

        for (const name of leaves) {
            const match = name.match(/^node\/([^/]+)\/(.+)$/);
            if (match === null) continue;
            const node = match[1], path = match[2];
            if (SKIP.test(path)) continue;
            if (!api.nodes.hasOwnProperty(node)) {
                api.nodes[node] = {
                    type: _nodeType(node),
                    module_type: "DTPS",
                    module_instance: null,
                    machine: robotName,
                    enabled: null,
                    health: "UNKNOWN",
                    health_value: 0,
                    health_reason: "",
                    services: "-",
                    parameters: "-",
                };
                topicsIn[node] = [];
                topicsOut[node] = [];
            }
            const topic = publicName[entry(name).unique_id] || name;
            if (!api.topics.hasOwnProperty(topic)) {
                api.topics[topic] = {
                    type: "DTPS queue",
                    frequency: null,
                    effective_frequency: null,
                    bandwidth: null,
                    health: "UNKNOWN",
                    health_value: 0,
                };
            }
            if (_isOutput(path)) {
                api.graph.edges.node_to_topic.push({from: node, to: topic});
                topicsOut[node].push(topic);
            } else {
                api.graph.edges.topic_to_node.push({from: topic, to: node});
                topicsIn[node].push(topic);
            }
        }
        for (const node of Object.keys(api.nodes)) {
            api.nodes[node].topics_in = topicsIn[node].sort().join("\n") || "-";
            api.nodes[node].topics_out = topicsOut[node].sort().join("\n") || "-";
        }
        api.graph.nodes = Object.keys(api.nodes).sort();
        return {data: api};
    }

    window.agraph_dtps_graph = agraph_dtps_graph;
})();
