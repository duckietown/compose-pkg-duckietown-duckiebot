<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele


namespace system\packages\duckietown_duckiebot;

use \system\classes\Core;
use \system\classes\Utils;
use \system\classes\Database;
use \system\classes\Configuration;

/**
 *   Module for managing Duckiebots
 */
class Duckiebot {
    
    private static $initialized = false;
    private static $PERMISSIONS_KEYS = [
        "allow_push_logs_data",
        "allow_push_stats_data",
        "allow_push_config_data",
    ];
    private static $PERMISSION_FILENAME = '/data/config/permissions/%s';
    private static $SETTING_FILENAME = [
        "type" => "/data/config/robot_type",
        "configuration" => "/data/config/robot_configuration",
        "hardware" => "/data/config/robot_hardware",
        "autolab/tag_id" => "/data/config/autolab/tag_id",
        "autolab/map_name" => "/data/config/autolab/map_name"
    ];
    
    // disable the constructor
    private function __construct() {
    }
    
    /** Initializes the module.
     *
     * @retval array
     *        a status array of the form
     *    <pre><code class="php">[
     *        "success" => boolean,    // whether the function succeded
     *        "data" => mixed        // error message or NULL
     *    ]</code></pre>
     *        where, the `success` field indicates whether the function succeded.
     *        The `data` field contains errors when `success` is `FALSE`.
     */
    public static function init() {
        if (!self::$initialized) {
            // do stuff
            //
            self::$initialized = true;
            return ['success' => true, 'data' => null];
        } else {
            return ['success' => true, 'data' => "Module already initialized!"];
        }
    }//init
    
    /** Returns whether the module is initialized.
     *
     * @retval boolean
     *        whether the module is initialized.
     */
    public static function isInitialized() {
        return self::$initialized;
    }//isInitialized
    
    /** Safely terminates the module.
     *
     * @retval array
     *        a status array of the form
     *    <pre><code class="php">[
     *        "success" => boolean,    // whether the function succeded
     *        "data" => mixed        // error message or NULL
     *    ]</code></pre>
     *        where, the `success` field indicates whether the function succeded.
     *        The `data` field contains errors when `success` is `FALSE`.
     */
    public static function close() {
        // do stuff
        return ['success' => true, 'data' => null];
    }//close
    
    
    // =======================================================================================================
    // Public functions
    
    public static function getDuckiebotName() {
        $duckiebot_hostname = self::getDuckiebotHostname();
        // remove '.local' from the end of the host string (if present)
        $duckiebot_name = preg_replace('/\.local$/', '', $duckiebot_hostname);
        // ---
        return $duckiebot_name;
    }//getDuckiebotName
    
    public static function getDuckiebotType($duckiebot_hostname) {
        return self::getFileFromRobot('/config/robot_type', $duckiebot_hostname);
    }//getDuckiebotType
    
    public static function getDuckiebotHostname() {
        $duckiebot_name = Core::getSetting('duckiebot_name', 'duckietown_duckiebot');
        // revert to http host if no vehicle name is set
        if (strlen($duckiebot_name) < 2) {
            $duckiebot_hostname = Core::getBrowserHostname();
            // remove port (if any) from the http host string
            $duckiebot_name_parts = explode(':', $duckiebot_hostname);
            $duckiebot_hostname = $duckiebot_name_parts[0];
        } else {
            $duckiebot_hostname = sprintf('%s.local', $duckiebot_name);
        }
        // ---
        return $duckiebot_hostname;
    }//getDuckiebotHostname
    
    public static function setDuckiebotPermission($key, $value) {
        if (!in_array($key, self::$PERMISSIONS_KEYS))
            return ['success' => false, 'data' => "Permission key `$key` not recognized."];
        $fpath = sprintf(self::$PERMISSION_FILENAME, $key);
        return self::writeFileToDisk($fpath, trim($value));
    }//setDuckiebotPermission
    
    public static function setDuckiebotConfiguration($key, $value) {
        if (!array_key_exists($key, self::$SETTING_FILENAME))
            return ['success' => false, 'data' => "Setting `$key` not recognized."];
        $fpath = self::$SETTING_FILENAME[$key];
        return self::writeFileToDisk($fpath, trim($value));
    }//setDuckiebotConfiguration
    
    
    // =======================================================================================================
    // Private functions
    
    // YOUR PRIVATE METHODS HERE
    
    
    private static function getFileFromRobot($filepath, $duckiebot_hostname = null) {
        $files_api_hostname = $duckiebot_hostname;
        if (is_null($duckiebot_hostname)) {
            $files_api_hostname = Core::getSetting('files_api_host', 'duckietown_duckiebot');
        }
        $files_api_port = Core::getSetting('files_api_port', 'duckietown_duckiebot');
        // revert to duckiebot hostname (or eventually to http host) if no vehicle name is set
        if (strlen($files_api_hostname) < 2) {
            $files_api_hostname = self::getDuckiebotHostname();
        }
        // prepare url
        $url = sprintf('http://%s:%s/%s', $files_api_hostname, $files_api_port, ltrim($filepath, '/'));
        // fetch data from robot
        if (!$data = file_get_contents($url)) {
            $error = error_get_last();
            Core::throwError(sprintf("HTTP request failed. Error was: %s", $error['message']));
        } else {
            return $data;
        }
    }//getFileFromRobot
    
    private static function writeFileToDisk($fpath, $content) {
        $fdirpath = dirname($fpath);
        if (file_exists($fdirpath) && !is_writable($fdirpath)) {
            return ['success' => false, 'data' => "Directory `$fdirpath` cannot be written."];
        }
        if (!file_exists($fdirpath)) {
            $res = mkdir($fdirpath);
            if ($res === false)
                return ['success' => false, 'data' => "Directory `$fdirpath` cannot be created."];
        }
        if (file_exists($fpath) && !is_writable($fpath)) {
            return ['success' => false, 'data' => "File `$fpath` cannot be written."];
        }
        $res = file_put_contents($fpath, $content);
        if ($res === false)
            return [
                'success' => false,
                'data' => "An error occurred while writing the file `$fpath`."
            ];
        return ['success' => true, 'data' => null];
    }//writeFileToDisk
    
}//Duckiebot
?>
