<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;


use WP_CLI_PACKAGIST\Package\Utility\Package_Install;
use WP_CLI_PACKAGIST\Package\Utility\Package_Temporary;

class MySQL
{
    /**
     * Constant List
     *
     * @var array
     * @see https://wordpress.org/support/article/editing-wp-config-php
     */
    public static $Constant = array(
        "DB_HOST",
        "DB_USER",
        "DB_PASSWORD",
        "DB_NAME",
        "DB_CHARSET",
        "DB_COLLATE",
        "table_prefix" //variable
    );

    /**
     * Change Wordpress database Prefix
     *
     * @after_wp_loadded
     * @param $new_prefix
     * @return bool
     * @throws \WP_CLI\ExitException
     */
    public static function changeDBPrefix($new_prefix = 'wp_')
    {
        global $wpdb;

        // Check is Multi-Site
        $_is_multisite = false;
        if (function_exists('is_multisite')) {
            if (is_multisite()) {
                $_is_multisite = true;
            }
        }

        //Set Number Step
        $step = 4;

        //Step 1 : Change $table_prefix in wp-config
        $config     = Config::get_config_transformer();
        $old_prefix = $wpdb->prefix;
        $config->update('variable', 'table_prefix', $new_prefix);
        \WP_CLI_Helper::log(\WP_CLI_Helper::color("    Step 1/" . $step . ":", "Y") . " Change table_prefix variable in wp-config.php");

        //Step 2 : Rename Wordpress Table
        $show_table_query = sprintf('SHOW TABLES LIKE "%s%%";', $wpdb->esc_like($old_prefix));
        $tables           = $wpdb->get_results($show_table_query, ARRAY_N);
        if ( ! $tables) {
            \WP_CLI_Helper::error('MySQL error: ' . $wpdb->last_error);
            return false;
        }
        foreach ($tables as $table) {
            $table        = substr($table[0], strlen($old_prefix));
            $rename_query = sprintf("RENAME TABLE `%s` TO `%s`;", $old_prefix . $table, $new_prefix . $table);
            if (false === $wpdb->query($rename_query)) {
                \WP_CLI_Helper::error('MySQL error: ' . $wpdb->last_error);
                return false;
            }
        }
        \WP_CLI_Helper::log(\WP_CLI_Helper::color("    Step 2/" . $step . ":", "Y") . " Rename all WordPress table names in database");

        //Step 3 : Update Blog Options Table
        $update_query = $wpdb->prepare("UPDATE `{$new_prefix}options` SET option_name = %s WHERE option_name = %s LIMIT 1;",
            $new_prefix . 'user_roles',
            $old_prefix . 'user_roles'
        );
        if ( ! $wpdb->query($update_query)) {
            \WP_CLI_Helper::error('MySQL error: ' . $wpdb->last_error, true);
            return false;
        }
        if ($_is_multisite) {
            $sites = get_sites(array('number' => false));
            if ($sites) {
                foreach ($sites as $site) {
                    $update_query = $wpdb->prepare("UPDATE `{$new_prefix}{$site->blog_id}_options` SET option_name = %s WHERE option_name = %s LIMIT 1;",
                        $new_prefix . $site->blog_id . '_user_roles',
                        $old_prefix . $site->blog_id . '_user_roles'
                    );
                    if ( ! $wpdb->query($update_query)) {
                        \WP_CLI_Helper::error('MySQL error: ' . $wpdb->last_error, true);
                        return false;
                    }
                }
            }
        }
        \WP_CLI_Helper::log(\WP_CLI_Helper::color("    Step 3/" . $step . ":", "Y") . " Update blog options table");

        //Step 4 : Update User Meta Prefix
        $rows = $wpdb->get_results("SELECT `meta_key` FROM `{$new_prefix}usermeta`;");
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $meta_key_prefix = substr($row->meta_key, 0, strlen($old_prefix));
                if ($meta_key_prefix !== $old_prefix) {
                    continue;
                }
                $new_key      = $new_prefix . substr($row->meta_key, strlen($old_prefix));
                $update_query = $wpdb->prepare("UPDATE `{$new_prefix}usermeta` SET meta_key=%s WHERE meta_key=%s LIMIT 1;",
                    $new_key,
                    $row->meta_key
                );
                if ( ! $wpdb->query($update_query)) {
                    \WP_CLI_Helper::error('MySQL error: ' . $wpdb->last_error);
                    return false;
                }
            }
        }
        \WP_CLI_Helper::log(\WP_CLI_Helper::color("    Step 4/" . $step . ":", "Y") . " Update users meta prefix");
        return true;
    }

    /**
     * Get Current Config of WordPress Site
     * @after_wp_loadded
     */
    public static function getCurrentConfig()
    {
        //Load Wp-config Transform
        $config_transformer = Config::get_config_transformer();

        // Constant List
        $list = array();
        foreach (self::$Constant as $key) {
            $list[$key] = ($config_transformer->exists(($key == "table_prefix" ? 'variable' : 'constant'), $key) ? str_replace("'", "", $config_transformer->get_value(($key == "table_prefix" ? 'variable' : 'constant'), $key)) : '');
        }
        return $list;
    }

    /**
     * Update Command
     *
     * @param $mysql_array
     * @throws \WP_CLI\ExitException
     */
    public static function update($mysql_array)
    {
        // Check $args
        if ($mysql_array == "default") {
            $mysql_array = self::getCurrentConfig();
        }

        // get Temporary Package
        $tmp = Package_Temporary::getTemporaryFile();

        // Get Current Status
        $tmp_mysql = (isset($tmp['mysql']) ? $tmp['mysql'] : self::getCurrentConfig());

        // If Not any change
        if ($tmp_mysql == $mysql_array) {
            return;
        }

        //Load Wp-config Transform
        $config_transformer = Config::get_config_transformer();

        // Update Constant
        foreach (self::$Constant as $key) {
            if ($tmp_mysql[$key] != $mysql_array[$key]) {
                if ($key != "table_prefix") {
                    $config_transformer->update('constant', $key, $mysql_array[$key], array('raw' => false, 'normalize' => true));
                    Package_Install::add_detail_log("Updated " . \WP_CLI_Helper::color($key, "Y") . " WordPress constant");
                } else {
                    Package_Install::add_detail_log("Changing WordPress database table prefix:");
                    self::changeDBPrefix($mysql_array[$key]);
                }
            }
        }
    }


}