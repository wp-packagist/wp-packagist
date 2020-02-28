<?php

namespace WP_CLI_PACKAGIST\Package\Utility;

use WP_CLI_PACKAGIST\Package\Arguments\Admin;
use WP_CLI_PACKAGIST\Package\Arguments\Config;
use WP_CLI_PACKAGIST\Package\Arguments\Cookie;
use WP_CLI_PACKAGIST\Package\Arguments\Core;
use WP_CLI_PACKAGIST\Package\Arguments\Dir;
use WP_CLI_PACKAGIST\Package\Arguments\Emoji;
use WP_CLI_PACKAGIST\Package\Arguments\Locale;
use WP_CLI_PACKAGIST\Package\Arguments\MySQL;
use WP_CLI_PACKAGIST\Package\Arguments\Options;
use WP_CLI_PACKAGIST\Package\Arguments\Permalink;
use WP_CLI_PACKAGIST\Package\Arguments\Plugins;
use WP_CLI_PACKAGIST\Package\Arguments\REST_API;
use WP_CLI_PACKAGIST\Package\Arguments\Security;
use WP_CLI_PACKAGIST\Package\Arguments\Themes;
use WP_CLI_PACKAGIST\Package\Arguments\Timezone;
use WP_CLI_PACKAGIST\Package\Arguments\Users;
use WP_CLI_PACKAGIST\Package\Arguments\Version;
use WP_CLI_PACKAGIST\Package\Arguments\XML_RPC;
use \WP_CLI_PACKAGIST\Package\Arguments\Core as Network;
use WP_CLI_PACKAGIST\Package\Package;

class Package_Update extends Package
{
    /**
     * Update WordPress Package
     *
     * @param $pkg_array
     * @throws \Exception
     */
    public function run($pkg_array)
    {
        # Remove please wait
        if (defined('WP_CLI_PLEASE_WAIT_LOG')) {
            \WP_CLI_Helper::pl_wait_end();
        }

        # Set define when update package is process
        define('WP_CLI_PACKAGIST_RUN_UPDATE_PROCESS', true);

        # Set Timer for Process
        $start_time = time();

        # Run
        self::runUpdatePackage($pkg_array);

        # Save Package LocalTemp
        Package_Temporary::saveTemporary($pkg_array);

        # Success Process
        if (defined('WP_CLI_PACKAGIST_UPDATE_LOG')) {
            \WP_CLI_Helper::success(Package::_e('package', 'success_update') . ' ' . Package::_e('config', 'process_time', array("[time]" => \WP_CLI_Helper::process_time($start_time))));
        } else {
            \WP_CLI_Helper::success(Package::_e('package', 'not_change_pkg'));
        }
    }

    /**
     * Check IS Package Update Process Running
     *
     * @return bool
     */
    public static function isUpdateProcess()
    {
        return (defined('WP_CLI_PACKAGIST_RUN_UPDATE_PROCESS') && WP_CLI_PACKAGIST_RUN_UPDATE_PROCESS);
    }

    /**
     * Run Package Update Parameter
     *
     * @param $pkg_array
     * @throws \WP_CLI\ExitException
     */
    public static function runUpdatePackage($pkg_array)
    {
        # Get MU-Plugins
        $MU_Plugins = \WP_CLI_FileSystem::normalize_path(WPMU_PLUGIN_DIR);

        # Update WordPress Version
        Version::update_version($pkg_array);

        # Update WordPress Locale
        Locale::update_language($pkg_array);

        # Update WordPress Multi-Site
        Network::update_network($pkg_array);

        # Cookie Prefix
        Cookie::update((isset($pkg_array['config']['cookie']) ? $pkg_array['config']['cookie'] : 'default'));

        # Constant
        Config::update((isset($pkg_array['config']['constant']) ? $pkg_array['config']['constant'] : 'default'));

        # Update Title
        Core::updateTitle((isset($pkg_array['config']['title']) ? $pkg_array['config']['title'] : 'default'));

        # Update Options
        Options::update($pkg_array);

        # Update XML-RPC
        XML_RPC::update($MU_Plugins, (isset($pkg_array['config']['xml-rpc']) && $pkg_array['config']['xml-rpc'] === false ? false : XML_RPC::$default_active_xml_rpc));

        # Update Emoji
        Emoji::update($MU_Plugins, (isset($pkg_array['config']['emoji']) && $pkg_array['config']['emoji'] === false ? false : Emoji::$default_active_emoji));

        # Update WordPress Admin
        Admin::update($pkg_array);

        # Update WordPress Users
        Users::update($pkg_array);

        # Update REST-API
        REST_API::update($MU_Plugins, (isset($pkg_array['config']['rest-api']) ? $pkg_array['config']['rest-api'] : 'default'));

        # Update WordPress TimeZone
        Timezone::update((isset($pkg_array['config']['timezone']) ? $pkg_array['config']['timezone'] : Timezone::$default_timezone));

        # Update Plugins
        Plugins::update((isset($pkg_array['plugins']) ? $pkg_array['plugins'] : 'default'));

        # Update Themes
        Themes::update((isset($pkg_array['themes']) ? $pkg_array['themes'] : 'default'));

        # Switch Theme
        Themes::updateSwitchTheme((isset($pkg_array['config']['theme']) ? $pkg_array['config']['theme'] : 'default'));

        # Dir
        Dir::updateCommand($pkg_array);

        # URL
        Core::updateURL((isset($pkg_array['config']['url']) ? $pkg_array['config']['url'] : 'default'));

        # Permalink
        Permalink::update($pkg_array);

        # Update MySQL
        MySQL::update((isset($pkg_array['mysql']) ? $pkg_array['mysql'] : 'default'));
    }

    /**
     * Check Enable Auto Update Core/Plugins/Themes to last version
     */
    public static function isAutoUpdate()
    {
        // Default value
        $var = Package::get_config('package', 'auto_update');
        try {
            $get = \WP_CLI_CONFIG::get('auto_update');
            $get = \WP_CLI_Util::is_boolean($get);
            if ( ! is_null($get)) {
                $var = $get;
            }
        } catch (\Exception $e) {
            // Use same Default Of Config
        }

        return $var;
    }
}