function agraph_cluster_by_module() {
    let module_types = new Set();
    for (let node of window._architecture_graph.data.nodes) {
        if (node._module_type !== undefined && node._module_type !== null){
            module_types.add(node._module_type);
        }
    }
    module_types = Array.from(module_types).sort();
    let palette = new ColorPalette();
    for (let module_type of module_types){
        let clusterOptionsByData = {
            joinCondition: function(childOptions) {
                return childOptions.group === 'ros_node' && childOptions._module_type === module_type;
            },
            clusterNodeProperties: {
                id: 'module_type:' + module_type,
                label: module_type,
                shape: 'circle',
                color: {
                    border: "gray",
                    background: palette.next(),
                    highlight: {
                        border: "darkgray",
                        background: palette.current('dark'),
                    }
                },
                _module_type: module_type
            }
        };
        window._architecture_graph.network.cluster(clusterOptionsByData);
    }
}


function agraph_cluster_by_machine() {
    let machines = new Set();
    for (let node of window._architecture_graph.data.nodes) {
        if (node._machine !== undefined && node._machine !== null){
            machines.add(node._machine);
        }
    }
    machines = Array.from(machines).sort();
    let palette = new ColorPalette();
    for (let machine of machines){
        let clusterOptionsByData = {
            joinCondition: function(childOptions) {
                return childOptions.group === 'ros_node' && childOptions._machine === machine;
            },
            clusterNodeProperties: {
                id: 'machine:' + machine,
                label: machine,
                shape: 'circle',
                color: {
                    border: "gray",
                    background: palette.next(),
                    highlight: {
                        border: "darkgray",
                        background: palette.current('dark'),
                    }
                },
                _machine: machine
            }
        };
        window._architecture_graph.network.cluster(clusterOptionsByData);
    }
}