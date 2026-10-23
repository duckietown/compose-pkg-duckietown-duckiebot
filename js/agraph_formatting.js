//NOTE: This has to match the value in dtros.constants.NodeHealth
let _NODE_HEALTH = {
    UNKNOWN: 0,
    STARTING: 5,
    STARTED: 6,
    HEALTHY: 10,
    WARNING: 20,
    ERROR: 30,
    FATAL: 40,
};

//NOTE: This has to match the value in dtros.constants.NodeType and dtros.constants.TopicType
let _TYPE = {
    GENERIC: 0,
    DRIVER: 1,
    PERCEPTION: 2,
    CONTROL: 3,
    PLANNING: 4,
    LOCALIZATION: 5,
    MAPPING: 6,
    SWARM: 7,
    BEHAVIOR: 8,
    VISUALIZATION: 9,
    INFRASTRUCTURE: 10,
    COMMUNICATION: 11,
    DIAGNOSTICS: 12,
    DEBUG: 20,
};

let _COLOR_SCALE = [
    ColorPalette.get('grey'),
    ColorPalette.get('red', 'dark', 4),
    ColorPalette.get('red'),
    ColorPalette.get('yellow', 'dark', 4),
    ColorPalette.get('yellow'),
    ColorPalette.get('green', 'dark', 2)
];

let _MAX_FREQUENCY = 30.0;
let _MAX_BANDWIDTH = 2 * (10 ** 6);  // 2 MB/s
let _MAX_EDGE_WIDTH = 16;


function _agraph_health_color(health, tone = 'standard') {
    if (health < _NODE_HEALTH.STARTED) {
        return ColorPalette.get('gray', tone);
    } else if (health >= _NODE_HEALTH.ERROR) {
        return ColorPalette.get('red', tone);
    } else if (health >= _NODE_HEALTH.WARNING) {
        return ColorPalette.get('orange', tone);
    } else if (health >= _NODE_HEALTH.STARTED) {
        return ColorPalette.get('cyan', tone);
    }
    return ColorPalette.get('green', tone);
}


function _agraph_status_color(enabled, tone = 'standard') {
    if (enabled === true) {
        return ColorPalette.get('green', tone);
    } else if (enabled === false) {
        return ColorPalette.get('red', tone);
    }
    return ColorPalette.get('grey', tone);
}


function _agraph_type_color(type, tone = 'standard') {
    if (!_TYPE.hasOwnProperty(type) || type === 'GENERIC') {
        return ColorPalette.get('grey', tone);
    }
    let types = Object.keys(_TYPE).sort();
    let idx = types.indexOf(type);
    return ColorPalette.geti(idx);
}


function agraph_get_node_color(node, filter) {
    if (filter === 'none') return {};
    let color = {
        border: "gray",
        background: null,
        highlight: {
            border: "darkgray",
            background: null,
        }
    };
    if (filter === 'health') {
        color.background = _agraph_health_color(node.health_value);
        color.highlight.background = _agraph_health_color(node.health_value, 'dark');
    }
    if (filter === 'status') {
        color.background = _agraph_status_color(node.enabled);
        color.highlight.background = _agraph_status_color(node.enabled, 'dark');
    }
    if (filter === 'type') {
        color.background = _agraph_type_color(node.type);
        color.highlight.background = _agraph_type_color(node.type, 'dark');
    }
    return color;
}


function agraph_get_topic_color(topic, filter) {
    if (filter === 'none') {
        return undefined;
    }
    let color = {
        border: "gray",
        color: ColorPalette.get('gray'),
        background: ColorPalette.get('gray'),
        highlight: {
            border: "gray",
            color: ColorPalette.get('gray', 'dark'),
            background: ColorPalette.get('gray')
        }
    };
    if (filter === 'health') {
        let frequency = Math.max(1.0, topic.frequency || 0.0);
        let effective_frequency = Math.max(0.0, topic.effective_frequency || 0);
        let health_idx = Math.min(
            _COLOR_SCALE.length,
            Math.max(
                1,
                Math.ceil((effective_frequency / frequency) * _COLOR_SCALE.length)
            )
        );
        health_idx -= 1;
        color.color = _COLOR_SCALE[health_idx];
        color.background = _COLOR_SCALE[health_idx];
        color.highlight.color = _COLOR_SCALE[health_idx];
        color.highlight.background = _COLOR_SCALE[health_idx];
    }
    if (filter === 'type') {
        color.color = _agraph_type_color(topic.type);
        color.background = _agraph_type_color(topic.type);
        color.highlight.color = _agraph_type_color(topic.type, 'dark');
        color.highlight.background = _agraph_type_color(topic.type, 'dark');
    }
    return color;
}


function agraph_get_edge_width(topic, filter) {
    let default_width = 2;
    if (filter === 'frequency') {
        let effective_frequency = Math.max(0.0, topic.effective_frequency || 0);
        return Math.max(
            default_width,
            Math.min(
                _MAX_EDGE_WIDTH,
                (effective_frequency / _MAX_FREQUENCY) * _MAX_EDGE_WIDTH
            )
        );
    }
    if (filter === 'bandwidth') {
        let bandwidth = Math.max(1.0, topic.bandwidth || 0.0);
        return Math.max(
            default_width,
            Math.min(
                _MAX_EDGE_WIDTH,
                (bandwidth / _MAX_BANDWIDTH) * _MAX_EDGE_WIDTH
            )
        );
    }
    return default_width;
}