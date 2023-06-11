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

    // special handling for the IMU game
    if (test_topic_type === "sensor_msgs/Imu") {
        duckiebot_imu_funcs.ros_setup(ros, robot_name, topic_name);
        $('#modal_IMU').modal('show');
        $("#modal_IMU").on("hidden.bs.modal", function (event) {
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
        // handle different rendering of different message types here
        let outHtml = "<h4>Live Data</h4>";
        switch (test_topic_type) {
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
                let imageUrl = "data:image/jpg;base64," + message.data;
                // downsized the image shown, to make the modal appear fully without scrolling
                // 640 x 480 -> 320 x 240
                outHtml += "<img src='" + imageUrl + "' alt='Live stream' width='320' height='240'/>";
                break;
            default:
                outHtml += ("<p>Error: Test topic type [" + test_topic_type + "] is not supported! It has to be handled properly in the dashboard code.</p>");
        }

        $('#' + update_id).html(outHtml);
    });

    // on modal close, unsubscribe
    $("#" + modal_id).on("hidden.bs.modal", function (event) {
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

function download_file_via_link(url_link, file_name_suggest = "") {
    // the file_name_suggest is not used, if the file name is provided by the server

    // trigger click of the donwload link
    let tmp_download_elem = $('<a>', {
        href: url_link,
        style: 'display: none;',
        download: file_name_suggest,
    });
    $('body').append(tmp_download_elem);
    tmp_download_elem[0].click();
    // clean up
    tmp_download_elem.remove();
}

function download_ros_node_logs(robot_name, node_name) {
    let download_url = `http://${robot_name}.local/duckiebot/ros/logs/download/${node_name}`;
    download_file_via_link(download_url);
    console.log("Fetched logs for ROS Node:", node_name);
}

function create_view_list_docker_containers(robot_name, modal_id = 'modal-docker-container-logs-label') {
    let api_url = `http://${robot_name}.local/code/container/list`;
    $.ajax({
        url: api_url,
        method: 'GET',
        success: function(response) {
            let list_containers = response.data.containers;
            list_containers.sort();

            // Create the selction modal
            let $modal = $('<div>', {
                'class': 'modal fade',
                'id': 'modal-docker-container-logs',
                'tabindex': '-1',
                'role': 'dialog',
                'aria-labelledby': modal_id,
                'aria-hidden': 'true'
            });
            
            // Create the modal dialog
            let $modalDialog = $('<div>', {
                'class': 'modal-dialog',
                'role': 'document'
            });
            
            // Create the modal content
            let $modalContent = $('<div>', {
                'class': 'modal-content'
            });
            
            // Create the modal header
            let $modalHeader = $('<div>', {
                'class': 'modal-header'
            }).append($('<h4>', {
                'class': 'modal-title',
                'id': 'modal-docker-container-logs-label',
                'text': 'Download Docker container logs for:'
            }));
            
            // Create the modal body
            let $modalBody = $('<div>', {
                'class': 'modal-body',
            });

            
            let $column = $('<div>', {'class': 'col-sm-12'});
            // $modalBody.append($column);

            $.each(list_containers, function(index, value) {
                let $row = $('<div>', {'class': 'row'});
                let $button = $('<button>', {
                    'type': 'button',
                    'class': 'btn btn-primary',
                    'text': value,
                    'click': function() {
                        download_docker_container_logs(robot_name, value);
                    }
                }).css({
                    "width": "60%",
                    // top, right, bottom, left
                    "margin": "10px 10px 0px 10px",
                    "text-align": "left",
                });
                $row.append($button);
                $modalBody.append($row)
            });

            // Create the modal footer
            let $modalFooter = $('<div>', {
                'class': 'modal-footer'
            }).append($('<button>', {
                'type': 'button',
                'class': 'btn btn-secondary',
                'data-dismiss': 'modal',
                'text': 'Close'
            }));
            
            // Append modal components in the correct hierarchy
            $modalContent.append($modalHeader, $modalBody, $modalFooter);
            $modalDialog.append($modalContent);
            $modal.append($modalDialog);
            
            // Append the modal to the document body
            $('body').append($modal);
            
        },
        error: function(xhr, status, error) {
            console.error('Request failed:', status, error);
        }
    });

}

function download_docker_container_logs(robot_name, container_name) {
    let api_url = `http://${robot_name}.local/code/container/logs/${container_name}`;
    $.ajax({
        url: api_url,
        method: 'GET',
        success: function(response) {
            // Create a Blob object from the response string
            let blob = new Blob([response.data.logs], { type: 'text/plain' });
            // a temporary URL for the Blob object
            let url = URL.createObjectURL(blob);

            let now = new Date();
            let timestamp = now.toISOString().replace(/[-:.Z]/g, '').slice(0, -3);
            download_file_via_link(url, `${robot_name}_docker_container_${container_name}_TS${timestamp}.log`);
            console.log("Fetched logs for docker container:", container_name);
        },
        error: function(xhr, status, error) {
            console.error('Request failed:', status, error);
        }
    });
}

function parse_db_record_response(response, test_id) {
    // parse formatted record string, and return
    // [null, null] if illy formatted, otherwise
    // [datetime: str, passed: bool]

    let datetime = null;
    let passed = null;

    let separator = `___${test_id}___`;  // see the php functions in components/index.php
    let lst_resp = response.split(separator);
    if (lst_resp.length == 4) {
        // contains exactly the special separator 3 times
        if (lst_resp[1] == "PASSED") {
            passed = true;
            datetime = lst_resp[2];
        } else if (lst_resp[1] == "FAILED") {
            passed = false;
            datetime = lst_resp[2];
        }
    }

    return [datetime, passed];
}

function update_style_based_on_records(id_str_name, datetime, passed) {
    // based on the last "passing" status of the test, the buttons are colored

    let modal_btn_id = 'modal-btn-' + id_str_name;
    let record_id = 'record-' + id_str_name;

    let disp_txt = "None"
    if (datetime != null && passed != null) {
        if (passed) {
            $('#' + modal_btn_id).removeClass().addClass("btn btn-success");
            disp_txt = `Success at ${datetime}`
        } else {
            $('#' + modal_btn_id).removeClass().addClass("btn btn-warning");
            disp_txt = `Problem since ${datetime}`
        }
    } else {
        $('#' + modal_btn_id).removeClass().addClass("btn btn-info");
    }
    $('#' + record_id).html(`Last status: ${disp_txt}`);
}


function create_hardware_test_event_file(robot_name, test_id, passed, notes = "") {
    // the file is created with dt-files-api
    // the file format complies with dt-commons/dt_staticstics_utils
    // the created file will be read by dt-device-online and uploaded/backed-up, if the user permits

    const EVENTS_DIR = "/data/stats/events";
    const FILES_API_URL_BASE = `http://${robot_name}.local/files`;

    // timestamp as int, in nanoseconds
    let stamp = parseInt((performance.timeOrigin + performance.now()) * (10 ** 6));
    let url = `${FILES_API_URL_BASE}${EVENTS_DIR}/${stamp}.json`;

    let obj = {
        "type": `hardware/test/${test_id}`,
        "stamp": stamp,
        "data": {
            "passed": passed,
            "notes": notes,
        },
    };
    let raw_data_str = JSON.stringify(obj) + "\n";

    $.ajax({
        url: url,
        type: "POST",
        data: raw_data_str,
        contentType: false, // to prevent automatic content-type header
        processData: false, // to prevent automatic data processing
        error: function(xhr, status, error) {
            console.error('Request failed:', status, error);
        }
    });
}