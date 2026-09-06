<?php
/**
 * Modern Architecture tab (default).
 *
 * Compact 1040px layout (consistent with other robot tabs) and rosbridge
 * fallback when the ROS HTTP API (/ros → :8084) is unavailable.
 *
 * Legacy full-bleed implementation preserved at:
 *   pages/robot/legacy/architecture_fullbleed.php
 * Toggle via RobotUIFeatures::modern_architecture() / robot_ui/modern_architecture.
 * Rosbridge fallback: RobotUIFeatures::architecture_rosbridge_fallback().
 *
 * @see ../../ui_features.php
 */
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele

use \system\classes\Core;
use \system\packages\ros\ROS;
use \system\packages\duckietown_duckiebot\Duckiebot;

require_once dirname(__DIR__, 2) . '/ui_features.php';

$dbot_hostname = Duckiebot::getDuckiebotHostname();
$ros_hostname = ROS::sanitize_hostname($dbot_hostname);
$connected_evt = ROS::get_event(ROS::$ROSBRIDGE_CONNECTED, $ros_hostname);

// Rosbridge is required when HTTP /ros API is down (common when nothing
// listens on device-proxy upstream :8084). Gated by feature flag.
$arch_rosbridge_fallback = RobotUIFeatures::architecture_rosbridge_fallback();
if ($arch_rosbridge_fallback) {
    ROS::connect($ros_hostname);
}

$height_px = 560;
?>

<script
        src="<?php echo Core::getJSscriptURL('vis-network.min.js', 'duckietown') ?>"
        type="text/javascript">
</script>

<style type="text/css">
    /* Match Overview / Components content width - do NOT expand #page_container */
    .robot-architecture {
        max-width: var(--r-max, 1040px);
        width: 100%;
        margin: 0 auto;
    }

    .robot-architecture-status {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--r-gap, 10px);
        margin-bottom: var(--r-gap, 10px);
        padding: 8px 12px;
        background: var(--r-surface, #f8f9fb);
        border: 1px solid var(--r-border, #e6e8eb);
        border-radius: var(--r-radius-md, 10px);
        font-size: var(--r-fs-md, 12px);
        color: var(--r-muted, #6b7280);
    }
    .robot-architecture-status .arch-status-msg i {
        margin-right: 6px;
    }
    .robot-architecture-status.is-ok .arch-status-msg { color: var(--r-ok, #047857); }
    .robot-architecture-status.is-bad .arch-status-msg { color: var(--r-bad, #b91c1c); }
    .robot-architecture-status.is-wait .arch-status-msg { color: var(--r-warn, #b45309); }

    .robot-architecture-layout {
        display: grid;
        grid-template-columns: minmax(180px, 220px) minmax(0, 1fr) minmax(160px, 200px);
        gap: var(--r-gap, 10px);
        align-items: stretch;
    }
    @media (max-width: 900px) {
        .robot-architecture-layout {
            grid-template-columns: 1fr;
        }
    }

    .robot-architecture-panel {
        background: #fff;
        border: 1px solid var(--r-border, #e6e8eb);
        border-radius: var(--r-radius-md, 10px);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-height: <?php echo $height_px ?>px;
        max-height: <?php echo $height_px ?>px;
    }
    .robot-architecture-panel > .panel-heading {
        flex: 0 0 auto;
        background: var(--r-surface, #f8f9fb);
        border-bottom: 1px solid var(--r-border, #e6e8eb);
        padding: 8px 12px;
        font-size: var(--r-fs-md, 12px);
        font-weight: var(--r-fw-semibold, 600);
        color: var(--r-text, #111827);
        margin: 0;
        border-radius: 0;
    }
    .robot-architecture-panel > .panel-body {
        flex: 1 1 auto;
        position: relative;
        overflow: hidden;
        padding: 0;
        height: auto;
    }

    #_architecture_toolbox_form,
    #_architecture_inspector_div {
        position: absolute;
        inset: 0;
        overflow: auto;
        padding: 10px 12px;
        font-size: var(--r-fs-md, 12px);
    }
    #_architecture_toolbox_form h4 {
        margin: 0 0 8px;
        font-size: var(--r-fs-md, 12px);
        font-weight: var(--r-fw-semibold, 600);
    }
    #_architecture_toolbox_form h5 {
        margin: 0 0 6px;
        font-size: var(--r-fs-sm, 11px);
        font-weight: var(--r-fw-semibold, 600);
        color: var(--r-muted, #6b7280);
        text-transform: uppercase;
        letter-spacing: var(--r-tracking-label, 0.04em);
    }
    #_architecture_toolbox_form hr {
        margin: 10px 0;
        border-top-color: var(--r-border, #e6e8eb);
    }
    /* Segment controls use shared .robot-seg / Bootstrap btn-group mapping */

    #_architecture_canvas_wrap {
        position: relative;
        background: #fff;
        border: 1px solid var(--r-border, #e6e8eb);
        border-radius: var(--r-radius-md, 10px);
        overflow: hidden;
        min-height: <?php echo $height_px ?>px;
        height: <?php echo $height_px ?>px;
    }
    #_graph_canvas {
        width: 100%;
        height: 100%;
        background: #fff;
    }
    #_architecture_empty {
        display: none;
        position: absolute;
        inset: 0;
        z-index: 5;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 24px;
        background: rgba(255,255,255,0.92);
        color: var(--r-muted, #6b7280);
        font-size: var(--r-fs-lg, 13px);
    }
    #_architecture_empty.is-visible {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    #_architecture_empty .btn {
        align-self: center;
        margin-top: 6px;
    }

    #_architecture_inspector_div .dl-horizontal dt { width: 50px; }
    #_architecture_inspector_div .dl-horizontal dd {
        margin-left: 50px;
        padding-left: 8px;
    }
</style>


<div class="robot-architecture">
    <div class="robot-architecture-status is-wait" id="_architecture_status">
        <span class="arch-status-msg">
            <i class="fa fa-spinner fa-pulse" aria-hidden="true"></i>
            Loading ROS graph…
        </span>
        <button type="button" class="robot-btn robot-btn-ghost robot-btn-xs" id="_architecture_reload_btn">
            <i class="fa fa-refresh" aria-hidden="true"></i> Reload
        </button>
    </div>

    <div class="robot-architecture-layout">
        <div class="robot-architecture-panel panel panel-default" id="_architecture_toolbox" style="margin:0; box-shadow:none;">
            <div class="panel-heading">
                <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
                Toolbox
            </div>
            <div class="panel-body">
                <div id="_architecture_toolbox_form">
                    <h4>Nodes</h4>
                    <div>
                        <h5>Filter</h5>
                        <div class="btn-group robot-seg" data-toggle="buttons">
                            <label class="btn btn-default robot-seg-item active">
                                <input type="radio" name="node-filter" data-query-key="node-filter" data-query-value="all" checked>
                                All
                            </label>
                            <label class="btn btn-default robot-seg-item">
                                <input type="radio" name="node-filter" data-query-key="node-filter" data-query-value="enabled">
                                Enabled
                            </label>
                            <label class="btn btn-default robot-seg-item">
                                <input type="radio" name="node-filter" data-query-key="node-filter" data-query-value="active">
                                Active
                            </label>
                        </div>
                        <hr>
                        <h5>Color</h5>
                        <div class="btn-group robot-seg" data-toggle="buttons">
                            <label class="btn btn-default robot-seg-item active">
                                <input type="radio" name="node-color" data-query-key="node-color" data-query-value="none" checked>
                                No
                            </label>
                            <label class="btn btn-default robot-seg-item">
                                <input type="radio" name="node-color" data-query-key="node-color" data-query-value="health">
                                Health
                            </label>
                            <label class="btn btn-default robot-seg-item">
                                <input type="radio" name="node-color" data-query-key="node-color" data-query-value="status">
                                Status
                            </label>
                            <label class="btn btn-default robot-seg-item">
                                <input type="radio" name="node-color" data-query-key="node-color" data-query-value="type">
                                Type
                            </label>
                        </div>
                        <hr>
                        <h5>Cluster</h5>
                        <div class="btn-group robot-seg" data-toggle="buttons">
                            <label class="btn btn-default robot-seg-item active">
                                <input type="radio" name="node-cluster" data-query-key="node-cluster" data-query-value="none" checked>
                                No
                            </label>
                            <label class="btn btn-default robot-seg-item">
                                <input type="radio" name="node-cluster" data-query-key="node-cluster" data-query-value="module">
                                Module
                            </label>
                            <label class="btn btn-default robot-seg-item">
                                <input type="radio" name="node-cluster" data-query-key="node-cluster" data-query-value="machine">
                                Machine
                            </label>
                        </div>
                    </div>
                    <hr>
                    <h4>Topics</h4>
                    <div>
                        <h5>Filter</h5>
                        <div class="btn-group robot-seg" data-toggle="buttons">
                            <label class="btn btn-default robot-seg-item active">
                                <input type="radio" name="topic-filter" data-query-key="topic-filter" data-query-value="all" checked>
                                All
                            </label>
                            <label class="btn btn-default robot-seg-item">
                                <input type="radio" name="topic-filter" data-query-key="topic-filter" data-query-value="used">
                                Used
                            </label>
                            <label class="btn btn-default robot-seg-item">
                                <input type="radio" name="topic-filter" data-query-key="topic-filter" data-query-value="subscribed">
                                Subscribed
                            </label>
                        </div>
                        <hr>
                        <h5>Render as</h5>
                        <div class="btn-group robot-seg" data-toggle="buttons">
                            <label class="btn btn-default robot-seg-item active">
                                <input type="radio" name="topic-shape" data-query-key="topic-shape" data-query-value="ellipse" checked>
                                Ellipses
                            </label>
                            <label class="btn btn-default robot-seg-item">
                                <input type="radio" name="topic-shape" data-query-key="topic-shape" data-query-value="label">
                                Edge labels
                            </label>
                        </div>
                        <hr>
                        <h5>Width</h5>
                        <div class="btn-group robot-seg" data-toggle="buttons">
                            <label class="btn btn-default robot-seg-item active">
                                <input type="radio" name="edge-width" data-query-key="edge-width" data-query-value="none" checked>
                                No
                            </label>
                            <label class="btn btn-default robot-seg-item">
                                <input type="radio" name="edge-width" data-query-key="edge-width" data-query-value="frequency">
                                Frequency
                            </label>
                            <label class="btn btn-default robot-seg-item">
                                <input type="radio" name="edge-width" data-query-key="edge-width" data-query-value="bandwidth">
                                Bandwidth
                            </label>
                        </div>
                        <hr>
                        <h5>Color</h5>
                        <div class="btn-group robot-seg" data-toggle="buttons">
                            <label class="btn btn-default robot-seg-item active">
                                <input type="radio" name="edge-color" data-query-key="edge-color" data-query-value="none" checked>
                                No
                            </label>
                            <label class="btn btn-default robot-seg-item">
                                <input type="radio" name="edge-color" data-query-key="edge-color" data-query-value="health">
                                Health
                            </label>
                            <label class="btn btn-default robot-seg-item">
                                <input type="radio" name="edge-color" data-query-key="edge-color" data-query-value="type">
                                Type
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="_architecture_canvas_wrap">
            <div id="_graph_canvas"></div>
            <div id="_architecture_empty">
                <div><i class="fa fa-sitemap" style="font-size:var(--r-fs-icon, 24px); opacity:0.45"></i></div>
                <div id="_architecture_empty_msg">Waiting for ROS graph…</div>
                <button type="button" class="robot-btn robot-btn-ghost robot-btn-sm" id="_architecture_empty_reload">
                    Try again
                </button>
            </div>
        </div>

        <div class="robot-architecture-panel panel panel-default" id="_architecture_inspector" style="margin:0; box-shadow:none;">
            <div class="panel-heading">
                <span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>
                Inspector
            </div>
            <div class="panel-body">
                <div id="_architecture_inspector_div"></div>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">
    window._architecture_graph = {};
    window._architecture_graph.api_data = {
        graph: {
            nodes: [],
            edges: {
                node_to_node: [],
                node_to_topic: [],
                topic_to_topic: [],
                topic_to_node: []
            }
        },
        nodes: {},
        topics: {}
    };
    window._architecture_graph.data = {
        nodes: [],
        edges: []
    };
    window._architecture_graph.query = {};
    window._architecture_graph.inspector = {
        current: {
            node: null,
            topic: null,
            module: null,
            legend: null
        },
        data: {
            nodes: {},
            topics: {},
            modules: {}
        }
    };
    window._architecture_graph.source = null;
    window._architecture_graph.ros_hostname = <?php echo json_encode($ros_hostname) ?>;
    window._architecture_graph.connected_evt = <?php echo json_encode($connected_evt) ?>;
    window._architecture_graph.rosbridge_fallback = <?php echo $arch_rosbridge_fallback ? 'true' : 'false' ?>;
    window._architecture_graph._loading = false;
    window._architecture_graph._loaded_ok = false;

    window._architecture_graph.container = document.getElementById('_graph_canvas');
    window._architecture_graph.options = {
        layout: {
            hierarchical: {
                enabled: true,
                levelSeparation: 160,
                nodeSpacing: 18,
                direction: "UD",
                sortMethod: "directed",
                shakeTowards: "leaves"
            }
        },
        physics: {
            hierarchicalRepulsion: {
                centralGravity: 0,
                springLength: 0,
                springConstant: 0.1,
                nodeDistance: 220,
                damping: 1,
                avoidOverlap: 1
            },
            minVelocity: 0.75,
            solver: "hierarchicalRepulsion"
        },
        interaction: {
            dragNodes: true,
            navigationButtons: true
        },
        edges: {
            smooth: {
                type: "cubicBezier",
                forceDirection: "vertical",
                roundness: 0.4
            },
            arrows: "to",
            color: { color: "gray" },
            font: { size: 16 },
            width: 2
        },
        nodes: {
            font: { size: 16 },
            margin: 10
        },
        groups: {
            ros_node: { color: {} },
            ros_topic: {
                color: {
                    border: "gray",
                    background: "#F0F0F0",
                    highlight: {
                        border: "darkgray",
                        background: "#C8C8C8"
                    }
                }
            }
        },
        configure: {
            enabled: false,
            filter: 'physics, layout',
            showButton: true
        }
    };

    window._architecture_graph.network = new vis.Network(
        window._architecture_graph.container,
        window._architecture_graph.data,
        window._architecture_graph.options
    );

    function agraph_set_status(state, message) {
        let el = $('#_architecture_status');
        el.removeClass('is-ok is-bad is-wait').addClass('is-' + state);
        let icon = state === 'ok' ? 'fa-check-circle'
            : (state === 'bad' ? 'fa-exclamation-circle' : 'fa-spinner fa-pulse');
        el.find('.arch-status-msg').html(
            '<i class="fa ' + icon + '" aria-hidden="true"></i>' + message
        );
    }

    function agraph_set_empty(visible, message) {
        let el = $('#_architecture_empty');
        if (message) $('#_architecture_empty_msg').text(message);
        el.toggleClass('is-visible', !!visible);
    }

    function agraph_ros_api(callback, on_error) {
        let hostname = "<?php echo Core::getSetting(
            'ros_api/hostname', 'duckietown_duckiebot', Duckiebot::getDuckiebotHostname()
        ) ?>";
        let url = 'http://{0}/ros/graph'.format(hostname);
        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            timeout: 8000,
            success: function(res) {
                if (res && res.status === 'ok' && res.data) {
                    callback(res);
                } else if (typeof on_error === 'function') {
                    on_error(res && res.message ? res.message : 'ROS API returned an error');
                }
            },
            error: function(xhr) {
                if (typeof on_error === 'function') {
                    on_error('ROS API unavailable (HTTP ' + (xhr.status || '?') + ')');
                }
            }
        });
    }

    function agraph_call_rosapi(ros, name, serviceType, args) {
        return new Promise(function(resolve, reject) {
            let client = new ROSLIB.Service({
                ros: ros,
                name: name,
                serviceType: serviceType
            });
            let settled = false;
            let timer = setTimeout(function() {
                if (settled) return;
                settled = true;
                reject('Timeout calling ' + name);
            }, 10000);
            client.callService(new ROSLIB.ServiceRequest(args || {}), function(result) {
                if (settled) return;
                settled = true;
                clearTimeout(timer);
                resolve(result);
            }, function(err) {
                if (settled) return;
                settled = true;
                clearTimeout(timer);
                reject(err || ('Service failed: ' + name));
            });
        });
    }

    function agraph_map_pool(items, limit, worker) {
        return new Promise(function(resolve, reject) {
            if (!items.length) {
                resolve([]);
                return;
            }
            let index = 0;
            let active = 0;
            let done = 0;
            let results = new Array(items.length);
            let failed = false;

            function pump() {
                if (failed) return;
                while (active < limit && index < items.length) {
                    (function(i) {
                        active++;
                        Promise.resolve(worker(items[i], i)).then(function(result) {
                            results[i] = result;
                            active--;
                            done++;
                            if (done === items.length) resolve(results);
                            else pump();
                        }).catch(function(err) {
                            failed = true;
                            reject(err);
                        });
                    })(index++);
                }
            }
            pump();
        });
    }

    function agraph_wait_for_rosbridge(timeout_ms) {
        return new Promise(function(resolve, reject) {
            let hostname = window._architecture_graph.ros_hostname;
            let ros = (window.ros && window.ros[hostname]) ? window.ros[hostname] : null;
            if (ros && ros.isConnected) {
                resolve(ros);
                return;
            }
            let finished = false;
            let timer = setTimeout(function() {
                if (finished) return;
                finished = true;
                reject('Rosbridge connect timeout');
            }, timeout_ms || 12000);
            $(document).one(window._architecture_graph.connected_evt, function() {
                if (finished) return;
                finished = true;
                clearTimeout(timer);
                let connected = (window.ros && window.ros[hostname]) ? window.ros[hostname] : null;
                if (connected) resolve(connected);
                else reject('Rosbridge connected but handle missing');
            });
        });
    }

    function agraph_build_from_rosapi(nodes, topic_types, publishers_by_topic, subscribers_by_topic) {
        let data = {
            graph: {
                nodes: nodes.slice(),
                edges: {
                    node_to_node: [],
                    node_to_topic: [],
                    topic_to_topic: [],
                    topic_to_node: []
                }
            },
            nodes: {},
            topics: {}
        };

        nodes.forEach(function(node) {
            let parts = node.split('/').filter(Boolean);
            data.nodes[node] = {
                enabled: true,
                health_value: 1.0,
                health_reason: 'From rosbridge',
                module_type: parts.length > 1 ? parts[parts.length - 1] : node,
                module_instance: parts.length > 1 ? parts[0] : 'local',
                machine: 'local',
                type: 'node',
                status: 'running'
            };
        });

        Object.keys(topic_types).forEach(function(topic) {
            data.topics[topic] = {
                type: topic_types[topic] || 'unknown',
                frequency: 0,
                bandwidth: 0,
                health_value: 1.0
            };
            let pubs = publishers_by_topic[topic] || [];
            let subs = subscribers_by_topic[topic] || [];
            pubs.forEach(function(node) {
                if (data.nodes[node]) {
                    data.graph.edges.node_to_topic.push({ from: node, to: topic });
                }
            });
            subs.forEach(function(node) {
                if (data.nodes[node]) {
                    data.graph.edges.topic_to_node.push({ from: topic, to: node });
                }
            });
            pubs.forEach(function(from_node) {
                subs.forEach(function(to_node) {
                    if (data.nodes[from_node] && data.nodes[to_node]) {
                        data.graph.edges.node_to_node.push({
                            from: from_node,
                            to: to_node,
                            middle: topic
                        });
                    }
                });
            });
        });

        return { status: 'ok', data: data };
    }

    function agraph_load_via_rosbridge() {
        agraph_set_status('wait', 'Waiting for rosbridge…');
        return agraph_wait_for_rosbridge(12000).then(function(ros) {
            agraph_set_status('wait', 'Loading graph via rosbridge…');

            // Prefer global /rosapi/*; fall back to vehicle-namespaced services.
            let hostname = window._architecture_graph.ros_hostname;
            let prefixes = ['/rosapi', '/' + String(hostname).split('.')[0] + '/rosapi'];

            function try_prefix(prefix) {
                return agraph_call_rosapi(ros, prefix + '/nodes', 'rosapi/Nodes')
                    .then(function(nodes_res) {
                        return agraph_call_rosapi(ros, prefix + '/topics', 'rosapi/Topics')
                            .then(function(topics_res) {
                                return { prefix: prefix, nodes_res: nodes_res, topics_res: topics_res };
                            });
                    });
            }

            return try_prefix(prefixes[0]).catch(function() {
                return try_prefix(prefixes[1]);
            }).then(function(bundle) {
                let nodes = bundle.nodes_res.nodes || [];
                let topics = bundle.topics_res.topics || [];
                let types = bundle.topics_res.types || [];
                let topic_types = {};
                topics.forEach(function(t, i) {
                    topic_types[t] = types[i] || 'unknown';
                });

                let pub_map = {};
                let sub_map = {};
                agraph_set_status(
                    'wait',
                    'Resolving publishers/subscribers for {0} topics…'.format(topics.length)
                );

                return agraph_map_pool(topics, 8, function(topic) {
                    return Promise.all([
                        agraph_call_rosapi(ros, bundle.prefix + '/publishers', 'rosapi/Publishers', { topic: topic })
                            .catch(function() { return { publishers: [] }; }),
                        agraph_call_rosapi(ros, bundle.prefix + '/subscribers', 'rosapi/Subscribers', { topic: topic })
                            .catch(function() { return { subscribers: [] }; })
                    ]).then(function(pair) {
                        pub_map[topic] = pair[0].publishers || [];
                        sub_map[topic] = pair[1].subscribers || [];
                    });
                }).then(function() {
                    return agraph_build_from_rosapi(nodes, topic_types, pub_map, sub_map);
                });
            });
        });
    }

    function _agraph_on_node_selection_change(params) {
        window._architecture_graph.inspector.current.node = null;
        window._architecture_graph.inspector.current.topic = null;
        window._architecture_graph.inspector.current.module = null;
        if (params.nodes.length === 1) {
            let node_id = params.nodes[0];
            if (window._architecture_graph.network.isCluster(node_id) === true) {
                window._architecture_graph.network.openCluster(node_id);
            } else {
                let selected = agraph_get_node(node_id);
                if (selected) {
                    if (node_id.startsWith('node:')) {
                        window._architecture_graph.inspector.current.node = selected._node;
                    } else if (selected._topic) {
                        window._architecture_graph.inspector.current.topic = selected._topic;
                    }
                }
            }
        }
        agraph_refresh_inspector();
    }
    window._architecture_graph.network.on("selectNode", _agraph_on_node_selection_change);
    window._architecture_graph.network.on("deselectNode", _agraph_on_node_selection_change);

    function _agraph_on_edge_selection_change(params) {
        if ([undefined, 'ellipse'].includes(window._architecture_graph.query['topic-shape']))
            return;
        window._architecture_graph.inspector.current.topic = null;
        if (params.edges.length === 1) {
            let edge = agraph_get_edge(params.edges[0]);
            if (edge && edge._topic) {
                window._architecture_graph.inspector.current.topic = edge._topic;
            }
        }
        agraph_refresh_inspector();
    }
    window._architecture_graph.network.on("selectEdge", _agraph_on_edge_selection_change);
    window._architecture_graph.network.on("deselectEdge", _agraph_on_edge_selection_change);

    function agraph_refresh_inspector() {
        let inspector = $('#_architecture_inspector_div');
        inspector.html('');
        let html = agraph_inspector_graph_html();
        html += agraph_inspector_node_html();
        html += agraph_inspector_topic_html();
        inspector.html(html);
    }

    function agraph_load_query_from_browser() {
        let query_key = '_DUCKIETOWN_DUCKIEBOT._ROBOT._ARCHITECTURE._QUERY';
        if (localStorage.getItem(query_key) !== null) {
            window._architecture_graph.query = JSON.parse(localStorage.getItem(query_key));
        }
        let btnf = '#_architecture_toolbox_form input[data-query-key={0}][data-query-value={1}]';
        let btnf_all = '#_architecture_toolbox_form input[data-query-key={0}][data-query-value!={1}]';
        for (let [key, value] of Object.entries(window._architecture_graph.query)) {
            let btn = $(btnf.format(key, value));
            let btn_all = $(btnf_all.format(key, value));
            btn_all.closest('label').removeClass('active');
            btn.prop('checked', true);
            btn.closest('label').addClass('active');
        }
    }

    function agraph_apply_loaded(res, source) {
        window._architecture_graph.api_data = res.data;
        window._architecture_graph.source = source;
        window._architecture_graph._loaded_ok = true;
        agraph_apply_query();
        agraph_redraw();
        agraph_perform_clustering();
        let n = Object.keys(res.data.nodes || {}).length;
        let t = Object.keys(res.data.topics || {}).length;
        if (n === 0 && t === 0) {
            agraph_set_empty(true, 'ROS graph is empty - no nodes or topics found.');
            agraph_set_status('bad', 'Empty graph (' + source + ')');
        } else {
            agraph_set_empty(false);
            let note = source === 'rosbridge'
                ? ' · Health/freq metrics limited without ROS API'
                : '';
            agraph_set_status('ok', 'Loaded {0} nodes · {1} topics via {2}{3}'.format(n, t, source, note));
            setTimeout(function() {
                try { window._architecture_graph.network.fit({ animation: false }); } catch (e) {}
            }, 400);
        }
    }

    function agraph_refresh(force) {
        if (!force) {
            agraph_apply_query();
            agraph_redraw();
            agraph_perform_clustering();
            return;
        }
        if (window._architecture_graph._loading) return;
        window._architecture_graph._loading = true;

        agraph_set_status('wait', 'Loading ROS graph…');
        agraph_set_empty(true, 'Fetching ROS graph…');

        function done() {
            window._architecture_graph._loading = false;
        }

        agraph_ros_api(function(res) {
            agraph_apply_loaded(res, 'ROS API');
            done();
        }, function(err) {
            // Optional rosbridge fallback (robot_ui/architecture_rosbridge_fallback).
            if (!window._architecture_graph.rosbridge_fallback) {
                agraph_set_empty(true, 'ROS HTTP API unavailable. Enable robot_ui/architecture_rosbridge_fallback or restore the /ros service on :8084.');
                agraph_set_status('bad', String(err));
                done();
                return;
            }
            agraph_set_status('wait', err + ' - trying rosbridge…');
            agraph_load_via_rosbridge().then(function(res) {
                agraph_apply_loaded(res, 'rosbridge');
            }).catch(function(bridge_err) {
                agraph_set_empty(true, 'Could not load Architecture. ROS HTTP API is down and rosbridge fallback failed.');
                agraph_set_status('bad', String(bridge_err));
            }).then(done);
        });
    }

    $(document).ready(function () {
        agraph_load_query_from_browser();
        agraph_refresh(true);
    });

    // Retry only if initial load failed and bridge connects later.
    $(document).on(<?php echo json_encode($connected_evt) ?>, function() {
        if (!window._architecture_graph._loaded_ok && !window._architecture_graph._loading) {
            agraph_refresh(true);
        }
    });

    $('#_architecture_reload_btn, #_architecture_empty_reload').on('click', function() {
        window._architecture_graph._loaded_ok = false;
        agraph_refresh(true);
    });

    $('#_architecture_toolbox_form input').change(function(){
        let key = $(this).data('query-key');
        window._architecture_graph.query[key] = $(this).data('query-value');
        localStorage.setItem(
            '_DUCKIETOWN_DUCKIEBOT._ROBOT._ARCHITECTURE._QUERY',
            JSON.stringify(window._architecture_graph.query)
        );
        agraph_refresh(false);
    });

    function agraph_get_node(node_id) {
        for (let node of window._architecture_graph.data.nodes) {
            if (node.id === node_id) return node;
        }
        return null;
    }

    function agraph_get_edge(edge_id) {
        for (let edge of window._architecture_graph.data.edges) {
            if (edge.id === edge_id) return edge;
        }
        return null;
    }

    function agraph_apply_query() {
        window._architecture_graph.data.nodes = [];
        window._architecture_graph.data.edges = [];

        let query = window._architecture_graph.query;
        let data = window._architecture_graph.api_data || {};
        if (!data.graph) data.graph = {};
        if (!data.graph.edges) {
            data.graph.edges = {
                node_to_node: [],
                node_to_topic: [],
                topic_to_topic: [],
                topic_to_node: []
            };
        }
        if (!data.nodes) data.nodes = {};
        if (!data.topics) data.topics = {};
        ['node_to_node', 'node_to_topic', 'topic_to_topic', 'topic_to_node'].forEach(function(k) {
            if (!Array.isArray(data.graph.edges[k])) data.graph.edges[k] = [];
        });

        let nodes = new Set();
        let topics = new Set();

        if (query['node-filter'] === 'enabled') {
            for (const [node, info] of Object.entries(data.nodes)) {
                if (info['enabled'] !== false) nodes.add(node);
            }
        } else if (query['node-filter'] === 'active') {
            for (let edge of data.graph.edges.node_to_topic) nodes.add(edge.from);
            for (let edge of data.graph.edges.topic_to_node) nodes.add(edge.to);
        } else {
            nodes = new Set(Object.keys(data.nodes));
        }

        if (query['topic-filter'] === 'subscribed') {
            for (let edge of data.graph.edges.topic_to_node) topics.add(edge.from);
        } else if (query['topic-filter'] === 'used') {
            for (let edge of data.graph.edges.topic_to_node) {
                if (nodes.has(edge.to)) topics.add(edge.from);
            }
            for (let edge of data.graph.edges.node_to_topic) {
                if (nodes.has(edge.from)) topics.add(edge.to);
            }
        } else {
            topics = new Set(Object.keys(data.topics));
        }

        let node_list = Array.from(nodes).sort();
        for (let node of node_list) {
            let info = data.nodes[node];
            if (!info) continue;
            window._architecture_graph.data.nodes.push({
                id: 'node:' + node,
                label: node,
                shape: 'box',
                group: 'ros_node',
                color: agraph_get_node_color(info, query['node-color']),
                _node: node,
                _module_type: info.module_type,
                _module_instance: info.module_instance,
                _machine: info.machine,
                _type: info.type
            });
        }

        let topic_list = Array.from(topics).sort();
        if ([undefined, 'ellipse'].includes(query['topic-shape'])) {
            for (let topic of topic_list) {
                let info = window._architecture_graph.api_data.topics[topic];
                if (!info) continue;
                window._architecture_graph.data.nodes.push({
                    id: 'topic:' + topic,
                    label: topic,
                    shape: 'ellipse',
                    group: 'ros_topic',
                    color: agraph_get_topic_color(info, query['edge-color']),
                    _topic: topic
                });
            }
        }

        if ([undefined, 'ellipse'].includes(query['topic-shape'])) {
            for (let edge of data.graph.edges.node_to_topic) {
                if (!nodes.has(edge.from) || !topics.has(edge.to)) continue;
                let info = window._architecture_graph.api_data.topics[edge.to] || {};
                window._architecture_graph.data.edges.push({
                    id: 'edge:{0}:{1}'.format(edge.from, edge.to),
                    from: 'node:' + edge.from,
                    to: 'topic:' + edge.to,
                    width: agraph_get_edge_width(info, query['edge-width']),
                    color: agraph_get_topic_color(info, query['edge-color']),
                    _topic: edge.to
                });
            }
            for (let edge of data.graph.edges.topic_to_node) {
                if (!topics.has(edge.from) || !nodes.has(edge.to)) continue;
                let info = window._architecture_graph.api_data.topics[edge.from] || {};
                window._architecture_graph.data.edges.push({
                    id: 'edge:{0}:{1}'.format(edge.from, edge.to),
                    from: 'topic:' + edge.from,
                    to: 'node:' + edge.to,
                    width: agraph_get_edge_width(info, query['edge-width']),
                    color: agraph_get_topic_color(info, query['edge-color']),
                    _topic: edge.from
                });
            }
        }
        if (query['topic-shape'] === 'label') {
            for (let edge of data.graph.edges.node_to_node) {
                if (!nodes.has(edge.from) || !nodes.has(edge.to)) continue;
                let info = window._architecture_graph.api_data.topics[edge.middle] || {};
                window._architecture_graph.data.edges.push({
                    id: 'edge:{0}:{1}'.format(edge.from, edge.to),
                    from: 'node:' + edge.from,
                    to: 'node:' + edge.to,
                    label: edge.middle,
                    width: agraph_get_edge_width(info, query['edge-width']),
                    color: agraph_get_topic_color(info, query['edge-color']),
                    _topic: edge.middle
                });
            }
        }
    }

    function agraph_perform_clustering() {
        let query = window._architecture_graph.query;
        if (query['node-cluster'] === 'module') agraph_cluster_by_module();
        if (query['node-cluster'] === 'machine') agraph_cluster_by_machine();
    }

    function agraph_redraw() {
        window._architecture_graph.network.setData(window._architecture_graph.data);
        window._architecture_graph.network.redraw();
        agraph_refresh_inspector();
    }

</script>

<script
        src="<?php echo Core::getJSscriptURL('agraph_formatting.js', 'duckietown_duckiebot') ?>"
        type="text/javascript">
</script>
<script
        src="<?php echo Core::getJSscriptURL('agraph_clustering.js', 'duckietown_duckiebot') ?>"
        type="text/javascript">
</script>
<script
        src="<?php echo Core::getJSscriptURL('agraph_inspector.js', 'duckietown_duckiebot') ?>"
        type="text/javascript">
</script>
