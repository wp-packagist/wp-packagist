<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\API\WP_Themes_Api;
use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\Package_Install;
use WP_CLI_PACKAGIST\Package\Utility\Package_Temporary;
use WP_CLI_PACKAGIST\Package\Utility\Package_Update;

class Themes
{
    /**
     * Get Current theme WordPress
     *
     * @return mixed
     */
    public static function evalGetCurrentTheme()
    {
        return \WP_CLI::runcommand('eval "echo get_template();"', array('return' => 'stdout'));
    }

    /**
     * Get WordPress theme list in eval cli [use in install step]
     */
    public static function evalGetThemesList()
    {
        return \WP_CLI::runcommand('eval "$list = array(); foreach(wp_get_themes() as $stylesheet => $v) { $list[] = $stylesheet; } echo json_encode($list);"', array('return' => 'stdout', 'parse' => 'json'));
    }

    /**
     * Get base WordPress theme Path
     */
    public static function evalGetThemeRoot()
    {
        return \WP_CLI::runcommand('eval "echo get_theme_root();"', array('return' => 'stdout'));
    }

    /**
     * Get List Of WordPress Themes
     *
     * @see https://developer.wordpress.org/reference/functions/wp_get_themes/
     * @when after_wp_load
     */
    public static function getListThemes()
    {
        //Get List Of themes
        $themes = wp_get_themes();

        //Creat Empty List
        $themes_list = array();

        //Get Current stylesheet theme
        $current_theme = self::evalGetCurrentTheme();

        //List Of Data
        $data = array('name', 'title', 'version', 'parent_theme', 'template_dir', 'stylesheet_dir', 'template', 'stylesheet', 'screenshot', 'description', 'author', 'tags', 'theme_root', 'theme_root_uri');

        //Push To list
        foreach ($themes as $stylesheet => $theme_val) {
            //Get Theme Detail
            $theme = wp_get_theme($stylesheet);

            //Added To list
            $info = array(
                'slug'     => $stylesheet, # twentyseventeen
                'path'     => $theme->__get('template_dir'), # Complete path folder theme
                'activate' => ($current_theme == strtolower($stylesheet) ? true : false),
            );
            foreach ($data as $key) {
                $info[$key] = $theme->__get($key);
            }

            //Push
            $themes_list[] = \WP_CLI_Util::array_change_key_case_recursive($info);
        }

        return $themes_list;
    }

    /**
     * Search in WordPress Theme
     *
     * @param array $args
     * @return array|bool
     */
    public static function searchWordPressThemes($args = array())
    {
        $defaults = array(
            /**
             * Search By :
             * name   -> Theme Name
             * folder -> Folder of Theme
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
        $list = self::getListThemes();

        //Get List Search Result
        $search_result = array();

        //Start Loop theme List
        foreach ($list as $theme) {
            //is in Search
            $is_in_search = false;

            //Check Type Search
            switch ($args['search_by']) {
                case "name":
                    if (strtolower($theme['name']) == strtolower($args['search'])) {
                        $is_in_search = true;
                    }
                    break;

                case "folder":
                    if (stristr($theme['path'], strtolower($args['search']))) {
                        $is_in_search = true;
                    }
                    break;
            }

            //Add To list Function
            if ($is_in_search === true) {
                $search_result[] = $theme;
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
     * Update Themes List
     *
     * @param $pkg_themes
     * @param array $current_themes_list
     * @param array $options
     * @throws \WP_CLI\ExitException
     */
    public static function update_themes($pkg_themes, $current_themes_list = array(), $options = array())
    {
        //Load WP_THEMES_API
        $themes_api = new WP_Themes_Api();

        //Get theme root
        $theme_root = self::evalGetThemeRoot();

        // Get Current Active Theme in WordPress
        $Current_theme = self::evalGetCurrentTheme();

        //Default Params
        $defaults = array(
            'force'  => false,
            'log'    => true,
            'remove' => true
        );
        $args     = \WP_CLI_Util::parse_args($options, $defaults);

        //Check Removed Theme
        if (isset($args['remove']) and ! empty($current_themes_list)) {
            foreach ($current_themes_list as $wp_theme_stylesheet => $wp_theme_ver) {
                //if not exist in Package themes list then be Removed
                $exist = false;
                foreach ($pkg_themes as $stylesheet => $version) {
                    if ($wp_theme_stylesheet == $stylesheet) {
                        $exist = true;
                    }
                }

                if ($exist === false) {
                    //Check if is active theme
                    if ($Current_theme == $wp_theme_stylesheet) {
                        if (isset($args['log']) and $args['log'] === true) {
                            \WP_CLI_Helper::pl_wait_end();
                            \WP_CLI_Helper::error(Package::_e('package', 'er_delete_no_theme', array("[theme]" => $wp_theme_stylesheet)), true);
                            \WP_CLI_Helper::pl_wait_start();
                        }
                    } else {
                        //Removed From Current theme
                        unset($current_themes_list[$wp_theme_stylesheet]);

                        //Run Removed Theme
                        self::runDeleteTheme($wp_theme_stylesheet);

                        //Add Log
                        if (isset($args['log']) and $args['log'] === true) {
                            \WP_CLI_Helper::pl_wait_end();
                            Package_Install::add_detail_log(Package::_e('package', 'manage_item', array("[work]" => "Removed", "[slug]" => $wp_theme_stylesheet, "[type]" => "theme", "[more]" => "")));
                            \WP_CLI_Helper::pl_wait_start();
                        }
                    }
                }
            }
        }

        // Check install or Update Theme
        foreach ($pkg_themes as $stylesheet => $version) {
            //Check Exist Theme
            $wp_exist           = false;
            $version_in_pkg     = $version;
            $version_in_current = '';
            foreach ($current_themes_list as $wp_theme_stylesheet => $wp_theme_ver) {
                if ($wp_theme_stylesheet == $stylesheet) {
                    $version_in_current = $wp_theme_ver;
                    $wp_exist           = true;
                }
            }

            // Install theme
            if ($wp_exist === false) {
                // Install Theme
                self::runInstallTheme($theme_root, $stylesheet, $version);

                //Add Log
                if (isset($args['log']) and $args['log'] === true) {
                    \WP_CLI_Helper::pl_wait_end();
                    Package_Install::add_detail_log(Package::_e('package', 'manage_item', array("[work]" => "Added", "[slug]" => $stylesheet . (($version != "latest" and \WP_CLI_Util::is_semver_version($version) === true) ? ' ' . \WP_CLI_Helper::color("v" . $version, "P") : ''), "[type]" => "theme", "[more]" => "")));
                    \WP_CLI_Helper::pl_wait_start();
                }
            } else {
                # Update Theme Process
                if ($version_in_current != $version_in_pkg || (Package_Update::isAutoUpdate() and $version_in_pkg == "latest")) {
                    $_show_update_log = false;

                    //Check theme from Url or WordPress
                    $is_pkg_url     = (\WP_CLI_Util::is_url($version_in_pkg) === false ? false : true);
                    $is_current_url = (\WP_CLI_Util::is_url($version_in_current) === false ? false : true);

                    //Get Current Version
                    $this_version = '';
                    if ( ! $is_pkg_url) {
                        $this_version = ($version_in_pkg == "latest" ? $themes_api->get_last_version_theme($stylesheet, true) : $version_in_pkg); //Check if last version
                    }

                    // Get Current Version this theme in WordPress
                    if ($version_in_current == "latest" and $version_in_pkg == "latest") {
                        $search_theme = self::searchWordPressThemes(array('search_by' => 'folder', 'search' => $stylesheet, 'first' => true));
                        if (isset($search_theme['version']) and ! empty($search_theme['version'])) {
                            $version_in_current = $search_theme['version'];
                        }
                    }

                    // 1) Check Version is Changed For Theme
                    if ( ! $is_pkg_url and ((empty($this_version) and $version_in_current != $version_in_pkg) || ( ! empty($this_version) and $version_in_current != $this_version))) {
                        //Check From URL or WordPress Theme
                        $prompt = $stylesheet;
                        if ($is_pkg_url === false and $version_in_pkg != "latest") {
                            $prompt .= ' --version=' . $this_version;
                        }

                        //Run Command
                        $cmd = "theme update {$prompt}";
                        \WP_CLI_Helper::run_command($cmd, array('exit_error' => true));

                        // show log
                        $_show_update_log = true;
                    }

                    // 2) Check User Changed Version to Custom URL
                    if (
                        ($is_current_url and ! $is_pkg_url)
                        ||
                        ( ! $is_current_url and $is_pkg_url)
                        ||
                        ($is_current_url and $is_pkg_url and $version_in_current != $version_in_pkg)
                    ) {
                        // First Delete Theme
                        self::runDeleteTheme($stylesheet);

                        // Second Download New Theme file
                        sleep(2);
                        self::runInstallTheme($theme_root, $stylesheet, ($is_pkg_url === false ? $this_version : $version_in_pkg));

                        // Third Activate if in WordPress
                        if ($Current_theme == $stylesheet) {
                            sleep(3);
                            $cmd = "theme activate  {$stylesheet}";
                            \WP_CLI_Helper::run_command($cmd, array('exit_error' => false));
                        }

                        // show log
                        $_show_update_log = true;
                    }

                    //Add Log
                    if (isset($args['log']) and $args['log'] and $_show_update_log) {
                        \WP_CLI_Helper::pl_wait_end();
                        Package_Install::add_detail_log(Package::_e('package', 'manage_item', array("[work]" => "Updated", "[slug]" => $stylesheet . (($version_in_pkg != "latest" and \WP_CLI_Util::is_semver_version($version_in_pkg) === true) ? ' ' . \WP_CLI_Helper::color("v" . $version_in_pkg, "P") : ''), "[type]" => "theme", "[more]" => "")));
                        \WP_CLI_Helper::pl_wait_start();
                    }
                }
            }
        }

        if (isset($args['log'])) {
            \WP_CLI_Helper::pl_wait_end();
        }
    }

    /**
     * Install Theme With WP-CLI
     *
     * @param $theme_root
     * @param $stylesheet
     * @param $version
     * @throws \WP_CLI\ExitException
     */
    public static function runInstallTheme($theme_root, $stylesheet, $version)
    {
        //Check theme from Url or WordPress
        $from_url = (\WP_CLI_Util::is_url($version) === false ? false : true);

        //Check From URL or WordPress theme
        if ($from_url === true) {
            # Theme From WordPress
            $prompt = $version;
        } else {
            # Theme from Source
            $prompt = $stylesheet;
            if ($version != "latest") {
                $prompt .= ' --version=' . $version;
            }
        }

        //Run Command
        $cmd = "theme install {$prompt} --force";
        \WP_CLI_Helper::run_command($cmd, array('exit_error' => true));

        //Sanitize Folder Theme
        if ($from_url === true and ! empty($theme_root)) {
            // Wait For Downloaded
            sleep(3);

            //Get Last Dir
            $last_dir = \WP_CLI_FileSystem::sort_dir_by_date($theme_root, "DESC");

            //Sanitize
            Plugins::sanitizeDirBySlug($stylesheet, \WP_CLI_FileSystem::path_join($theme_root, $last_dir[0]));
        }
    }

    /**
     * Uninstall Theme With WP-CLI
     *
     * @param $stylesheet
     */
    public static function runDeleteTheme($stylesheet)
    {
        $cmd = "theme delete {$stylesheet} --force";
        \WP_CLI_Helper::run_command($cmd, array('exit_error' => false));
    }

    /**
     * Switch theme in WordPress
     *
     * @param $stylesheet
     * @return array
     */
    public static function switch_theme($stylesheet)
    {
        //Get List exist theme
        $exist_list = self::evalGetThemesList();

        //Get Active theme
        $active_theme = self::evalGetCurrentTheme();

        //Check is active theme
        if ($stylesheet == $active_theme) {
            return array('status' => true, 'data' => Package::_e('package', 'is_now_theme_active', array("[stylesheet]" => $stylesheet)));
        }

        //Check exist theme stylesheet
        if ( ! in_array($stylesheet, $exist_list)) {
            return array('status' => false, 'data' => Package::_e('package', 'theme_not_found', array("[stylesheet]" => $stylesheet)));
        } else {
            //run switch theme
            \WP_CLI_Helper::run_command("theme activate {$stylesheet}", array('exit_error' => false));

            //log
            return array('status' => true, 'data' => Package::_e('package', 'switch_to_theme', array("[stylesheet]" => $stylesheet)));
        }
    }

    /**
     * Update Command config Theme
     *
     * @param $stylesheet
     * @throws \WP_CLI\ExitException
     */
    public static function updateSwitchTheme($stylesheet)
    {
        //Get Active theme
        $active_theme = self::evalGetCurrentTheme();

        // Check Default
        if ($stylesheet == "default") {
            $stylesheet = $active_theme;
        }

        // get Temp Package
        $tmp = Package_Temporary::getTemporaryFile();

        // Get Current theme status
        $tmp_theme = (isset($tmp['config']['theme']) ? $tmp['config']['theme'] : $active_theme);

        // If Not any change
        if ($tmp_theme == $stylesheet) {
            return;
        }

        // Switch Theme
        $switch = self::switch_theme($stylesheet);
        if ($switch['status'] === false) {
            \WP_CLI_Helper::error($switch['data'], true);
        }

        // it's better Flush after switch theme
        Permalink::runFlushRewriteCLI();

        // Return Data
        Package_Install::add_detail_log($switch['data']);
    }

    /**
     * Update Themes in Update Command
     *
     * @param $pkg_themes
     * @throws \WP_CLI\ExitException
     */
    public static function update($pkg_themes)
    {
        // Default
        if ($pkg_themes == "default") {
            $pkg_themes = array();
        }

        // get Temporary Package
        $tmp        = Package_Temporary::getTemporaryFile();
        $tmp_themes = (isset($tmp['themes']) ? $tmp['themes'] : array());

        // If Not any change
        if (($tmp_themes == $pkg_themes) and ! Package_Update::isAutoUpdate()) {
            return;
        }

        // Update Themes
        self::update_themes($pkg_themes, $tmp_themes, array('force' => true));
    }

}