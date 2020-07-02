// This is the structure of `window._architecture_graph.inspector`:
//
//         {
//             current: {
//                 node: null,
//                 topic: null,
//                 module: null,
//                 legend: null
//             },
//             data: {
//                 nodes: {},
//                 topics: {},
//                 modules: {}
//             }
//         }
//


let _GRAPH_FMT = `
<div id="_architecture_inspector_graph">
    <h4>Graph:</h4>
    <div style="padding-left: 5px">
        <div class="col-md-6">
            <strong>Nodes: </strong>{0}
        </div>
        <div class="col-md-6">
            <strong>Topics: </strong>{1}
        </div>
    </div>
</div>
`;

let _NODE_FMT = `
<br/>
<br/>
<div id="_architecture_inspector_node">
    <h4>Node:</h4>
    <div style="padding-left: 5px">
        <pre style="margin-left: 10px; margin-bottom: 0">{node}</pre>
    </div>
    <div class="col-md-12" style="margin-top: 10px">
        <strong>Status: </strong>
        <span class="label label-default" style="background-color: {status_color}">{status}</span>
    </div>
    <div class="col-md-12" style="margin-top: 10px">
        <strong>Health: </strong>
        <span class="label label-default" style="background-color: {health_color}">{health}</span>
    </div>
    <div class="col-md-12" style="margin-top: 10px; padding-right: 0">
        <strong>Health reason: </strong>
        <pre style="margin-bottom: 0">{health_reason}</pre>
    </div>
    <div class="col-md-12" style="margin-top: 10px">
        <strong>Type: </strong>{type}
    </div>
    <div class="col-md-12" style="margin-top: 10px">
        <strong>Module: </strong>{module_type}
    </div>
    <div class="col-md-12" style="margin-top: 10px">
        <strong>Machine: </strong>{machine}
    </div>
    <div class="col-md-12" style="margin-top: 10px">
        <strong>Topics (Incoming): </strong>
        <pre style="margin-bottom: 0">{topics_in}</pre>
    </div>
    <div class="col-md-12" style="margin-top: 10px">
        <strong>Topics (Outgoing): </strong>
        <pre style="margin-bottom: 0">{topics_out}</pre>
    </div>
    <div class="col-md-12" style="margin-top: 10px">
        <strong>Services: </strong>
        <pre style="margin-bottom: 0">{services}</pre>
    </div>
    <div class="col-md-12" style="margin-top: 10px">
        <strong>Parameters: </strong>
        <pre style="margin-bottom: 0">{parameters}</pre>
    </div>
</div>
`;

let _TOPIC_FMT = `
<br/>
<br/>
<div id="_architecture_inspector_topic">
    <h4>Topic:</h4>
    <div style="padding-left: 5px">
        <pre style="margin-left: 10px; margin-bottom: 0">{topic}</pre>
    </div>
    <div class="col-md-12" style="margin-top: 10px">
        <strong>Type: </strong>{type}
    </div>
    <div class="col-md-12" style="margin-top: 10px">
        <strong>Frequency: </strong>{frequency_str}
    </div>
    <div class="col-md-12" style="margin-top: 10px">
        <strong>Effective Frequency: </strong>{effective_frequency_str}
    </div>
    <div class="col-md-12" style="margin-top: 10px">
        <strong>Bandwidth: </strong>{bandwidth_str}
    </div>
    <div class="col-md-12" style="margin-top: 10px; padding-right: 0">
        <strong>Health: </strong>
        <div class="progress">
            <div class="progress-bar"
                role="progressbar"
                aria-valuenow="{usage}"
                aria-valuemin="0"
                aria-valuemax="100"
                style="width: {usage}%; background-image: none; background-color: {usage_color}">
                {usage}%
            </div>
        </div>
    </div>
</div>
`;


function agraph_inspector_graph_html() {
    let is_node = n => n.group === 'ros_node';
    let is_topic = n => n.group === 'ros_topic';
    let num_nodes = window._architecture_graph.data.nodes.filter(is_node).length;
    let num_topics = window._architecture_graph.data.nodes.filter(is_topic).length;
    return _GRAPH_FMT.format(num_nodes, num_topics);
}


function agraph_inspector_node_html() {
    let node = window._architecture_graph.inspector.current.node;
    if (node === null || node === undefined) return '';
    // ---
    let info = window._architecture_graph.api_data.nodes[node];
    return _NODE_FMT.format({
        node: node,
        health_color: _agraph_health_color(info.health_value),
        status: (info.enabled === null)? 'UNKNOWN' : ((info.enabled === true)? 'ENABLED' : 'DISABLED'),
        status_color: agraph_get_node_color(info, 'status').background,
        ...info
    });
}


function agraph_inspector_topic_html() {
    let topic = window._architecture_graph.inspector.current.topic;
    if (topic === null || topic === undefined) return '';
    // ---
    let info = window._architecture_graph.api_data.topics[topic];
    // compute info
    let bandwidth = Math.max(0.0, info.bandwidth || 0.0);
    let frequency = Math.max(0.0, info.frequency || 0.0);
    let effective_frequency = Math.max(0.0, info.effective_frequency || 0);
    let frequency_str = (info.frequency === null)?
        "ND" : "{0} Hz".format(frequency.toFixed(1));
    let effective_frequency_str = (info.effective_frequency === null)?
        "ND" : "{0} Hz".format(effective_frequency.toFixed(1));
    let bandwidth_str = (info.bandwidth === null) ?
        "ND" : "{0}/s".format(humanFileSize(bandwidth));
    let usage = (100 * (effective_frequency / Math.max(1, frequency))).toFixed(1);
    let usage_color = agraph_get_topic_color(info, 'health').color;
    // ---
    return _TOPIC_FMT.format({
        topic: topic,
        frequency_str: frequency_str,
        effective_frequency_str: effective_frequency_str,
        bandwidth_str: bandwidth_str,
        usage: usage,
        usage_color: usage_color,
        ...info
    });
}