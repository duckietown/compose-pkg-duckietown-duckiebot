<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele


namespace system\packages\duckietown_duckiebot;

use \system\classes\Core;


/**
 *   Module for managing Duckiebots
 */
class Duckiebot {
    
    private static $initialized = false;
    private static $files_api = null;
    private static $PERMISSION_LOCATION = 'config/permissions/%s';
    private static $PERMISSION_KEYS = [
        "allow_push_logs_data",
        "allow_push_stats_data",
        "allow_push_config_data",
    ];
    private static $CONFIGURATION_LOCATION = 'config/robot_%s';
    private static $CONFIGURATION_KEYS = [
        "type",
        "configuration",
        "hardware"
    ];
    private static $AUTOLAB_CFG_LOCATION = 'config/autolab/%s';
    private static $AUTOLAB_CFG_KEYS = [
        "tag_id",
        "map_name"
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
    public static function init(): array {
        if (!self::$initialized) {
            self::$files_api = new FilesAPI();
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
    
    public static function getDuckiebotName(): string {
        $duckiebot_hostname = self::getDuckiebotHostname();
        // remove '.local' from the end of the host string (if present)
        return preg_replace('/\.local$/', '', $duckiebot_hostname);
    }//getDuckiebotName
    
    public static function getDuckiebotType(): string {
        return self::$files_api->get('/config/robot_type');
    }//getDuckiebotType
    
    public static function getDuckiebotHostname(): string {
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
    
    public static function getDuckiebotPermission($key): array {
        if (!in_array($key, self::$PERMISSION_KEYS))
            return ['success' => false, 'data' => "Permission key `$key` not recognized."];
        $fpath = sprintf(self::$PERMISSION_LOCATION, $key);
        return self::$files_api->get($fpath);
    }//getDuckiebotPermission
    
    public static function getDuckiebotConfiguration($key): array {
        if (!in_array($key, self::$CONFIGURATION_KEYS))
            return ['success' => false, 'data' => "Configuration key `$key` not recognized."];
        $fpath = sprintf(self::$CONFIGURATION_LOCATION, $key);
        return self::$files_api->get($fpath);
    }//getDuckiebotConfiguration
    
    public static function getAutolabConfiguration($key): array {
        if (!in_array($key, self::$AUTOLAB_CFG_KEYS))
            return ['success' => false, 'data' => "Autolab setting key `$key` not recognized."];
        $fpath = sprintf(self::$AUTOLAB_CFG_LOCATION, $key);
        return self::$files_api->get($fpath);
    }//getAutolabConfiguration
    
    public static function setDuckiebotPermission($key, $value): array {
        if (!in_array($key, self::$PERMISSION_KEYS))
            return ['success' => false, 'data' => "Permission key `$key` not recognized."];
        $fpath = sprintf(self::$PERMISSION_LOCATION, $key);
        return self::$files_api->post($fpath, is_bool($value)? +$value : trim($value));
    }//setDuckiebotPermission
    
    public static function setDuckiebotConfiguration($key, $value): array {
        if (!in_array($key, self::$CONFIGURATION_KEYS))
            return ['success' => false, 'data' => "Configuration key `$key` not recognized."];
        $fpath = sprintf(self::$CONFIGURATION_LOCATION, $key);
        return self::$files_api->post($fpath, trim($value));
    }//setDuckiebotConfiguration
    
    public static function setAutolabConfiguration($key, $value): array {
        if (!in_array($key, self::$AUTOLAB_CFG_KEYS))
            return ['success' => false, 'data' => "Autolab setting key `$key` not recognized."];
        $fpath = sprintf(self::$AUTOLAB_CFG_LOCATION, $key);
        return self::$files_api->post($fpath, trim($value));
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
    
    
    // =======================================================================================================
    // Private functions
    
    // YOUR PRIVATE METHODS HERE
    
    private static function writeFileToDisk($fpath, $content): array {
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
