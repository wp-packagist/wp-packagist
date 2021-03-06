<?php

use \WP_CLI_PACKAGIST\Package\Arguments\Core;
use \WP_CLI_PACKAGIST\Package\Arguments\Users;

/**
 * Open WordPress site in browser.
 *
 * ## OPTIONS
 *
 * [<type>]
 * : Show type of WordPress page.
 *
 * [--user_id=<user-id>]
 * : The User ID.
 *
 * [--user_login=<user-login>]
 * : The User Login.
 *
 * [--user_email=<user-email>]
 * : The User Email.
 *
 * ## EXAMPLES
 *
 *      # Open the WordPress homepage.
 *      $ wp run
 *
 *      # Open Custom URL of WordPress in browser e.g. (http://site.com/contact).
 *      $ wp run contact
 *
 *      # Automatic login as the WordPress admin user and open /wp-admin in browser.
 *      $ wp run admin
 *
 *      # Automatic login as the WordPress user with ID=5 and open /wp-admin in browser.
 *      $ wp run admin --user_id=5
 *
 *      # Automatic login as the WordPress user with user_login=alain and open admin area.
 *      $ wp run admin --user_login=alain
 *
 *      # Automatic login as the WordPress user with user_email=email@site.com and open admin area.
 *      $ wp run admin --user_email=email@site.com
 */
\WP_CLI::add_command('run', function ($args, $assoc_args) {
    // Get Site Url
    $site_url = Core::getSiteUrl();

    //Default show HomeUrl
    if ( ! isset($args[0])) {
        \WP_CLI_Helper::Browser($site_url);
        return;
    }

    // Open /wp-admin/ in browser
    if (isset($args[0]) and $args[0] == "admin") {
        // Check Custom User Id or User login
        if (isset($assoc_args['user_id']) || isset($assoc_args['user_login']) || isset($assoc_args['user_email'])) {
            $user_id = false;

            $parameter = array(
                'user_login' => 'user_login',
                'user_email' => 'user_email',
                'user_id'    => 'ID',
            );
            foreach ($parameter as $key => $key_in_user_function) {
                if (isset($assoc_args[$key]) and ! empty($assoc_args[$key])) {
                    $user = Users::check_exist_user(array($key_in_user_function => $assoc_args[$key]));
                    if ($user['status'] === true) {
                        $user_id = $user['data']['ID'];
                        break;
                    }
                }
            }

            // Check Exist User
            if ( ! $user_id) {
                \WP_CLI_Helper::error("User not found.");
                return;
            }
        } else {
            // Get First User ID in WordPress
            $user_id = Users::get_first_wordpress_user();
        }

        // Login As User
        \WP_CLI_Helper::pl_wait_start(false);
        $set_current_user = Users::set_login_user($user_id);
        if ($set_current_user['status'] === false) {
            \WP_CLI_Helper::pl_wait_end();
            \WP_CLI_Helper::error($set_current_user['message']);
            return;
        }

        // Open in Browser
        \WP_CLI_Helper::pl_wait_end();
        \WP_CLI_Helper::log("Open URL in the browser: " . WP_CLI_Helper::color($set_current_user['link'], "Y"));
        \WP_CLI_Helper::Browser($set_current_user['link']);
        return;
    }

    // Open Custom WordPress Url
    $home = rtrim(get_option("home"), "/");
    $url  = trim($args[0]);
    \WP_CLI_Helper::Browser($home . "/" . ltrim($url, "/"));
    return;
});