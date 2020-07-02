<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele

use \system\classes\Core;
use \system\packages\duckietown_duckiebot\Duckiebot;

$sides_size_px = 340;
$height_px = 700;
$min_canvas_width_px = 970 - 2 * $sides_size_px;
?>

<script
        src="<?php echo Core::getJSscriptURL('vis-network.min.js', 'duckietown') ?>"
        type="text/javascript">
</script>

<style type="text/css">
    /* enlarge page container */
    body > #page_container {
        min-width: 100%;
    }

    #_graph_canvas {
        width: 100%;
        height: <?php echo $height_px ?>px;
        border: 1px solid lightgray;
    }

    #_architecture_table {
        width: 100%;
    }

    #_architecture_table #_architecture_toolbox_container,
    #_architecture_table #_architecture_inspector_container {
        min-width: <?php echo $sides_size_px ?>px;
        width: <?php echo $sides_size_px ?>px;
        max-width: <?php echo $sides_size_px ?>px;
    }

    #_architecture_table #_architecture_container {
        width: 100%;
        min-width: <?php echo $min_canvas_width_px ?>px;
        padding: 0 10px;
    }

    #_architecture_table #_architecture_container #_graph_canvas {
        background-color: white;
    }
    
    #_architecture_toolbox,
    #_architecture_inspector{
        height: <?php echo $height_px ?>px;
        margin: 0;
    }
    
    #_architecture_toolbox .panel-body,
    #_architecture_inspector .panel-body{
        height: <?php echo $height_px - 44 ?>px;
        position: relative;
        overflow: hidden;
        width: 100%;
    }
    
    #_architecture_toolbox .panel-body #_architecture_toolbox_form,
    #_architecture_inspector .panel-body #_architecture_inspector_div{
        position: absolute;
        overflow: auto;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 10px 22px 10px 12px;
    }
    
    #_architecture_inspector_div .dl-horizontal dt {
        width: 50px;
    }
    
    #_architecture_inspector_div .dl-horizontal dd {
        margin-left: 50px;
        padding-left: 8px;
    }
</style>


<table id="_architecture_table">
    <tr>
        <td id="_architecture_toolbox_container">
            
            <div class="panel panel-default" id="_architecture_toolbox">
                <div class="panel-heading">
                    <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
                    &nbsp;
                    Toolbox
                </div>
                <div class="panel-body">
                    
                    <div id="_architecture_toolbox_form">
                        
                        <!-- Group: Nodes -->
                        <h4>Nodes:</h4>
                        <div style="padding-left: 16px">
                            
                            <!-- Filter -->
                            <h5>Filter:</h5>
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-default active">
                                    <input type="radio" name="options" data-query-key="node-filter" data-query-value="all">
                                    All
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="options" data-query-key="node-filter" data-query-value="enabled">
                                    Enabled
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="options" data-query-key="node-filter" data-query-value="active">
                                    Active
                                </label>
                            </div>
                            <hr>
                            
                            <!-- Color -->
                            <h5>Color:</h5>
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-default active">
                                    <input type="radio" name="options" data-query-key="node-color" data-query-value="none">
                                    No
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="options" data-query-key="node-color" data-query-value="health">
                                    Health
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="options" data-query-key="node-color" data-query-value="status">
                                    Status
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="options" data-query-key="node-color" data-query-value="type">
                                    Type
                                </label>
                            </div>
                            <hr>
                            
                            <!-- Cluster -->
                            <h5>Cluster:</h5>
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-default active">
                                    <input type="radio" name="options" data-query-key="node-cluster" data-query-value="none">
                                    No
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="options" data-query-key="node-cluster" data-query-value="module">
                                    Module
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="options" data-query-key="node-cluster" data-query-value="machine">
                                    Machine
                                </label>
                            </div>
                            
                        </div>
                        <hr>
                        
                        <!-- Group: Topics -->
                        <h4>Topics:</h4>
                        <div style="padding-left: 16px">
                            
                            <!-- Filter -->
                            <h5>Filter:</h5>
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-default active">
                                    <input type="radio" name="options" data-query-key="topic-filter" data-query-value="all">
                                    All
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="options" data-query-key="topic-filter" data-query-value="used">
                                    Used
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="options" data-query-key="topic-filter" data-query-value="subscribed">
                                    Subscribed
                                </label>
                            </div>
                            <hr>
                            
                            <!-- Shape -->
                            <h5>Render as:</h5>
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-default active">
                                    <input type="radio" name="options" data-query-key="topic-shape" data-query-value="ellipse">
                                    Ellipses
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="options" data-query-key="topic-shape" data-query-value="label">
                                    Edge labels
                                </label>
                            </div>
                            <hr>
                            
                            <!-- Width -->
                            <h5>Width:</h5>
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-default active">
                                    <input type="radio" name="options" data-query-key="edge-width" data-query-value="none">
                                    No
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="options" data-query-key="edge-width" data-query-value="frequency">
                                    Frequency
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="options" data-query-key="edge-width" data-query-value="bandwidth">
                                    Bandwidth
                                </label>
                            </div>
                            <hr>
                            
                            <!-- Color -->
                            <h5>Color:</h5>
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-default active">
                                    <input type="radio" name="options" data-query-key="edge-color" data-query-value="none">
                                    No
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="options" data-query-key="edge-color" data-query-value="health">
                                    Health
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="options" data-query-key="edge-color" data-query-value="type">
                                    Type
                                </label>
                            </div>
                            
                        </div>
                        <hr>
                        
                    </div>
                    
                </div>
            </div>
            
        </td>
        <td id="_architecture_container">
            <div id="_graph_canvas"></div>
        </td>
        <td id="_architecture_inspector_container">
            
            <div class="panel panel-default" id="_architecture_inspector">
                <div class="panel-heading">
                    <span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>
                    &nbsp;
                    Inspector
                </div>
                <div class="panel-body">
                    
                    <div id="_architecture_inspector_div">
                    </div>
                    
                </div>
            </div>
            
        </td>
    </tr>
</table>


<script type="text/javascript">
    // this object model the graph
    window._architecture_graph = {};
    // raw data as received from the API
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
    // data contains the graph data (nodes and edges), obtained by applying filters to api_data
    window._architecture_graph.data = {
        nodes: [],
        edges: []
    };
    // query holds the user configuration of the graph (e.g., show_topics, group_by_module)
    window._architecture_graph.query = {};
    // inspector data holds data (and some history) that will be shown to the right
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
    // container is the HTML object the graph is drawn on
    window._architecture_graph.container = document.getElementById('_graph_canvas');
    // options are the vis.js options for the graph renderer
    window._architecture_graph.options = {
        layout: {
            hierarchical: {
                enabled: true,
                levelSeparation: 200,
                nodeSpacing: 20,
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
                nodeDistance: 260,
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
            color: {
                color: "gray"
            },
            font: {
                size: 24
            },
            width: 2
        },
        nodes: {
            font: {
                size: 28
            },
            margin: 12
        },
        groups: {
            ros_node: {
                color: {
                    // default color
                }
            },
            ros_topic: {
                color: {
                    border: "gray",
                    background: "#F0F0F0",
                    highlight: {
                        border: "darkgray",
                        background: "#C8C8C8",
                    }
                }
            }
        },
        // NOTE: re-enable this block to tune the options
        configure: {
            enabled: false,
            filter: 'physics, layout',
            showButton: true
        }
    };

    // network is the vis.Network instance
    window._architecture_graph.network = new vis.Network(
        window._architecture_graph.container,
        window._architecture_graph.data,
        window._architecture_graph.options
    );

    // helper functions
    function agraph_ros_api(callback, group, action, selector = '', arguments = {}) {
        let hostname = "<?php echo Core::getSetting(
            'ros_api_host', 'duckietown_duckiebot', Duckiebot::getDuckiebotHostname()
        ) ?>";
        let url = 'http://{0}/ros/{1}/{2}/{3}{4}'.format(
            hostname, group, action, selector, $.param(arguments)
        );
        // remove trailing slashes
        url = url.replace(/\/+$/, "");
        callExternalAPI(url, 'GET', 'json', false, false, callback, true, true);
    }

    // node selection
    function _agraph_on_node_selection_change(params) {
        window._architecture_graph.inspector.current.node = null;
        window._architecture_graph.inspector.current.topic = null;
        window._architecture_graph.inspector.current.module = null;
        // only single selection is supported
        if (params.nodes.length === 1) {
            let node_id = params.nodes[0];
            if (window._architecture_graph.network.isCluster(node_id) === true) {
                // it is a cluster, unpack it
                window._architecture_graph.network.openCluster(node_id);
            } else {
                // it is a ROS node/topic, show its info in the inspector
                if (node_id.startsWith('node:')) {
                    let node = agraph_get_node(node_id)._node;
                    window._architecture_graph.inspector.current.node = node;
                } else {
                    let topic = agraph_get_node(node_id)._topic;
                    window._architecture_graph.inspector.current.topic = topic;
                }
            }
        }
        agraph_refresh_inspector();
    }
    window._architecture_graph.network.on("selectNode", _agraph_on_node_selection_change);
    window._architecture_graph.network.on("deselectNode", _agraph_on_node_selection_change);

    // edge selection
    function _agraph_on_edge_selection_change(params) {
        if ([undefined, 'ellipse'].includes(window._architecture_graph.query['topic-shape']))
            return;
        // ---
        window._architecture_graph.inspector.current.topic = null;
        // only single selection is supported
        if (params.edges.length === 1) {
            let edge = params.edges[0];
            window._architecture_graph.inspector.current.topic = agraph_get_edge(edge)._topic;
        }
        agraph_refresh_inspector();
    }
    window._architecture_graph.network.on("selectEdge", _agraph_on_edge_selection_change);
    window._architecture_graph.network.on("deselectEdge", _agraph_on_edge_selection_change);
    
    function agraph_refresh_inspector() {
        let inspector = $('#_architecture_inspector_div');
        // clear inspector
        inspector.html('');
        // render graph info
        let html = agraph_inspector_graph_html();
        html += agraph_inspector_node_html();
        html += agraph_inspector_topic_html();
        // fill inspector
        inspector.html(html);
    }
    
    function agraph_load_query_from_browser() {
        // load last query
        let query_key = '_DUCKIETOWN_DUCKIEBOT._ROBOT._ARCHITECTURE._QUERY';
        if (localStorage.getItem(query_key) !== null) {
            window._architecture_graph.query = JSON.parse(localStorage.getItem(query_key));
        }
        // apply query to toolbox
        let btnf = '#_architecture_toolbox_form input[data-query-key={0}][data-query-value={1}]';
        let btnf_all = '#_architecture_toolbox_form input[data-query-key={0}][data-query-value!={1}]';
        for (let [key, value] of Object.entries(window._architecture_graph.query)) {
            let btn = $(btnf.format(key, value));
            let btn_all = $(btnf_all.format(key, value));
            btn_all.closest('label').removeClass('active');
            btn.closest('label').addClass('active');
        }
    }

    $(document).ready(function () {
        // load last query
        agraph_load_query_from_browser();
        // refresh graph
        agraph_refresh(true);
    });
    
    $('#_architecture_toolbox_form input').change(function(evt){
        let key = $(this).data('query-key');
        // update query
        window._architecture_graph.query[key] = $(this).data('query-value');
        // store current query
        localStorage.setItem(
            '_DUCKIETOWN_DUCKIEBOT._ROBOT._ARCHITECTURE._QUERY',
            JSON.stringify(window._architecture_graph.query)
        );
        // refresh graph
        agraph_refresh();
    });
    
    function agraph_refresh(force = false){
        function refresh(res) {
            if (res !== undefined) {
                 window._architecture_graph.api_data = res.data;
            }
            // apply query
            agraph_apply_query();
            // redraw
            agraph_redraw();
            // perform clustering
            agraph_perform_clustering();
        }
        // fetch ROS graph
        if (force) {
            agraph_ros_api(refresh, 'graph', '');
        } else {
            refresh();
        }
    }
    
    function agraph_get_node(node_id) {
        for (let node of window._architecture_graph.data.nodes) {
            if (node.id === node_id) {
                return node;
            }
        }
        return null;
    }
    
    function agraph_get_edge(edge_id) {
        for (let edge of window._architecture_graph.data.edges) {
            if (edge.id === edge_id) {
                return edge;
            }
        }
        return null;
    }
    
    function agraph_apply_query() {
        // clear old data
        window._architecture_graph.data.nodes = [];
        window._architecture_graph.data.edges = [];
        
        // get new data
        let query = window._architecture_graph.query;
        let data = window._architecture_graph.api_data;
        
        // prepare data structures
        let nodes = new Set();
        let topics = new Set();
        
        // get nodes
        if (query['node-filter'] === 'enabled') {
            // only nodes that are not disabled
            for (const [node, info] of Object.entries(data.nodes)) {
                if (info['enabled'] !== false) {
                    nodes.add(node);
                }
            }
        } else if (query['node-filter'] === 'active') {
            // only nodes with at least one incoming/outgoing edge
            for (let edge of data.graph.edges.node_to_topic) {
                nodes.add(edge.from);
            }
            for (let edge of data.graph.edges.topic_to_node) {
                nodes.add(edge.to);
            }
        } else {
            // all nodes
            nodes = new Set(Object.keys(data.nodes));
        }

        // get topics
        if (query['topic-filter'] === 'subscribed') {
            // topics with at least one subscriber
            for (let edge of data.graph.edges.topic_to_node) {
                topics.add(edge.from);
            }
        } else if (query['topic-filter'] === 'used') {
            // topics with at least one publisher/subscriber
            for (let edge of data.graph.edges.topic_to_node) {
                if (nodes.has(edge.to))
                    topics.add(edge.from);
            }
            for (let edge of data.graph.edges.node_to_topic) {
                if (nodes.has(edge.from))
                    topics.add(edge.to);
            }
        } else {
            // all topics
            topics = new Set(Object.keys(data.topics));
        }
        
        // add Nodes
        // - ROS nodes
        nodes = Array.from(nodes).sort();
        for (let node of nodes) {
            let info = data.nodes[node];
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
        // - ROS topics
        topics = Array.from(topics).sort();
        if ([undefined, 'ellipse'].includes(query['topic-shape'])) {
            for (let topic of topics) {
                let info = window._architecture_graph.api_data.topics[topic];
                window._architecture_graph.data.nodes.push({
                    id: 'topic:' + topic,
                    label: topic,
                    shape: 'ellipse',
                    group: 'ros_topic',
                    color: agraph_get_topic_color(info, query['edge-color']),
                    _topic: topic
                })
            }
        }
        
        // add Edges
        if ([undefined, 'ellipse'].includes(query['topic-shape'])) {
            // - node -> topic
            for (let edge of data.graph.edges.node_to_topic) {
                let info = window._architecture_graph.api_data.topics[edge.to];
                window._architecture_graph.data.edges.push({
                    id: 'edge:{0}:{1}'.format(edge.from, edge.to),
                    from: 'node:' + edge.from,
                    to: 'topic:' + edge.to,
                    width: agraph_get_edge_width(info, query['edge-width']),
                    color: agraph_get_topic_color(info, query['edge-color']),
                    _topic: edge.to
                })
            }
            // - topic -> node
            for (let edge of data.graph.edges.topic_to_node) {
                let info = window._architecture_graph.api_data.topics[edge.from];
                window._architecture_graph.data.edges.push({
                    id: 'edge:{0}:{1}'.format(edge.from, edge.to),
                    from: 'topic:' + edge.from,
                    to: 'node:' + edge.to,
                    width: agraph_get_edge_width(info, query['edge-width']),
                    color: agraph_get_topic_color(info, query['edge-color']),
                    _topic: edge.from
                })
            }
        }
        // - node -> node
        if (query['topic-shape'] === 'label') {
            for (let edge of data.graph.edges.node_to_node) {
                let info = window._architecture_graph.api_data.topics[edge.middle];
                window._architecture_graph.data.edges.push({
                    id: 'edge:{0}:{1}'.format(edge.from, edge.to),
                    from: 'node:' + edge.from,
                    to: 'node:' + edge.to,
                    label: edge.middle,
                    width: agraph_get_edge_width(info, query['edge-width']),
                    color: agraph_get_topic_color(info, query['edge-color']),
                    _topic: edge.middle
                })
            }
        }
        
        // ---
        agraph_redraw();
    }
    
    function agraph_perform_clustering() {
        // get query
        let query = window._architecture_graph.query;
        // clustering
        if (query['node-cluster'] === 'module') {
            agraph_cluster_by_module();
        }
        if (query['node-cluster'] === 'machine') {
            agraph_cluster_by_machine();
        }
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