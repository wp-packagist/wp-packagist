<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\Package_Install;
use WP_CLI_PACKAGIST\Package\Utility\Package_Temporary;

class Cookie
{
    /**
     * Get Default WordPress Cookie
     *
     * @return mixed
     */
    public static function getDefaultCookie()
    {
        return Package::get_config('package', 'default_wordpress_cookie');
    }

    /**
     * Default Constant
     *
     * @see https://developer.wordpress.org/reference/functions/wp_cookie_constants/
     */
    public static function getDefaultCookieConstant()
    {
        return array(
            "COOKIEHASH",
            "USER_COOKIE",
            "PASS_COOKIE",
            "AUTH_COOKIE",
            "SECURE_AUTH_COOKIE",
            "LOGGED_IN_COOKIE",
            "TEST_COOKIE"
        );
    }

    /**
     * Change WordPress Cookie Constant
     *
     * @param $cookie_prefix
     * @param $site_url
     * @param bool $hash_test_cookie
     * @return array
     */
    public static function setCookiePrefix($cookie_prefix, $site_url, $hash_test_cookie = true)
    {
        // Default constant
        $list = self::getDefaultCookieConstant();

        //Load WP-config Transform
        $config_transformer = Config::get_config_transformer();

        //First Remove All Cookie Constant if exist
        foreach ($list as $const) {
            $config_transformer->remove('constant', $const);
        }

        //Hash url
        if (function_exists('wp_hash_password')) {
            $hash_url = wp_hash_password($site_url);
        } else {
            $hash_url = sha1($site_url);
        }

        //Added constant
        if (trim($cookie_prefix) != self::getDefaultCookie()) {
            foreach ($list as $const) {
                switch ($const) {
                    case "COOKIEHASH":
                        $config_transformer->update('constant', $const, $hash_url, array('raw' => false, 'normalize' => true));
                        break;
                    case "USER_COOKIE":
                        $config_transformer->update('constant', $const, "'" . $cookie_prefix . "user_' . COOKIEHASH", array('raw' => true, 'normalize' => true));
                        break;
                    case "PASS_COOKIE":
                        $config_transformer->update('constant', $const, "'" . $cookie_prefix . "pass_' . COOKIEHASH", array('raw' => true, 'normalize' => true));
                        break;
                    case "AUTH_COOKIE":
                        $config_transformer->update('constant', $const, "'" . $cookie_prefix . "' . COOKIEHASH", array('raw' => true, 'normalize' => true));
                        break;
                    case "SECURE_AUTH_COOKIE":
                        $config_transformer->update('constant', $const, "'" . $cookie_prefix . "sec_' . COOKIEHASH", array('raw' => true, 'normalize' => true));
                        break;
                    case "LOGGED_IN_COOKIE":
                        $config_transformer->update('constant', $const, "'" . $cookie_prefix . "login_' . COOKIEHASH", array('raw' => true, 'normalize' => true));
                        break;
                    case "TEST_COOKIE":
                        if ($hash_test_cookie) {
                            $test_cookie = "'" . \WP_CLI_Util::random_key(30, false) . "'";
                        } else {
                            $test_cookie = "'" . $cookie_prefix . "_cookie_test'";
                        }
                        $config_transformer->update('constant', $const, $test_cookie, array('raw' => true, 'normalize' => true));
                        break;
                }
            }
        }

        return array('status' => true);
    }

    /**
     * Update Cookie Prefix
     *
     * @param $cookie_prefix
     */
    public static function update($cookie_prefix)
    {
        // Default
        if ($cookie_prefix == "default") {
            $cookie_prefix = self::getDefaultCookie();
        }

        // get Temp Package
        $tmp = Package_Temporary::getTemporaryFile();

        // Get Current From Tmp
        $tmp_cookie = (isset($tmp['config']['cookie']) ? $tmp['config']['cookie'] : self::getDefaultCookie());

        // If Not any change
        if ($tmp_cookie == $cookie_prefix) {
            return;
        }

        // Update Cookie
        $cookie_run = self::setCookiePrefix($cookie_prefix, Core::getSiteUrl());

        // Add Update Log
        if ($cookie_run) {
            Package_Install::add_detail_log("Updated WordPress " . \WP_CLI_Helper::color("Cookie prefix", "Y") . "");
        }
    }
}