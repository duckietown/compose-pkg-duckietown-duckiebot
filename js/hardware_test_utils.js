function json_to_html(json_obj) {
    let outHtml = "";
    let blocks = json_obj.parameters
    
    for (let idx = 0; idx < blocks.length; idx++) {
        let block = blocks.at(idx);

        // handle different types of contents
        let tmp_title = "<h4>" + block.key + "</h4>";
        let tmp = "";
        switch (block.type) {
            case "html":
                tmp = block.value;
                break;
            case "base64":
                // Parse Base64 string to binary data
                const binaryString = atob(block.value);
                // Create blob from binary data
                const blob = new Blob([new Uint8Array([...binaryString].map(char => char.charCodeAt(0)))]);
                // Create object URL from blob
                const url = URL.createObjectURL(blob);
                // Create image element and set source to object URL
                const img = document.createElement('img');
                img.src = url;

                // downsize the image shown, to make the modal appear fully without scrolling
                // original size: 640 x 480
                img.width = 320;
                img.height = 240;

                tmp = img.outerHTML;
                break;
            case "string":
                tmp = "<p>" + block.value + "</p>";
                break;
            default:
                tmp_title = "";
        }
        // get section title
        outHtml += tmp_title;
        outHtml += tmp;
    }
    
    return outHtml
}

function stream_data(
    ros,  // ROSLIBJS object
    robot_name,  // robot hostname, e.g. "autobot33"
    test_topic_name,  // the rest of topic name, without '/' at the front. E.g. the topic is "/db01/camera_node/image/compressed", then this is "camera_node/image/compressed"
    test_topic_type, // message type
    update_id,  // the HTML <div> component to be updated
    modal_id,  // on closing this test modal, the listener would unsubscribe
) {
    let topic_name = '/' + robot_name + '/' + test_topic_name;
    // console.log("Listening to topic: " + topic_name);
    // console.log("Updating text: ", update_id);

    // special handling for the IMU game
    if (test_topic_type === "sensor_msgs/Imu") {
        duckiebot_imu_funcs.ros_setup(ros, robot_name, topic_name);
        $('#modal_IMU').modal('show');
        $("#modal_IMU").on("hidden.bs.modal", function(event) {
            duckiebot_imu_funcs.ros_cleanup();
        });
        return;
    }
    
    // other live data
    let listener = new ROSLIB.Topic({
        ros: ros,
        name: topic_name,
        messageType: test_topic_type,
    });

    listener.subscribe(function (message) {
        // console.log(message);

        // handle different rendering of different message types here
        let outHtml = "<h4>Live Data</h4>";
        switch(test_topic_type) {
            case "duckietown_msgs/WheelEncoderStamped":
                outHtml += "<p><strong>Tick value: </strong>" + message.data + "</p>";
                break;
            case "sensor_msgs/Range":
                let range = message.range;
                if (range >= message.min_range && range <= message.max_range) {
                    let range_cm = (range * 100.0).toFixed(1);
                    outHtml += "<p><strong>Range: </strong>" + range_cm + " &nbsp;cm</p>";
                } else {
                    outHtml += "<p style='color: red;'>Out of range</p>"
                }
                break;
            case "sensor_msgs/CompressedImage":
                var imageUrl = "data:image/jpg;base64," + message.data;
                // downsized the image shown, to make the modal appear fully without scrolling
                // 640 x 480 -> 320 x 240
                outHtml += "<img src='" + imageUrl + "' alt='Live stream' width='320' height='240'/>";
                break;
            default:
                outHtml += ("<p>Error: Test topic type ["+ test_topic_type + "] is not supported! It has to be handled properly in the dashboard code.</p>");
        }

        $('#' + update_id).html(outHtml);
    });

    // on modal close, unsubscribe
    $("#" + modal_id).on("hidden.bs.modal", function(event) {
        listener.unsubscribe();
    });
}

function extract_stream_topic_from_json(json_obj) {
    let blocks = json_obj.parameters

    let ret = {};
    
    for (let idx = 0; idx < blocks.length; idx++) {
        let block = blocks.at(idx);
        if (block.key === "test_topic_name") {
            ret["test_topic_name"] = block.value;
        } else if (block.key === "test_topic_type") {
            ret["test_topic_type"] = block.value;
        }
    }
    
    return ret
}