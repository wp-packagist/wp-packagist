<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\install;

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
        //Run Command
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
    public static function install_options($options, $table_prefix)
    {
        if (is_array($options)) {
            foreach ($options as $option) {
                $exist = self::update_option($table_prefix, $option['option_name'], $option['option_value'], $option['autoload']);
                install::add_detail_log(Package::_e('package', 'item_log', array("[what]" => "option", "[key]" => $option['option_name'], "[run]" => ($exist === true ? "Updated" : "Added"))));
            }
        }
    }

}