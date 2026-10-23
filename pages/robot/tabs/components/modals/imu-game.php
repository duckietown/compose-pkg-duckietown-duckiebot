<?php
use system\classes\Core;
?>

<!-- Modal -->
<div class="modal fade top-view-modal" id="modal_IMU" role="dialog">
    <div class="modal-dialog modal-lg">

    <!-- Modal content-->
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">Duckiebot IMU Game</h4>
        </div>
        <div class="modal-body">
            <ul>
                <li>Hold your Duckiebot and move it, and verify that the plane movements correspond to your Duckiebot.</li>
                <li>If the plane does not move, there is a problem.</li>
                <li>Once you're satisfied, you can close this window and resume back to the verification results reporting.</li>
            </ul>
            
            <button id="connect_to_duckiebot" class="btn cursor-pointer"></button>
            <div id="imu-game" style="padding-top: 10px">
                <button id="reset" class="float-right btn btn-danger btn-sm" style="bottom: 40px">
                    Restart
                </button>
            </div>
        </div>
    </div>
    
    </div>
</div>

<script src="<?php echo Core::getJSscriptURL('duckiebot_imu_game_lib_oimo.js', 'duckietown_duckiebot'); ?>"></script>
<script src="<?php echo Core::getJSscriptURL('duckiebot_imu_game_lib_three.js', 'duckietown_duckiebot'); ?>"></script>
<script src="<?php echo Core::getJSscriptURL('duckiebot_imu_game.js', 'duckietown_duckiebot'); ?>"></script>
