<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele


namespace system\packages\duckietown_duckiebot;

use \system\classes\Core;
use \system\classes\Database;
use \system\packages\data\Data;


/**
 *   Module for managing Duckiebots
 */
class Duckiebot {
    
    private static $initialized = false;
    private static $PERMISSION_LOCATION = '/data/config/permissions/%s';
    private static $CALIBRATIONS_LOCATION = '/data/config/calibrations/';
    private static $FILE_WRITERS_LOCATION = '/tmp/sockets';
    public static $PERMISSION_KEYS = [
        "allow_push_logs_data",
        "allow_push_stats_data",
        "allow_push_config_data",
    ];
    private static $CONFIGURATION_LOCATION = '/data/config/robot_%s';
    private static $CONFIGURATION_KEYS = [
        "type",
        "configuration",
        "hardware"
    ];
    private static $AUTOLAB_CFG_LOCATION = '/data/config/autolab/%s';
    private static $AUTOLAB_CFG_KEYS = [
        "tag_id",
        "map_name"
    ];
    public static $HARDWARE_TEST_RESULTS_DATABASE_NAME = "hardware_test_result";
    
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
    public static function init(): array {
        if (!self::$initialized) {
            // this is a Duckiebot, so, skip the first two steps on the first-setup
            $first_setup_db = new Database('core', 'first_setup');
            if (!$first_setup_db->key_exists('step1')) {
                $first_setup_db->write('no_admin', null);
                // enable developer mode
                $res = Core::setSetting('core', 'developer_mode', true);
                // confirm step1 and step2
                if (!$res['success']) {
                    Core::throwError($res['data']);
                }
                // mark the first two steps as completed
                $first_setup_db->write('step1', null);
                $first_setup_db->write('step2', null);
            }
            // create hardware test database
            if (!Data::exists(self::$HARDWARE_TEST_RESULTS_DATABASE_NAME)) {
                Data::new(self::$HARDWARE_TEST_RESULTS_DATABASE_NAME);
                Data::set_public_access(self::$HARDWARE_TEST_RESULTS_DATABASE_NAME);
                Data::set_guest_access(self::$HARDWARE_TEST_RESULTS_DATABASE_NAME, true, true);
            }
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
    public static function isInitialized(): bool {
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
    public static function close(): array {
        // do stuff
        return ['success' => true, 'data' => null];
    }//close
    
    
    // =======================================================================================================
    // Public functions
    
    public static function getDuckiebotName() {
        $duckiebot_name = Core::getSetting('duckiebot_name', 'duckietown_duckiebot');
        if (strlen($duckiebot_name) < 2) {
            $duckiebot_hostname = Core::getBrowserHostname();
            // remove port (if any) from the http host string
            $duckiebot_name_parts = explode(':', $duckiebot_hostname);
            $duckiebot_name = $duckiebot_name_parts[0];
            // do not consider "localhost" a valid robot name
            if (strcasecmp($duckiebot_name, "localhost") == 0)
                return null;
        }
        // remove '.local' from the end of the host string (if present)
        return preg_replace('/\.local$/', '', $duckiebot_name);
    }//getDuckiebotName
    
    public static function getRobotType() {
        $res = self::getDuckiebotConfiguration('type');
        if (!$res['success']) return null;
        return $res['data'];
    }//getRobotType
    
    public static function getRobotConfiguration() {
        $res = self::getDuckiebotConfiguration('configuration');
        if (!$res['success']) return null;
        return $res['data'];
    }//getRobotConfiguration
    
    public static function getDuckiebotHostname(): string {
        $duckiebot_name = Core::getSetting('duckiebot_hostname', 'duckietown_duckiebot');
        if (strlen($duckiebot_name) >= 2){
            return $duckiebot_name;
        }
        // revert to http host if no vehicle name is set
        $duckiebot_name = self::getDuckiebotName();
        // revert to http host if no vehicle name is set
        if (is_null($duckiebot_name) || strlen($duckiebot_name) < 2) {
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
    
    public static function canSetDuckiebotHostname() {
        $sockets_dir = self::$FILE_WRITERS_LOCATION;
        $hostname_socket_fname = "$sockets_dir/etc/hostname.sock";
        return file_exists($hostname_socket_fname);
    }//canSetDuckiebotHostname
    
    public static function setDuckiebotHostname($hostname): array {
        $sockets_dir = self::$FILE_WRITERS_LOCATION;
        $hostname_socket_fname = "$sockets_dir/etc/hostname.sock";
        if (file_exists($hostname_socket_fname)) {
            $socket = fsockopen("unix://$hostname_socket_fname");
            // sanitize hostname
            $hostname = preg_replace('/[^a-z0-9-]/', '', $hostname);
            $hostname = trim($hostname, "-");
            // write hostname to socket
            fwrite($socket, $hostname);
            fclose($socket);
            return ['success' => true, 'data' => null];
        }
        return ['success' => false, 'data' => "Socket file '$hostname_socket_fname' not found"];
    }//setDuckiebotHostname
    
    public static function getDuckiebotPermission($key): array {
        if (!in_array($key, self::$PERMISSION_KEYS))
            return ['success' => false, 'data' => "Permission key `$key` not recognized."];
        $fpath = sprintf(self::$PERMISSION_LOCATION, $key);
        return self::readFileFromDisk($fpath);
    }//getDuckiebotPermission
    
    public static function getDuckiebotConfiguration($key): array {
        if (!in_array($key, self::$CONFIGURATION_KEYS))
            return ['success' => false, 'data' => "Configuration key `$key` not recognized."];
        $fpath = sprintf(self::$CONFIGURATION_LOCATION, $key);
        $res = self::readFileFromDisk($fpath);
        if ($res['success'])
            $res['data'] = trim($res['data']);
        return $res;
    }//getDuckiebotConfiguration
    
    public static function getAutolabConfiguration($key): array {
        if (!in_array($key, self::$AUTOLAB_CFG_KEYS))
            return ['success' => false, 'data' => "Autolab setting key `$key` not recognized."];
        $fpath = sprintf(self::$AUTOLAB_CFG_LOCATION, $key);
        return self::readFileFromDisk($fpath);
    }//getAutolabConfiguration
    
    public static function setDuckiebotPermission($key, $value): array {
        if (!in_array($key, self::$PERMISSION_KEYS))
            return ['success' => false, 'data' => "Permission key `$key` not recognized."];
        $fpath = sprintf(self::$PERMISSION_LOCATION, $key);
        return self::writeFileToDisk($fpath, is_bool($value)? +$value : trim($value));
    }//setDuckiebotPermission
    
    public static function setDuckiebotConfiguration($key, $value): array {
        if (!in_array($key, self::$CONFIGURATION_KEYS))
            return ['success' => false, 'data' => "Configuration key `$key` not recognized."];
        $fpath = sprintf(self::$CONFIGURATION_LOCATION, $key);
        return self::writeFileToDisk($fpath, trim($value));
    }//setDuckiebotConfiguration
    
    public static function setAutolabConfiguration($key, $value): array {
        if (!in_array($key, self::$AUTOLAB_CFG_KEYS))
            return ['success' => false, 'data' => "Autolab setting key `$key` not recognized."];
        $fpath = sprintf(self::$AUTOLAB_CFG_LOCATION, $key);
        return self::writeFileToDisk($fpath, trim($value));
    }//setAutolabConfiguration
    
    public static function getDuckiebotPermissions(): array {
        $out = [
            "success" => true,
            "data" => []
        ];
        foreach (self::$PERMISSION_KEYS as $key) {
            $res = self::getDuckiebotPermission($key);
            if (!$res['success']) {
                return $res;
            }
            $out['data'][$key] = $res['data'];
        }
        return $out;
    }//getDuckiebotPermissions
    
    public static function getDuckiebotConfigurations(): array {
        $out = [
            "success" => true,
            "data" => []
        ];
        foreach (self::$CONFIGURATION_KEYS as $key) {
            $res = self::getDuckiebotConfiguration($key);
            if (!$res['success']) {
                return $res;
            }
            $out['data'][$key] = $res['data'];
        }
        return $out;
    }//getDuckiebotConfigurations
    
    public static function getAutolabConfigurations(): array {
        $out = [
            "success" => true,
            "data" => []
        ];
        foreach (self::$AUTOLAB_CFG_KEYS as $key) {
            $res = self::getAutolabConfiguration($key);
            if (!$res['success']) {
                return $res;
            }
            $out['data'][$key] = $res['data'];
        }
        return $out;
    }//getAutolabConfigurations
    
    public static function getCalibrationContent($calib_type): array {
        $robot_name = self::getDuckiebotName();
        $calib_filename = sprintf("%s.yaml", $robot_name);
        $calib_filepath = join_path(self::$CALIBRATIONS_LOCATION, $calib_type, $calib_filename);
        return self::readFileFromDisk($calib_filepath);
    }//readFileFromDisk
    
    public static function readFileFromDisk($fpath): array {
        if (!file_exists($fpath)) {
            return ['success' => false, 'data' => "File `$fpath` does not exist."];
        }
        if (!is_readable($fpath)) {
            return ['success' => false, 'data' => "File `$fpath` cannot be read."];
        }
        $res = file_get_contents($fpath);
        if ($res === false)
            return [
                'success' => false,
                'data' => "An error occurred while reading the file `$fpath`."
            ];
        return ['success' => true, 'data' => $res];
    }//readFileFromDisk
    
    public static function writeFileToDisk($fpath, $content): array {
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
    
    
    // =======================================================================================================
    // Private functions
    
    // YOUR PRIVATE METHODS HERE
    
}//Duckiebot



class FilesAPI {
    
    /**
     * @var string
     */
    private $url;
    
    public function __construct($hostname = null) {
        $files_api_hostname = $hostname;
        if (is_null($hostname)) {
            $files_api_hostname = Core::getSetting('files_api/hostname', 'duckietown_duckiebot');
        }
        // revert to duckiebot hostname (or eventually to http host) if no vehicle name is set
        if (strlen($files_api_hostname) < 2) {
            $files_api_hostname = Duckiebot::getDuckiebotHostname();
        }
        // prepare url
        $this->url = sprintf('http://%s/files/', $files_api_hostname);
    }
    
    public function get($fpath, $format=null): array {
        $qs = [];
        if (!is_null($format))
            $qs['format'] = $format;
        $url = self::url('data', $fpath, $qs);
        // fetch data from robot
        $data = file_get_contents($url);
        if ($data === false) {
            $error = error_get_last();
            return ['success' => false, 'data' => $error['message']];
        } else {
            return ['success' => true, 'data' => $data];
        }
    }
    
    public function post($fpath, $content, $format=null): array {
        $qs = [];
        if (!is_null($format))
            $qs['format'] = $format;
        $url = self::url('data', $fpath, $qs);
        // send data to robot
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        $res = curl_exec($ch);
        curl_close($ch);
        // check if we got anything
        if ($res === false) {
            $ch_info = curl_getinfo($ch);
            return [
                'success' => false,
                'data' => sprintf('The Files API returned the code `%s`', $ch_info['http_code'])
            ];
        }
        // looks like we got something
        return ['success' => true, 'data' => $res];
    }
    
    private function url($action, $resource, $qs = []): string {
        return join_path($this->url, $action, $resource, toQueryString(array_keys($qs), $qs, true));
    }
    
}
?>
