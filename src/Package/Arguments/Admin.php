<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\install;
use WP_CLI_PACKAGIST\Package\Utility\temp;

class Admin
{
    /**
     * Get WordPress Admin User ID
     *
     * @return int|mixed
     */
    public static function get_admin_id()
    {
        $return = \WP_CLI::runcommand('eval "global $wpdb; echo $wpdb->get_var(\"SELECT `ID` FROM `"$wpdb->users"` ORDER BY `ID` ASC LIMIT 1\");"', array('return' => 'stdout', 'parse' => 'json', 'exit_error' => false));
        if (is_numeric($return) and $return > 0) {
            return $return;
        }
        return 1; # Default Admin ID in WordPress
    }

    /**
     * install Admin information
     *
     * @param $pkg_array
     * @run before_wp_load
     */
    public static function install_admin($pkg_array)
    {
        $table_prefix = (isset($pkg_array['mysql']['table_prefix']) ? $pkg_array['mysql']['table_prefix'] : "wp_");

        //Change display_name for Admin User if Exist
        if (isset($pkg_array['config']['admin']['display_name'])) {
            \WP_CLI_Helper::wpdb_query('UPDATE `' . $table_prefix . 'users` SET `display_name` = \'' . $pkg_array['config']['admin']['display_name'] . '\' WHERE `ID` = 1;');
            install::add_detail_log(Package::_e('package', 'change_admin', array("[key]" => "display_name")));
        }

        //Check First name Or Last name for Admin User if Exist
        foreach (array('first_name', 'last_name') as $admin_key) {
            if (isset($pkg_array['config']['admin'][$admin_key])) {
                \WP_CLI_Helper::wpdb_query('UPDATE `' . $table_prefix . 'usermeta` SET `meta_value` = \'' . $pkg_array['config']['admin'][$admin_key] . '\' WHERE `user_id` = 1 AND `meta_key` = \'' . $admin_key . '\';');
                install::add_detail_log(Package::_e('package', 'change_admin', array("[key]" => $admin_key)));
            }
        }

        //Check Admin Meta
        if (isset($pkg_array['config']['admin']['meta'])) {
            foreach ($pkg_array['config']['admin']['meta'] as $meta_key => $meta_val) {
                $exist = Users::update_user_meta(self::get_admin_id(), $meta_key, $meta_val);
                $type  = ($exist === false ? 'Added' : 'Updated');
                install::add_detail_log(Package::_e('package', 'item_log', array("[what]" => "admin meta", "[key]" => $meta_key, "[run]" => $type)));
            }
        }
    }

    /**
     * Check Used Admin email or user-login for another Users
     *
     * @param $pkg_config
     * @return array
     */
    public static function check_admin_duplicate($pkg_config)
    {
        // Create new validation
        $valid = new \WP_CLI_ERROR();

        // Check Exist Users
        $users = (isset($pkg_config['users']) ? $pkg_config['users'] : array());
        foreach ($users as $user) {
            if (\WP_CLI_Util::to_lower_string($user['user_login']) == \WP_CLI_Util::to_lower_string($pkg_config['admin']['admin_user'])) {
                $valid->add_error(Package::_e('package', 'dup_admin_user', array("[what]" => "user_login")));
                break;
            }
            if (\WP_CLI_Util::to_lower_string($user['user_email']) == \WP_CLI_Util::to_lower_string($pkg_config['admin']['admin_email'])) {
                $valid->add_error(Package::_e('package', 'dup_admin_user', array("[what]" => "user_email")));
                break;
            }
        }

        return $valid->result();
    }

    /**
     * Update WordPress Admin
     *
     * @param $pkg
     * @run after_wp_load
     */
    public static function update_admin($pkg)
    {
        // Get Local Temp
        $tmp = temp::get_temp(\WP_CLI_Util::getcwd());

        //Get Admin User ID
        $admin_ID = self::get_admin_id();

        //Get Admin User Data
        $admin_info = Users::get_userdata($admin_ID);

        // Disable Send email
        add_filter('send_email_change_email', '__return_false');
        add_filter('send_password_change_email', '__return_false');

        // Check Admin_User
        if (isset($pkg['config']['admin']['admin_user'])) {
            $before_admin_user = isset($tmp['config']['admin']['admin_user']) ? $tmp['config']['admin']['admin_user'] : $admin_info['user_login'];
            if (\WP_CLI_Util::to_lower_string($pkg['config']['admin']['admin_user']) != \WP_CLI_Util::to_lower_string($before_admin_user)) {
                Users::update_user_login($pkg['config']['admin']['admin_user'], $admin_ID, "admin user", "config: { admin: { admin_user ..");
            }
        }

        // Check Admin_email
        if (isset($pkg['config']['admin']['admin_email'])) {
            $before_admin_email = isset($tmp['config']['admin']['admin_email']) ? $tmp['config']['admin']['admin_email'] : $admin_info['user_email'];
            if (\WP_CLI_Util::to_lower_string($pkg['config']['admin']['admin_email']) != \WP_CLI_Util::to_lower_string($before_admin_email)) {
                Users::update_user_email($pkg['config']['admin']['admin_email'], $admin_ID, "admin user", "config: { admin: { admin_email ..");
            }
        }

        // Check Admin_pass
        if (isset($pkg['config']['admin']['admin_pass'])) {
            $_change = false;
            if (isset($tmp['config']['admin']['admin_pass'])) {
                if (\WP_CLI_Util::to_lower_string($tmp['config']['admin']['admin_pass']) != \WP_CLI_Util::to_lower_string($pkg['config']['admin']['admin_pass'])) {
                    $_change = true;
                }
            } else {
                // Check in Database
                if (wp_check_password($pkg['config']['admin']['admin_pass'], $admin_info['user_pass']) === false) {
                    $_change = true;
                }
            }

            //Update Password
            if ($_change === true) {
                Users::wp_update_user($admin_ID, 'user_pass', $pkg['config']['admin']['admin_pass'], 'config: { admin: { admin_pass ..', 'admin user');
            }
        }

        // Check display_name & first_name and last_name
        foreach (array('display_name', 'first_name', 'last_name') as $key) {
            if (isset($pkg['config']['admin'][$key])) {
                $before_value = isset($tmp['config']['admin'][$key]) ? $tmp['config']['admin'][$key] : $admin_info[$key];
                if (\WP_CLI_Util::to_lower_string($pkg['config']['admin'][$key]) != \WP_CLI_Util::to_lower_string($before_value)) {
                    Users::wp_update_user($admin_ID, $key, $pkg['config']['admin'][$key], 'config: { admin: { ' . $key . ' ..', 'admin user');
                }
            }
        }

        // Update User Meta
        $tmp_user_meta = isset($tmp['config']['admin']['meta']) ? $tmp['config']['admin']['meta'] : array();
        $pkg_user_meta = isset($pkg['config']['admin']['meta']) ? $pkg['config']['admin']['meta'] : array();
        Users::update_package_user_meta($pkg_user_meta, $tmp_user_meta, $admin_ID, "admin meta");

        // Remove Filter Send Mail
        remove_filter('send_email_change_email', '__return_false');
        remove_filter('send_password_change_email', '__return_false');
    }

}