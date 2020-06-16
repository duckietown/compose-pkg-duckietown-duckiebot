<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele

use \system\classes\Core;
use \system\packages\duckietown_duckiebot\Duckiebot;

$sides_size_px = 1;
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
        height: 700px;
        border: 1px solid lightgray;
    }

    #_architecture_table {
        width: 100%;
    }

    #_architecture_table #_architecture_toolbox,
    #_architecture_table #_architecture_details {
        min-width: <?php echo $sides_size_px ?>px;
        width: <?php echo $sides_size_px ?>px;
        max-width: <?php echo $sides_size_px ?>px;
    }

    #_architecture_table #_architecture_container {
        width: 100%;
        min-width: <?php echo $min_canvas_width_px ?>px;
    }

    #_architecture_table #_architecture_container #_graph_canvas{
        background-color: white;
    }
</style>


<table id="_architecture_table">
    <tr>
        <td id="_architecture_toolbox">

        </td>
        <td id="_architecture_container">
            <div id="_graph_canvas"></div>
        </td>
        <td id="_architecture_details">

        </td>
    </tr>
</table>

<!--<input type="button" onclick="clusterByCid()" value="Cluster all nodes with CID = 1"> <br/>-->
<input type="button" onclick="clusterByModuleType()" value="Cluster by Module Type"> <br/>
<!--<input type="button" onclick="clusterByConnection()" value="Cluster 'node 1' by connections"> <br/>-->
<!--<input type="button" onclick="clusterOutliers()" value="Cluster outliers"> <br/>-->
<!--<input type="button" onclick="clusterByHubsize()" value="Cluster by hubsize"> <br/>-->


<script type="text/javascript">
    // this object model the graph
    window._architecture_graph = {};
    // data contains the graph data (nodes and edges)
    window._architecture_graph.data = {
        nodes: [],
        edges: []
    };
    // query holds the user configuration of the graph (e.g., show_topics, group_by_module)
    window._architecture_graph.query = {};
    // container is the HTML object the graph is drawn on
    window._architecture_graph.container = document.getElementById('_graph_canvas');
    // options are the vis.js options for the graph renderer
    window._architecture_graph.options = {
        layout: {
            hierarchical: {
                enabled: true,
                levelSeparation: 45,
                nodeSpacing: 20,
                direction: "UD",
                sortMethod: "directed",
                shakeTowards: "roots"
            }
        },
        physics: {
            hierarchicalRepulsion: {
                centralGravity: 0,
                springLength: 120,
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
            // smooth: {
            //     type: "cubicBezier",
            //     forceDirection: "vertical",
            //     roundness: 0.4
            // },
            arrows: "to",
            color: {
                color: "gray"
            }
        },
        nodes: {
            font: {
                size: 18
            }
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
            enabled: true,
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
    function _ROS_API(callback, group, action, selector = '', arguments = {}) {
        let hostname = "<?php echo Core::getSetting(
            'ros_api_host', 'duckietown_duckiebot', Duckiebot::getDuckiebotHostname()
        ) ?>";
        let url = 'http://{0}/ros/{1}/{2}/{3}{4}'.format(
            hostname, group, action, selector, $.param(arguments)
        );
        // remove trailing slashes
        url = url.replace(/\/+$/, "");

        function errorFcn() {}

        callExternalAPI(url, 'GET', 'json', false, false, callback, true, true, errorFcn);
    }

    window._architecture_graph.network.on("selectNode", function (params) {
        if (params.nodes.length === 1) {
            if (window._architecture_graph.network.isCluster(params.nodes[0]) === true) {
                window._architecture_graph.network.openCluster(params.nodes[0]);
            }
        }
    });

    $(document).ready(function () {
        // fetch ROS graph
        _ROS_API(
            function (res) {
                let subscribed_topics = new Set();

                // find subscribed nodes
                for (let edge of res.data.graph.edges.topic_to_node) {
                    subscribed_topics.add(edge.from);
                }

                // add nodes
                for (let node of Object.keys(res.data.graph.nodes)) {
                    window._architecture_graph.data.nodes.push({
                        id: 'node:' + node,
                        label: node,
                        shape: 'box',
                        group: 'ros_node',
                        _module_type: res.data.graph.nodes[node].module_type,
                        _module_instance: res.data.graph.nodes[node].module_instance,
                        _machine: res.data.graph.nodes[node].machine,
                        _type: res.data.graph.nodes[node].type
                    })
                }
                // add topics
                for (let topic of subscribed_topics) {
                    window._architecture_graph.data.nodes.push({
                        id: 'topic:' + topic,
                        label: topic,
                        shape: 'ellipse',
                        group: 'ros_topic'
                    })
                }
                // add edges (node -> topic)
                for (let edge of res.data.graph.edges.node_to_topic) {
                    if (subscribed_topics.has(edge.to)){
                        window._architecture_graph.data.edges.push({
                            from: 'node:' + edge.from,
                            to: 'topic:' + edge.to
                        })
                    }
                }
                // add edges (topic -> node)
                for (let edge of res.data.graph.edges.topic_to_node) {
                    window._architecture_graph.data.edges.push({
                        from: 'topic:' + edge.from,
                        to: 'node:' + edge.to
                    })
                }
                // ---
                window._architecture_graph.network.setData(window._architecture_graph.data);
                window._architecture_graph.network.redraw();
            },
            'graph', ''
        );
    });

</script>

<script
        src="<?php echo Core::getJSscriptURL('architecture_clustering.js', 'duckietown_duckiebot') ?>"
        type="text/javascript">
</script>