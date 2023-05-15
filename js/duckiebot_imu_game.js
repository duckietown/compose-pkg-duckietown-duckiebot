var duckiebot_imu_funcs = {};

$(function () {
    // create scene
    const scene = new THREE.Scene();
    scene.add(new THREE.AmbientLight(0x3D4143, 0.1));

    const container_w = 868;
    const container_h = 600;

    // create camera
    const camera = new THREE.PerspectiveCamera(
        75,
        container_w / container_h,
        0.1,
        1000
    );
    camera.position.z = 5;

    // create renderer
    const renderer = new THREE.WebGLRenderer();
    renderer.setSize(
        container_w,
        container_h
    );
    $('#IMU-GAME').append(renderer.domElement);

    // physical world
    var world; 

    // should the main loop update
    var update_world = false;

    // ROS topic
    var listener;

    // rigidbodies in the world
    var ground;
    var ball;

    function init() {
        world = new OIMO.World({
            info: true,
            worldscale: 1,
            timestep: 1 / 60,
            gravity: [0, -9.8, 0],
        });

        let _box_dim = [5, 0.01, 5];
        ground = world.add({
            size: _box_dim,
            pos: [0, -1, 0],
            rot: [0, 90, 0],
            world: world,
            // move: true,
            kinematic: true,
            // density: 1,
        });

        let _mesh = new THREE.Mesh(
            new THREE.BoxGeometry(
                _box_dim[0],
                _box_dim[1],
                _box_dim[2],
                20, 20, 20
            ),
            new THREE.MeshBasicMaterial({
                color: 0x54b4e8,
                wireframe: true,
            }),
        );
        scene.add(_mesh);
        ground.connectMesh(_mesh);

        let _radius = 0.2;
        ball = world.add({
            type: "sphere",
            size: [_radius],
            pos: [0, 3, 0],
            world: world,
            move: true,
        })

        _mesh = new THREE.Mesh(
            new THREE.SphereGeometry(_radius, 20, 20),
            new THREE.MeshBasicMaterial({
                color: 0xedab4e,
                wireframe: true,
            }),
        )
        scene.add(_mesh);
        ball.connectMesh(_mesh);

        update_world = true;
        is_connected_to_robot = false;
    }

    var xs, ys, sum_x, sum_y;
    const average_filter_window_size = 10;
    // hard-coded ranges
    const max_x = 10;
    const max_y = 10;

    var is_connected_to_robot = false;

    function ros_setup(
        ros,  // ROSLIBJS obj
        robot_name,
        ros_topic_name,
    ) {
        let _robot_hostname = robot_name;

        survival_time_sec = 0;
        deinit();
        init();

        listener = new ROSLIB.Topic({
            ros: ros,
            name: ros_topic_name,
            messageType: 'sensor_msgs/Imu',
        });

        xs = [];
        ys = [];
        sum_x = sum_y = 0.0;

        listener.subscribe(function (message) {
            if (!is_connected_to_robot) {
                $("#connect_to_duckiebot")
                    .prop("disabled", true)
                    .text("Connected to " + _robot_hostname)
                    .removeClass("pointer")
                    .removeClass("btn-hover")
                    .removeClass("btn")
                    .addClass("btn-connected");
                is_connected_to_robot = true;
            }
            // console.log(message)
            let lin_acc = message.linear_acceleration;
            // console.log("x: " + lin_acc.x);
            let x = lin_acc.x;
            let y = lin_acc.y;
            xs.push(x);
            ys.push(y);
            sum_x += x;
            sum_y += y;

            // remove first element if longer than filter window size 
            if (xs.length > average_filter_window_size) {
                sum_x -= xs[0];
                xs.shift();
            }
            if (ys.length > average_filter_window_size) {
                sum_y -= ys[0];
                ys.shift();
            }

            ground.setQuaternion(ground_angle(
                sum_x / xs.length,
                sum_y / ys.length
            ));

        });
    }

    function ros_cleanup() {
        listener.unsubscribe();
    }

    var survival_time_sec = -1;
    var best_survival_time_sec = 0;
    setInterval(() => {
        if (ros != null) {
            if (update_world) {
                survival_time_sec += 1;
                $("#score").text(survival_time_sec + "s - Survival Time");
            } else {
                survival_time_sec = -1;
                $("#score").text("");
            }
        }
    }, 1000);

    // link button actions
    $("#reset").on("click", () => {
        deinit();
        init();
    });
    $("button").hover(
        (e) => {
            $("#" + e.target.id).addClass("btn-hover");
            $("#" + e.target.id).removeClass("btn");
        },
        (e) => {
            $("#" + e.target.id).removeClass("btn-hover");
            $("#" + e.target.id).addClass("btn");
        }
    );

    const xyz2rad = function (val, max_val) {
        return -(Math.PI * (val / max_val)) / 2;
    }

    const ground_angle = function (x, y) {
        let _q = new THREE.Quaternion();
        _q.setFromEuler(new THREE.Euler(
            xyz2rad(y, max_y),
            0,
            xyz2rad(-x, max_x),
            "XYZ",
        ));
        return new OIMO.Quat(_q.x, _q.y, _q.z, _q.w);
    }

    // remove meshes from scene and rigidbodies from the world
    const deinit = function () {
        scene.remove(ground.mesh);
        scene.remove(ball.mesh);

        ground.remove();
        ball.remove();

        xs = [];
        ys = [];
        sum_x = 0.0;
        sum_y = 0.0;

        survival_time_sec = -1;

        update_world = false;
    }

    // MAIN loop
    const loop = function () {
        // // Change framerate
        // setTimeout(() => {
        //     requestAnimationFrame(loop);
        // }, 1000 / 30);
        requestAnimationFrame(loop);

        // advance the physical world
        world.step()
        // the meshes are bound to rigidbodies, so also re-positioned

        // // Display stats
        $("#info").html(world.getInfo());

        renderer.render(scene, camera);
        
        let _bpos = ball.getPosition();
        if (
            world.numContacts == 0 && (
                Math.abs(_bpos.x) > 5 || 
                Math.abs(_bpos.z) > 5 ||
                _bpos.y < -3.5
            )
        ) {
            update_world = false;
            if (survival_time_sec > best_survival_time_sec) {
                best_survival_time_sec = survival_time_sec;
                $("#best_score").text(best_survival_time_sec + "s - Best Survival Time");
            }
        }
    }

    duckiebot_imu_funcs.ros_setup = ros_setup;
    duckiebot_imu_funcs.ros_cleanup = ros_cleanup;

    init();
    loop();
});