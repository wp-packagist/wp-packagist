<?php

namespace WP_CLI_PACKAGIST\Package\Params;

use WP_CLI_PACKAGIST\Package\Arguments\Admin;
use WP_CLI_PACKAGIST\Package\Arguments\Core;
use WP_CLI_PACKAGIST\Package\Arguments\Emoji;
use WP_CLI_PACKAGIST\Package\Arguments\Locale;
use WP_CLI_PACKAGIST\Package\Arguments\Permalink;
use WP_CLI_PACKAGIST\Package\Arguments\XML_RPC;
use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Arguments\Commands;
use WP_CLI_PACKAGIST\Package\Arguments\Dir;
use WP_CLI_PACKAGIST\Package\Arguments\Options;
use WP_CLI_PACKAGIST\Package\Arguments\Plugins;
use WP_CLI_PACKAGIST\Package\Arguments\Rest_API;
use WP_CLI_PACKAGIST\Package\Arguments\Security;
use WP_CLI_PACKAGIST\Package\Arguments\Themes;
use WP_CLI_PACKAGIST\Package\Arguments\Timezone;
use WP_CLI_PACKAGIST\Package\Arguments\Users;
use WP_CLI_PACKAGIST\Package\Utility\install;

class mysql
{
    /**
     * Default Parameter
     *
     * @var array
     */
    public $params_keys = array('DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME', 'DB_CHARSET', 'table_prefix', 'DB_COLLATE');

    /**
     * Get Wordpress Package options
     *
     * @var string
     */
    public $package_config;

    /**
     * Core constructor.
     */
    public function __construct()
    {
        /*
         * Set config Global
         */
        $this->package_config = Package::get_config('package');
    }

    /**
     * Validation Package
     *
     * @param $pkg_array
     * @return array
     */
    public function validation($pkg_array)
    {
        //Create new validation
        $valid = new \WP_CLI_ERROR();

        //Get mysql parameter
        $parameter = $pkg_array['mysql'];

        //Sanitize Custom Key
        $check = $this->sanitize_mysql($parameter, true);
        if ($check['status'] === false) {
            foreach ($check['data'] as $error) {
                $valid->add_error($error);
                break;
            }
        } else {
            //Get Sanitize Data
            $return['mysql'] = array_shift($check['data']);

            //Push To sanitize return data
            $valid->add_success($return['mysql']);
        }

        return $valid->result();
    }

    /**
     * Sanitize MYSQL Params
     *
     * @param $array
     * @param bool $validate
     * @return string|boolean|array
     * @since 1.0.0
     */
    public function sanitize_mysql($array, $validate = false)
    {
        //List of require Key
        $require_key = array('DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST', 'table_prefix');

        //List of all Accept key [ Not Empty ]
        $not_empty = array('DB_NAME', 'DB_USER', 'DB_HOST', 'table_prefix', 'DB_CHARSET');

        //Create new validation
        $valid = new \WP_CLI_ERROR();

        //Check is String
        if (is_string($array)) {
            $valid->add_error(Package::_e('package', 'is_string', array("[key]" => "mysql: { ..")));
        } elseif (empty($array)) {
            //Check Empty Array
            $valid->add_error(Package::_e('package', 'empty_val', array("[key]" => "mysql: { ..")));
        } else {
            //Check is Assoc array
            if (\WP_CLI_Util::is_assoc_array($array) === false) {
                $valid->add_error(Package::_e('package', 'er_valid', array("[key]" => "mysql: { ..")));
            } else {
                //Convert To Uppercase Keys
                $array = self::_to_uppercase($array);

                //Check Require Key
                $check_require_key = \WP_CLI_Util::check_require_array($array, $require_key, true);
                if ($check_require_key['status'] === false) {
                    foreach ($check_require_key['data'] as $key) {
                        //Check in global config
                        try {
                            $get = \WP_CLI_CONFIG::get(strtolower($key));
                        } catch (\Exception $e) {
                            $get = false;
                        }
                        if ($get != false) {
                            $array[$key] = $get;
                        } else {
                            $valid->add_error(Package::_e('package', 'not_exist_key', array("[require]" => $key, "[key]" => "mysql: { .. ")));
                            break;
                        }
                    }
                }

                //Check Empty Value for accept key
                foreach ($not_empty as $k) {
                    if (array_key_exists($k, $array)) {
                        //Check if array value show error
                        if (is_array($array[$k])) {
                            $valid->add_error(Package::_e('package', 'is_not_string', array("[key]" => "mysql: { " . $k . ": ..")));
                            break;
                        } else {
                            //Check if empty value
                            if (empty(trim($array[$k]))) {
                                //Check in global config
                                try {
                                    $get = \WP_CLI_CONFIG::get(strtolower($k));
                                } catch (\Exception $e) {
                                    $get = false;
                                }
                                if ($get != false) {
                                    $array[$k] = $get;
                                } else {
                                    $valid->add_error(Package::_e('package', 'empty_val', array("[key]" => "mysql: { " . $k . ": ..")));
                                    break;
                                }
                            }
                        }
                    }
                }

                //Check MYSQL Database DB-CHARSET
                if (isset($array['DB_CHARSET']) and ! in_array(\WP_CLI_Util::to_lower_string($array['DB_CHARSET']), Package::get_config('package', 'mysql_character'))) {
                    $valid->add_error(Package::_e('package', 'er_valid', array("[key]" => "DB_CHARSET")));
                }

                //Set Default for params
                $default_param_array = array('table_prefix' => 'table_prefix', 'DB_PASSWORD' => 'db_password', 'DB_CHARSET' => 'db_charset');
                foreach ($default_param_array as $k => $v) {
                    if (array_key_exists($k, $array) === false) {
                        $array[$k] = $this->package_config['default'][$v];
                    }
                }

                //Check Connect Database
                if ( ! $valid->is_cli_error() and defined('WP_CLI_PACKAGIST_RUN_CHECK_MYSQL')) {
                    //Check Status
                    $status = 'install';
                    if (Core::check_wp_exist() === true) {
                        $status = 'update';
                    }

                    //Check Connect
                    $check_db = self::check_connect_db($array, $status);
                    if ($check_db['status'] === false) {
                        $valid->add_error($check_db['data']);
                    }
                }

                //Push To sanitize return data
                $valid->add_success($array);
            }
        }

        return ($validate === true ? $valid->result() : $array);
    }

    /**
     * Create Default Value for init command
     *
     * @param $args
     * @param bool $validate
     * @return mixed
     */
    public function init($args, $validate = false)
    {
        //Create Default Value
        $default = array();

        //To uppercase
        $args = self::_to_uppercase($args);

        //Get List Default params
        foreach ($this->params_keys as $k) {
            //Check Exist Key
            if (array_key_exists($k, $args)) {
                $default[$k] = $args[$k];
            }
        }

        //Check Error
        $error = array();
        $valid = $this->sanitize_mysql($default, true);
        if ($valid['status'] === false) {
            $error = $valid['data'];
        }

        return ($validate === true ? $error : $this->sanitize_mysql($default));
    }

    /**
     * Check Connect To DB
     *
     * @param array $args
     * @param string $status
     * @return array
     */
    public static function check_connect_db($args = array(), $status = 'install')
    {
        //Check Connect To DB
        $conn = @mysqli_connect($args['DB_HOST'], $args['DB_USER'], $args['DB_PASSWORD']);
        if ( ! $conn) {
            return array('status' => false, 'data' => Package::_e('package', 'er_db_connect'));
        }

        //Check Database in Update
        if (array_key_exists('DB_NAME', $args)) {
            @$db_select = mysqli_select_db($conn, $args['DB_NAME']);
            if ( ! $db_select) {
                if ($status == "update") {
                    return array('status' => false, 'data' => Package::_e('package', 'er_not_exist_db', array('[name]' => $args['DB_NAME'])));
                }
            } else {
                //Check Table Exist in install status
                if ($status == "install") {
                    $num_result = mysqli_query($conn, "SELECT count(*) as `total_count` FROM information_schema.TABLES WHERE TABLE_SCHEMA='" . $args['DB_NAME'] . "'");
                    $row        = mysqli_fetch_array($num_result);
                    $tbl_number = $row['total_count'];
                    if ($tbl_number > 0) {
                        //Force Remove Table
                        if (defined('WP_CLI_APP_PACKAGE_FORCE_REMOVE_MYSQL_TABLE')) {
                            mysqli_query($conn, 'SET foreign_key_checks = 0');
                            if ($result = mysqli_query($conn, 'SHOW TABLES')) {
                                while ($table = mysqli_fetch_array($result, MYSQLI_NUM)) {
                                    mysqli_query($conn, 'DROP TABLE IF EXISTS ' . $table[0]);
                                }
                            }
                            mysqli_query($conn, 'SET foreign_key_checks = 1');
                        } else {
                            $s = '';
                            if ($tbl_number > 1) {
                                $s = 's';
                            }
                            return array('status' => false, 'data' => Package::_e('package', 'er_exist_db_tbl', array('[table]' => $tbl_number, '[name]' => $args['DB_NAME'], '[sum]' => $s)));
                        }
                    }
                }
            }
        }

        return array('status' => true);
    }

    /**
     * To uppercase array
     *
     * @param $array
     * @return array
     */
    public static function _to_uppercase($array)
    {
        $list = array();
        foreach ($array as $k => $v) {
            $key = $k;
            if ($k != "table_prefix") {
                $key = strtoupper($k);
            }
            $list[$key] = $v;
        }

        return $list;
    }

    /**
     * Check Exist Database name in MYSQL
     *
     * @param array $DB
     * @return bool
     */
    public static function exist_db_name($DB = array())
    {
        //Connect to mysql
        $conn = @mysqli_connect($DB['DB_HOST'], $DB['DB_USER'], $DB['DB_PASSWORD']);
        if ($conn) {
            //Check Exist db name
            $db_select = @mysqli_select_db($conn, $DB['DB_NAME']);
            if ( ! $db_select) {
                return false;
            } else {
                return true;
            }
        }

        return null;
    }

    /**
     * install Command
     *
     * @param $pkg_array
     * @param array $args
     * @return array
     * @throws \WP_CLI\ExitException
     */
    public function install($pkg_array, $args = array())
    {
        //Prepare Step
        $step     = $args['step'];
        $all_step = $args['all_step'];

        //Check MYSQL Database
        if (isset($pkg_array['mysql'])) {
            //exist database
            $exist_db = self::exist_db_name($pkg_array['mysql']);
            if ($exist_db === false) {
                \WP_CLI_Helper::run_command("db create");
                install::install_log($step, $all_step, Package::_e('package', 'create_db', array("[db_name]" => $pkg_array['mysql']['DB_NAME'])));
                $step++;
            }
        }

        //Check install Wordpress Network or single
        $network = false;
        if (isset($pkg_array['core']['network']) and is_array($pkg_array['core']['network']) and count($pkg_array['core']['network']) > 0) {
            $network = true;
        }

        //install WordPress
        install::install_log($step, $all_step, Package::_e('package', ($network === false ? "install_wp" : "install_wp_network")));
        \WP_CLI_Helper::pl_wait_start();
        Core::install_wordpress($pkg_array, $network);
        \WP_CLI_Helper::pl_wait_end();
        $step++;

        //Check Mod Rewrite file
        $mod_rewrite = Core::is_multisite();

        //Create Mod_Rewrite for Multi Site
        if ($network === true) {
            Permalink::run_permalink_file($pkg_array);
            install::add_detail_log(Package::_e('package', 'created_file', array("[file]" => $mod_rewrite['mod_rewrite_file'])));
        }

        //Get Table Prefix
        $table_prefix = $pkg_array['mysql']['table_prefix'];

        //Change Admin Information
        if (isset($pkg_array['config']['admin'])) {
            Admin::install_admin($pkg_array);
        }

        //Check Language Setup
        if (isset($pkg_array['core']['locale']) and $pkg_array['core']['locale'] != $this->package_config['default']['locale']) {
            $lang = Locale::get_locale_detail($pkg_array['core']['locale']);
            install::install_log($step, $all_step, Package::_e('package', "install_language", array("[key]" => $pkg_array['core']['locale'] . ($lang == "" ? '' : \WP_CLI_Helper::color(" [" . $lang . "]", "P")))));
            Locale::install_lang($pkg_array);
            $step++;
        }

        //Check Permalink structure
        if (isset($pkg_array['config']['permalink']) and is_array($pkg_array['config']['permalink']) and $network === false) {
            install::install_log($step, $all_step, Package::_e('package', "change_permalink"));
            \WP_CLI_Helper::pl_wait_start();
            Permalink::change_permalink_structure($pkg_array);
            Permalink::run_permalink_file();
            \WP_CLI_Helper::pl_wait_end();
            install::add_detail_log(Package::_e('package', 'created_file', array("[file]" => $mod_rewrite['mod_rewrite_file'])));
            $step++;
        }

        //Check install Sites in Network
        if ($network === true and isset($pkg_array['core']['network']['sites']) and count($pkg_array['core']['network']['sites']) > 0) {
            install::install_log($step, $all_step, Package::_e('package', "add_sites_network"));
            Core::install_network_sites($pkg_array['core']['network']['sites']);
            $step++;
        }

        //Change TimeZone Wordpress
        if (isset($pkg_array['config']['timezone']) and ! empty($pkg_array['config']['timezone'])) {
            install::install_log($step, $all_step, Package::_e('package', "change_timezone"));
            Timezone::update_timezone($pkg_array['config']['timezone']);
            $step++;
        }

        //Update WordPress Option
        if (isset($pkg_array['config']['options']) and count($pkg_array['config']['options']) > 0) {
            install::install_log($step, $all_step, Package::_e('package', "update_options"));
            Options::install_options($pkg_array['config']['options'], $table_prefix);
            $step++;
        }

        //Create WordPress Users
        if (isset($pkg_array['config']['users']) and count($pkg_array['config']['users']) > 0) {
            install::install_log($step, $all_step, Package::_e('package', "create_users"));
            foreach ($pkg_array['config']['users'] as $users) {
                Users::add_new_user($users);
            }
            $step++;
        }

        //Check Update REST API
        $mu_plugins_path = Dir::eval_get_mu_plugins_path();
        if (isset($pkg_array['config']['rest-api'])) {
            install::install_log($step, $all_step, Package::_e('package', "update_rest_api"));
            \WP_CLI_Helper::pl_wait_start();
            Rest_API::update_rest_api($mu_plugins_path, $pkg_array['config']['rest-api']);
            \WP_CLI_Helper::pl_wait_end();
            $step++;
        }

        //Check WordPress XML-RPC
        if (isset($pkg_array['config']['xml-rpc']) and $pkg_array['config']['xml-rpc'] === false) {
            install::install_log($step, $all_step, Package::_e('package', "disable_xml_rpc"));
            \WP_CLI_Helper::pl_wait_start();
            XML_RPC::update_xml_rpc($mu_plugins_path, $pkg_array['config']['xml-rpc']);
            \WP_CLI_Helper::pl_wait_end();
            $step++;
        }

        //Check WordPress Emoji
        if (isset($pkg_array['config']['emoji']) and $pkg_array['config']['emoji'] === false) {
            install::install_log($step, $all_step, Package::_e('package', "disable_emoji"));
            \WP_CLI_Helper::pl_wait_start();
            Emoji::update_emoji($mu_plugins_path, $pkg_array['config']['emoji']);
            \WP_CLI_Helper::pl_wait_end();
            $step++;
        }

        //Check WordPress Plugins
        if (isset($pkg_array['plugins'])) {
            install::install_log($step, $all_step, Package::_e('package', "install_wp_plugins"));
            \WP_CLI_Helper::pl_wait_start();
            Plugins::update_plugins($pkg_array['plugins'], $current_plugin_list = array(), $options = array('force' => true, 'remove' => false));
            $step++;
        }

        //Check WordPress Theme
        if (isset($pkg_array['themes'])) {
            install::install_log($step, $all_step, Package::_e('package', "install_wp_themes"));
            \WP_CLI_Helper::pl_wait_start();
            Themes::update_themes($pkg_array['themes'], $current_theme_list = array(), $options = array('force' => true, 'remove' => false));
            $step++;
        }

        //Check Active WordPress Theme
        if (isset($pkg_array['config']['theme'])) {
            $run = Themes::switch_theme($pkg_array['config']['theme']);
            install::install_log($step, $all_step, $run['data']);
            $step++;
        }

        //Run Custom Commands
        if (isset($pkg_array['commands'])) {
            install::install_log($step, $all_step, Package::_e('package', "run_pkg_commands"));
            Commands::run_commands($pkg_array['commands']);
            $step++;
        }

        //WordPress Security
        install::install_log($step, $all_step, Package::_e('package', 'wp_sec_file'));
        Security::remove_security_file(true);
        if ( ! defined('WP_CLI_APP_PACKAGE_DISABLE_WORDPRESS_JSON_SECURITY')) {
            Security::wordpress_package_security_plugin($pkg_array, true);
        }
        $step++;

        return array('status' => true, 'step' => $step);
    }

    /**
     * WordPress Package Update
     *
     * @param $pkg_array
     */
    public function update($pkg_array)
    {
        # Update WordPress Admin
        Admin::update_admin($pkg_array);

        # Update WordPress Users
        Users::update_users($pkg_array);
    }

}