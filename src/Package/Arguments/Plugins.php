<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\API\WP_Plugins_Api;
use WP_CLI_PACKAGIST\Package\Utility\Package_Install;
use WP_CLI_PACKAGIST\Package\Utility\Package_Temporary;
use WP_CLI_PACKAGIST\Package\Utility\Package_Update;

class Plugins
{
    /**
     * Get WordPress Plugins path
     *
     * @return mixed
     */
    public static function evalGetPluginsPath()
    {
        return \WP_CLI::runcommand('eval "if(defined(\'WP_PLUGIN_DIR\')) { echo WP_PLUGIN_DIR; } else { echo \'\'; }"', array('return' => 'stdout'));
    }

    /**
     * Get List Of WordPress Plugin (not contain mu-plugins)
     * @see https://developer.wordpress.org/reference/functions/get_plugins/
     */
    public static function getListPlugins()
    {
        //Check Function Exist
        if ( ! function_exists('get_plugins')) {
            require_once(Core::get_base_path() . 'wp-admin/includes/plugin.php');
        }

        //Get List Of Plugin
        $plugins = get_plugins();

        //Creat Empty List
        $plugin_list = array();

        //Push To list
        foreach ($plugins as $plugin_slug => $plugin_val) {
            //Get Plugin folder name
            $exp    = explode("/", $plugin_slug);
            $folder = (stristr($exp[0], ".php") != false ? "" : $exp[0]);

            //Ful path Plugin
            $path = \WP_CLI_FileSystem::path_join(Dir::get_plugins_dir(), $plugin_slug);

            //Added To list
            $basic_inf = array(
                'slug'        => $plugin_slug, # hello-dolly/hello.php
                'folder'      => $folder, #hello-dolly
                'path'        => $path, # Complete Path .php Plugin file
                'path_folder' => dirname($path), # complete Path without php file
                'activate'    => (is_plugin_active($plugin_slug) ? true : false)
            );

            //Push Plugins key
            $plugin_list[] = \WP_CLI_Util::array_change_key_case_recursive(array_merge($basic_inf, $plugin_val));
        }

        return $plugin_list;
    }

    /**
     * Get Current Plugins List For WordPress Package
     * @after_wp_loadded
     */
    public static function getCurrentPlugins()
    {
        $list       = array();
        $wp_plugins = self::getListPlugins();
        foreach ($wp_plugins as $plugins) {
            $plugin             = array();
            $plugin['slug']     = $plugins['folder'];
            $plugin['activate'] = $plugins['activate'];

            // Check Plugin in WordPress Plugins Directory
            $plugins_api = new WP_Plugins_Api();
            $plugin_info = $plugins_api->get_plugin_data($plugin['slug']);
            if ($plugin_info['status'] === false) {
                // Custom Plugin that not in WordPress Directory
                $plugin['url'] = ''; //@TODO Create New Proposal for get Plugin Url when not in WordPress plugin directory.(wp pack generate)
            } else {
                $plugin['version'] = $plugins['version'];
                // Check Version is latest of Plugin
                if ($plugin_info['data']['version'] == $plugins['version']) {
                    $plugin['version'] = 'latest';
                }
            }

            $list[] = $plugin;
        }

        return $list;
    }

    /**
     * Search in WordPress Plugin
     *
     * @param array $args
     * @return array|bool
     */
    public static function searchWordPressPlugins($args = array())
    {
        $defaults = array(
            /**
             * Search By :
             * name   -> Plugin name
             * folder -> Folder of plugin
             */
            'search_by' => 'name',
            /**
             * Search Value
             */
            'search'    => '',
            /**
             * Return First item
             */
            'first'     => false
        );

        // Parse incoming $args into an array and merge it with $defaults
        $args = \WP_CLI_Util::parse_args($args, $defaults);

        //Get List Of Plugins
        $list = self::getListPlugins();

        //Get List Search Result
        $search_result = array();

        //Start Loop Plugins List
        foreach ($list as $plugin) {
            //is in Search
            $is_in_search = false;

            //Check Type Search
            switch ($args['search_by']) {
                case "name":
                    if (strtolower($plugin['name']) == strtolower($args['search'])) {
                        $is_in_search = true;
                    }
                    break;

                case "folder":
                    if (stristr($plugin['path'], strtolower($args['search']))) {
                        $is_in_search = true;
                    }
                    break;
            }

            //Add To list Function
            if ($is_in_search === true) {
                $search_result[] = $plugin;
            }
        }

        //Return
        if (empty($search_result)) {
            return false;
        } else {
            //Get only first result
            if (isset($args['first'])) {
                return array_shift($search_result);
            } else {
                //Get All result
                return $search_result;
            }
        }
    }

    /**
     * Update Plugin List
     *
     * @param $pkg_plugins
     * @param array $current_plugin_list
     * @param array $options
     * @throws \WP_CLI\ExitException
     */
    public static function update_plugins($pkg_plugins, $current_plugin_list = array(), $options = array())
    {
        //Load WP_PLUGINS_API
        $plugins_api = new WP_Plugins_Api;

        //Get plugins path
        $plugins_path = \WP_CLI_FileSystem::normalize_path(self::evalGetPluginsPath());

        //Default Params
        $defaults = array(
            'force'  => false,
            'log'    => true,
            'remove' => true
        );
        $args     = \WP_CLI_Util::parse_args($options, $defaults);

        // Removed Plugins
        if (isset($args['remove']) and ! empty($current_plugin_list)) {
            $p = 0;
            foreach ($current_plugin_list as $wp_plugin) {
                //if not exist in Package Plugin list then be Removed
                $exist = false;
                foreach ($pkg_plugins as $plugin) {
                    if ($wp_plugin['slug'] == $plugin['slug']) {
                        $exist = true;
                    }
                }

                if ($exist === false) {
                    //Removed From Current Plugin
                    unset($current_plugin_list[$p]);

                    //Run Removed Plugin
                    self::runUninstallPlugin($wp_plugin['slug']);

                    //Add Log
                    if (isset($args['log']) and $args['log'] === true) {
                        \WP_CLI_Helper::pl_wait_end();
                        Package_Install::add_detail_log(Package::_e('package', 'manage_item', array("[work]" => "Removed", "[slug]" => $wp_plugin['slug'], "[type]" => "plugin", "[more]" => "")));
                        \WP_CLI_Helper::pl_wait_start();
                    }
                }
                $p++;
            }
        }

        // Check Install Or Uninstall Plugins
        $x = 0;
        foreach ($pkg_plugins as $plugin) {
            //Check Exist Const
            $wp_exist = $key_in_pkg = $key_in_current = false;
            foreach ($current_plugin_list as $c => $wp_plugin) {
                if ($wp_plugin['slug'] == $plugin['slug']) {
                    $key_in_current = $c;
                    $key_in_pkg     = $x;
                    $wp_exist       = true;
                }
            }

            // Add New Plugin
            if ($wp_exist === false) {
                // Install Plugin
                self::runInstallPlugin($plugins_path, $plugin);

                //Add Log
                if (isset($args['log']) and $args['log'] === true) {
                    \WP_CLI_Helper::pl_wait_end();
                    Package_Install::add_detail_log(Package::_e('package', 'manage_item', array("[work]" => "Added", "[slug]" => $plugin['slug'] . ((isset($plugin['version']) and \WP_CLI_Util::is_semver_version($plugin['version']) === true) ? ' ' . \WP_CLI_Helper::color("v" . $plugin['version'], "P") : ''), "[type]" => "plugin", "[more]" => ($plugin['activate'] === true ? \WP_CLI_Helper::color(" [activate]", "G") : ""))));
                    \WP_CLI_Helper::pl_wait_start();
                }
            } else {
                # Updated Plugin
                if (($pkg_plugins[$key_in_pkg] != $current_plugin_list[$key_in_current]) || (Package_Update::isAutoUpdate() and isset($pkg_plugins[$key_in_pkg]['version']) and $pkg_plugins[$key_in_pkg]['version'] == "latest")) {
                    // 1) Check Activate Or Deactivate Plugin
                    if ($pkg_plugins[$key_in_pkg]['activate'] != $current_plugin_list[$key_in_current]['activate']) {
                        //Run Command plugin
                        $cmd = "plugin " . ($pkg_plugins[$key_in_pkg]['activate'] === true ? 'activate' : 'deactivate') . " {$pkg_plugins[$key_in_pkg]['slug']}";
                        \WP_CLI_Helper::run_command($cmd, array('exit_error' => false));

                        //Add Log
                        if (isset($args['log']) and $args['log'] === true) {
                            \WP_CLI_Helper::pl_wait_end();
                            Package_Install::add_detail_log(Package::_e('package', 'manage_item', array("[work]" => ($pkg_plugins[$key_in_pkg]['activate'] === true ? 'Activate' : 'Deactivate'), "[slug]" => $pkg_plugins[$key_in_pkg]['slug'], "[type]" => "plugin", "[more]" => "")));
                            \WP_CLI_Helper::pl_wait_start();
                        }
                    }

                    // 2) Update IF Plugin Version is Changed
                    if (isset($pkg_plugins[$key_in_pkg]['version']) and isset($current_plugin_list[$key_in_current]['version'])) {
                        //Get Last Version Plugin From WordPress Directory
                        $version = $pkg_plugins[$key_in_pkg]['version'];
                        if ($version == "latest") {
                            $version = $plugins_api->get_last_version_plugin($pkg_plugins[$key_in_pkg]['slug'], true);
                        }

                        // Get Current Version this theme in WordPress
                        if ($current_plugin_list[$key_in_current]['version'] == "latest" and $pkg_plugins[$key_in_pkg]['version'] == "latest") {
                            $search_plugin = self::searchWordPressPlugins(array('search_by' => 'folder', 'search' => $pkg_plugins[$key_in_pkg]['slug'], 'first' => true));
                            if (isset($search_plugin['version']) and ! empty($search_plugin['version'])) {
                                $current_plugin_list[$key_in_current]['version'] = $search_plugin['version'];
                            }
                        }

                        if ($version != $current_plugin_list[$key_in_current]['version']) {
                            //Run Command
                            $prompt = $pkg_plugins[$key_in_pkg]['slug'];
                            if ($version != "latest") {
                                $prompt .= ' --version=' . $version;
                            }

                            $cmd = "plugin update {$prompt}";
                            \WP_CLI_Helper::run_command($cmd, array('exit_error' => false));

                            //Add Log
                            if (isset($args['log']) and $args['log'] === true) {
                                \WP_CLI_Helper::pl_wait_end();
                                Package_Install::add_detail_log(Package::_e('package', 'manage_item', array("[work]" => "Updated", "[slug]" => $pkg_plugins[$key_in_pkg]['slug'] . ((isset($version) and \WP_CLI_Util::is_semver_version($version) === true) ? ' ' . \WP_CLI_Helper::color("v" . $version, "P") : ''), "[type]" => "plugin", "[more]" => "")));
                                \WP_CLI_Helper::pl_wait_start();
                            }
                        }
                    }

                    // 3) Update IF Plugin URL is Changed, OR User Changed Plugin From Version to Custom URL Or Reverse
                    if (
                        (isset($pkg_plugins[$key_in_pkg]['url']) and isset($current_plugin_list[$key_in_current]['url']) and $pkg_plugins[$key_in_pkg]['url'] != $current_plugin_list[$key_in_current]['url'])
                        ||
                        (isset($pkg_plugins[$key_in_pkg]['version']) and ! isset($current_plugin_list[$key_in_current]['version']))
                        ||
                        (isset($pkg_plugins[$key_in_pkg]['url']) and ! isset($current_plugin_list[$key_in_current]['url']))
                    ) {
                        // Remove Before Plugin
                        self::runUninstallPlugin($pkg_plugins[$key_in_pkg]['slug']);

                        // Install New Plugin
                        sleep(2); // Wait For Remove Before Dir if Exist
                        self::runInstallPlugin($plugins_path, $pkg_plugins[$key_in_pkg]);

                        //Add Log
                        if (isset($args['log']) and $args['log'] === true) {
                            \WP_CLI_Helper::pl_wait_end();
                            Package_Install::add_detail_log(Package::_e('package', 'manage_item', array("[work]" => "Updated", "[slug]" => $pkg_plugins[$key_in_pkg]['slug'], "[type]" => "plugin", "[more]" => "")));
                            \WP_CLI_Helper::pl_wait_start();
                        }
                    }
                }
            }

            $x++;
        }

        if (isset($args['log'])) {
            \WP_CLI_Helper::pl_wait_end();
        }
    }

    /**
     * Update Plugins
     *
     * @param $pkg_plugins
     * @throws \WP_CLI\ExitException
     */
    public static function update($pkg_plugins)
    {
        // Default
        if ($pkg_plugins == "default") {
            $pkg_plugins = array();
        }

        // get Temp Package
        $tmp         = Package_Temporary::getTemporaryFile();
        $tmp_plugins = (isset($tmp['plugins']) ? $tmp['plugins'] : array());

        // If Not any change
        if (($tmp_plugins == $pkg_plugins) and ! Package_Update::isAutoUpdate()) {
            return;
        }

        // Update plugins
        self::update_plugins($pkg_plugins, $tmp_plugins, array('force' => true));
    }

    /**
     * Run Uninstall Plugin
     *
     * @param $slug
     */
    public static function runUninstallPlugin($slug)
    {
        $cmd = "plugin uninstall {$slug} --deactivate";
        \WP_CLI_Helper::run_command($cmd, array('exit_error' => false));
    }

    /**
     * Install a Plugin with WP-CLI
     *
     * @param $plugins_path | WordPress Plugin Directory Path (WP_PLUGINS_DIR)
     * @param $plugin
     * @throws \WP_CLI\ExitException
     * @example array('slug' => '', 'version' => '', 'activate' => false)
     */
    public static function runInstallPlugin($plugins_path, $plugin)
    {
        //Check From URL or WordPress Plugin
        if (isset($plugin['url'])) {
            $prompt = $plugin['url'];
        } else {
            $prompt = $plugin['slug'];
            if ($plugin['version'] != "latest") {
                $prompt .= ' --version=' . $plugin['version'];
            }
        }

        //Check Activate
        // We don't Custom url plugins activate because folder name is changed after downloaded
        if ($plugin['activate'] === true and ! isset($plugin['url'])) {
            $prompt .= ' --activate';
        }

        //Run Command
        $cmd = "plugin install {$prompt} --force";
        \WP_CLI_Helper::run_command($cmd, array('exit_error' => true));

        //Sanitize Folder Plugins
        if (isset($plugin['url']) and ! empty($plugins_path)) {
            // Wait For Complete Downloaded dir
            sleep(3);

            //Get Last Dir
            $last_dir = \WP_CLI_FileSystem::sort_dir_by_date($plugins_path, "DESC");

            //Sanitize
            $plugin_slug = \WP_CLI_Util::to_lower_string($plugin['slug']);
            self::sanitizeDirBySlug($plugin_slug, \WP_CLI_FileSystem::path_join($plugins_path, $last_dir[0]));

            // Activate Plugin
            if ($plugin['activate'] === true) {
                $cmd = "plugin activate {$plugin_slug}";
                \WP_CLI_Helper::run_command($cmd, array('exit_error' => false));
            }
        }
    }

    /**
     * Convert Plugins/themes dir name to plugin slug
     *
     * @param $slug
     * @param $dir_path
     * @return bool
     * @throws \WP_CLI\ExitException
     */
    public static function sanitizeDirBySlug($slug, $dir_path)
    {
        //Sanitize path
        $path = rtrim(\WP_CLI_FileSystem::normalize_path($dir_path), "/") . "/";

        //Check Real path
        if (realpath($path) and is_dir($path)) {
            //Get folder name
            $dir_name   = \WP_CLI_Util::to_lower_string(basename($path));
            $first_path = str_ireplace($dir_name, "", $path);
            $slug       = \WP_CLI_Util::to_lower_string($slug);

            //Check Find equal
            if ($dir_name != $slug) {
                $new_path = \WP_CLI_FileSystem::path_join($first_path, $slug);
                Dir::moveDir($path, $new_path);
                return true;
            }
        }
    }
}