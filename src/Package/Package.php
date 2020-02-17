<?php

namespace WP_CLI_PACKAGIST\Package;

/**
 * WordPress Package System.
 *
 * @author  Mehrshad Darzi <mehrshad198@gmail.com>
 * @since   1.0.0
 */
class Package
{
    /**
     * Get Wordpress Package options
     *
     * @var string
     */
    public $package_config;

    /**
     * Package Config Path
     *
     * @var string
     */
    public $package_path;

    /**
     * Primary Keys in WordPress Package
     *
     * @var array
     */
    public $primary_keys = array('core', 'config', 'mysql');

    /**
     * Package constructor.
     */
    public function __construct()
    {
        /**
         * Set global Package config
         */
        $this->package_config = self::get_config('package');
        /**
         * Get Full path of Wordpress package File
         */
        $this->package_path = \WP_CLI_FileSystem::path_join(\WP_CLI_Util::getcwd(), $this->package_config['file']);
    }

    /**
     * Check exist wordpress package file
     */
    public function exist_package_file()
    {
        if (file_exists($this->package_path)) {
            return true;
        }

        return false;
    }

    /**
     * Remove Package File
     */
    public function remove_package_file()
    {
        if (file_exists($this->package_path)) {
            \WP_CLI_FileSystem::remove_file($this->package_path);
        }
    }

    /**
     * Read WordPress Package Data
     */
    public function get_package_data()
    {
        //Check Exist Package
        if ($this->exist_package_file() === false) {
            return array('status' => false, 'data' => Package::_e('package', 'not_exist_pkg'));
        }

        //Read Json file
        $json_data = \WP_CLI_FileSystem::read_json_file($this->package_path);
        if ($json_data === false) {
            return array('status' => false, 'data' => Package::_e('package', 'er_pkg_syntax'));
        }

        return array('status' => true, 'data' => $json_data);
    }

    /**
     * Set Global Setting for Check params in Running Package
     */
    public function set_global_package_run_check()
    {
        //Active Run Check MYSQL
        define('WP_CLI_PACKAGIST_RUN_CHECK_MYSQL', true);

        //Active Run Check WebSite Url
        define('WP_CLI_PACKAGIST_RUN_CHECK_SITE_URL', true);

        //Active Run Check Custom Url exist
        define('WP_CLI_PACKAGIST_RUN_EXIST_CUSTOM_URL', true);
    }

    /**
     * Get Config
     *
     * @return mixed
     */
    public static function get_config()
    {
        // Load All Config
        $list = include WP_CLI_PACKAGIST_PATH . '/config/package.php';

        // Check arg
        $numArgs = func_num_args();
        if ($numArgs == 1) {
            if (array_key_exists(func_get_arg(0), $list)) {
                return $list[func_get_arg(0)];
            }
        } else {
            $exist_key = \WP_CLI_Util::check_exist_key(func_get_args(), $list);
            if ($exist_key != false) {
                $exp = $list;
                foreach (func_get_args() as $key) {
                    $exp = $exp[$key];
                }
                return $exp;
            }
        }

        return false;
    }

    /**
     * Get log Text
     *
     * @param $section
     * @param $key
     * @param array $replace
     * @return string
     */
    public static function _e($section, $key, $replace = array())
    {
        // Get Config
        $config = Package::get_config($section);

        //Check Exist Text Log
        $log = null;
        if (isset($config['log'][$key])) {
            $log = $config['log'][$key];
        }

        //Check replace
        if ( ! is_null($log) and ! empty($replace)) {
            $log = str_ireplace(array_keys($replace), array_values($replace), $log);
        }

        return $log;
    }

    /**
     * get before command which run in WP-CLI PACKAGIST
     *
     * @return array
     */
    public static function get_command_log()
    {
        //Command log file name
        $file = Package::get_config('command_log');
        if (file_exists($file)) {
            //Check time age cache [ 1 minute ]
            if (time() - filemtime($file) >= 120) {
                self::remove_command_log();
            } else {
                //get json parse
                $json = \WP_CLI_FileSystem::read_json_file($file);
                if ($json != false) {
                    return $json;
                }
            }
        }

        return array();
    }

    /**
     * Save last run command
     *
     * @param $command
     * @param $args
     * @param $assoc_args
     */
    public static function save_last_command($command, $args, $assoc_args)
    {
        //Command log file name
        $file = Package::get_config('command_log');

        //Get now Command
        $now = array(
            'command'    => $command,
            'args'       => $args,
            'assoc_args' => $assoc_args
        );

        //Add new Command to Log
        \WP_CLI_FileSystem::create_json_file($file, $now);
    }

    /**
     * Complete remove command log
     */
    public static function remove_command_log()
    {
        //Command log file name
        $file = Package::get_config('command_log');
        if (file_exists($file)) {
            \WP_CLI_FileSystem::remove_file($file);
        }
    }
}