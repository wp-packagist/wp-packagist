<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\Package_Install;
use WP_CLI_PACKAGIST\Package\Utility\Package_Temporary;
use WP_CLI_PACKAGIST\Package\Utility\Package_Update;

class Options
{
    /**
     * Sanitize Option name
     *
     * @param $option_name
     * @return mixed
     */
    public static function sanitize_option_name($option_name)
    {
        $option_name = str_ireplace(Package::get_config('package', 'tbl_prefix_key'), Config::get_tbl_prefix(), $option_name); # Check Table Prefix
        return $option_name;
    }

    /**
     * Check Exist Options name
     *
     * @param $option_name
     * @return bool
     */
    public static function exist_option_name($option_name)
    {
        global $wpdb;
        if (Package_Update::isUpdateProcess()) {
            $_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->options}` WHERE option_name = '{$option_name}'");
            return $_count > 0;
        }

        //Run Command For Install WordPress Package Process
        $return = \WP_CLI::runcommand('eval "if(get_option(\'' . self::sanitize_option_name($option_name) . '\') ===false) { echo 0; } else { echo 1; }"', array('return' => 'stdout', 'parse' => 'json'));
        if ($return == "1") {
            return true;
        }
        return false;
    }

    /**
     * Add/Update WordPress Option
     *
     * @param $table_prefix
     * @param $option_name
     * @param $option_value
     * @param string $autoload
     * @return bool
     */
    public static function update_option($table_prefix, $option_name, $option_value, $autoload = 'yes')
    {
        //Sanitize Meta Value
        $option_value = self::sanitize_meta_value($option_value);

        //Sanitize Option name
        $option_name = self::sanitize_option_name($option_name);

        //Check Exist Option
        $exist = self::exist_option_name($option_name);

        //We don't Use [wp option add] Command Because we want Force Push to database
        if ($exist === true) {
            \WP_CLI_Helper::wpdb_query('UPDATE `' . $table_prefix . 'options` SET `option_value` = \'' . $option_value . '\',`autoload` = \'' . $autoload . '\' WHERE `option_name` = \'' . $option_name . '\';', array('exit_error' => false));
        } else {
            \WP_CLI_Helper::wpdb_query('INSERT INTO `' . $table_prefix . 'options` (`option_id`, `option_name`, `option_value`, `autoload`) VALUES (NULL, \'' . $option_name . '\', \'' . $option_value . '\', \'' . $autoload . '\');', array('exit_error' => false));
        }

        return $exist;
    }

    /**
     * Sanitize Meta Value
     *
     * @param $meta_value
     * @return string
     */
    public static function sanitize_meta_value($meta_value)
    {
        # Check Value is Json Type
        if ( ! is_string($meta_value) || ! is_numeric($meta_value)) {
            if (is_array($meta_value)) {
                $meta_value = serialize($meta_value);
            } elseif (json_decode($meta_value, true) != null) {
                $meta_value = serialize(json_decode($meta_value, true));
            }
        }

        return $meta_value;
    }

    /**
     * Update Options in install
     *
     * @param $table_prefix
     * @param $options
     */
    public static function installOptions($options, $table_prefix)
    {
        if (is_array($options)) {
            foreach ($options as $option) {
                self::runCurdOption($table_prefix, $option);
            }
        }
    }

    /**
     * Run Update/Add Option
     *
     * @param $table_prefix
     * @param $option
     */
    public static function runCurdOption($table_prefix, $option)
    {
        $exist = self::update_option($table_prefix, $option['option_name'], $option['option_value'], $option['autoload']);
        Package_Install::add_detail_log(Package::_e('package', 'item_log', array("[what]" => "option", "[key]" => self::sanitize_option_name($option['option_name']), "[run]" => ($exist === true ? "Updated" : "Added"))));
    }

    /**
     * Get List Of Default Options
     */
    public static function get_default_options()
    {
        $list = array();
        foreach (Package::get_config('package', 'default_wp_options') as $key) {
            $list[] = self::sanitize_option_name($key);
        }
        return $list;
    }

    /**
     * Update command Option
     *
     * @param $pkg
     */
    public static function update($pkg)
    {
        global $wpdb;

        //Get Local Temp
        $tmp = Package_Temporary::getTemporaryFile();

        //Get Options List
        $tmp_options = (isset($tmp['config']['options']) ? $tmp['config']['options'] : array());
        $pkg_options = (isset($pkg['config']['options']) ? $pkg['config']['options'] : array());

        // Get Default WordPress Options
        $default_options = self::get_default_options();

        // Remove Options
        if (count($tmp_options) > count($pkg_options)) {
            self::removedLoopOptions($default_options, $tmp_options, $pkg_options);
        }

        // Add Options
        if (count($pkg_options) > count($tmp_options)) {
            foreach ($pkg_options as $package_options) {
                $_exist = false;

                // Check Exist Options in Temp
                foreach ($tmp_options as $temp_options) {
                    if (\WP_CLI_Util::to_lower_string($package_options['option_name']) == \WP_CLI_Util::to_lower_string($temp_options['option_name'])) {
                        $_exist = true;
                    }
                }

                if ( ! $_exist) {
                    self::runCurdOption($wpdb->prefix, $package_options);
                }
            }
        }

        // Edit Options item
        $x_pkg = 0;
        foreach ($pkg_options as $pack_options) {
            $_exist = $tmp_key = $pkg_key = false;

            // Check Exist Option in Tmp
            $x_tmp = 0;
            foreach ($tmp_options as $temp_options) {
                if (\WP_CLI_Util::to_lower_string($pack_options['option_name']) == \WP_CLI_Util::to_lower_string($temp_options['option_name'])) {
                    $_exist  = true;
                    $tmp_key = $x_tmp;
                    $pkg_key = $x_pkg;
                }
                $x_tmp++;
            }

            if ($_exist === true) {
                $current_pkg_option = $pkg_options[$pkg_key];
                $current_tmp_option = $tmp_options[$tmp_key];

                // Check different
                if ($current_pkg_option != $current_tmp_option) {
                    self::runCurdOption($wpdb->prefix, $current_pkg_option);
                }
            } else {
                // If User Change a Option name in wordpress.json file we added new Option and remove before Option
                self::removedLoopOptions($default_options, $tmp_options, $pkg_options); // This Function Removed IF options exist in Temp and not in Package
                self::runCurdOption($wpdb->prefix, $pack_options);
            }
            $x_pkg++;
        }
    }

    /**
     * Run Remove Option in Update Command
     *
     * @param $default_options
     * @param array $tmp_options
     * @param array $pkg_options
     */
    public static function removedLoopOptions($default_options, $tmp_options = array(), $pkg_options = array())
    {
        global $wpdb;

        foreach ($tmp_options as $temp_options) {
            $_exist = false;

            // Check Exist Options in Pkg
            foreach ($pkg_options as $pack_options) {
                if (\WP_CLI_Util::to_lower_string($pack_options['option_name']) == \WP_CLI_Util::to_lower_string($temp_options['option_name'])) {
                    $_exist = true;
                }
            }

            if ( ! $_exist) {
                // Sanitize Option name
                $_sanitize_name = self::sanitize_option_name($temp_options['option_name']);

                // Check User Exist in WordPress Database and not in Default WordPress Options
                $_exist_in_db = self::exist_option_name($_sanitize_name);

                // Check Not in Default Options
                if ($_exist_in_db === true and ! in_array($_sanitize_name, $default_options)) {
                    // Delete From Database Without Cache
                    $wpdb->query("DELETE FROM `{$wpdb->options}` WHERE option_name = '{$_sanitize_name}'");

                    // Log
                    Package_Install::add_detail_log(Package::_e('package', 'manage_item_red', array("[work]" => "Removed", "[key]" => $_sanitize_name, "[type]" => "option")));
                }
            }
        }
    }

}