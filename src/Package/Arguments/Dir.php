<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\Package_Install;
use WP_CLI_PACKAGIST\Package\Utility\Package_Temporary;

class Dir
{
    /**
     * List of dir that create in wp-content
     * @see https://codex.wordpress.org/Determining_Plugin_and_Content_Directories
     *
     * @var array
     */
    public static $dirs = array('uploads', 'mu-plugins', 'languages');

    /**
     * Default Dir Constant
     */
    public static function getDefaultDirConstant()
    {
        return array(
            'WP_HOME',
            'WP_SITEURL',
            'WP_CONTENT_FOLDER',
            'WP_CONTENT_DIR',
            'WP_CONTENT_URL',
            'WP_PLUGIN_DIR',
            'PLUGINDIR',
            'WP_PLUGIN_URL',
            'UPLOADS'
        );
    }

    /**
     * Create Directory in wp-content
     */
    public static function createExtraFolderInWPContent()
    {
        $wp_content = \WP_CLI_Util::getcwd('wp-content');
        foreach (self::$dirs as $folder) {
            if (\WP_CLI_FileSystem::folder_exist(\WP_CLI_FileSystem::path_join($wp_content, $folder)) === false) {
                \WP_CLI_FileSystem::create_dir($folder, $wp_content);
            }
        }
    }

    /**
     * Get MU-PLUGINS path
     *
     * @return mixed
     */
    public static function eval_get_mu_plugins_path()
    {
        return \WP_CLI::runcommand('eval "if(defined(\'WPMU_PLUGIN_DIR\')) { echo WPMU_PLUGIN_DIR; } else { echo \'\'; }"', array('return' => 'stdout'));
    }

    /**
     * Get mu-plugin path
     *
     * @param $pkg_array
     * @return bool|string
     */
    public static function get_mu_plugins_path($pkg_array)
    {
        //Get wp-content path
        $wp_content = 'wp-content';
        if (isset($pkg_array['dir']['wp-content'])) {
            $wp_content = $pkg_array['dir']['wp-content'];
        }

        //Get Mu Plugins Path
        return \WP_CLI_FileSystem::path_join(\WP_CLI_Util::getcwd(), \WP_CLI_FileSystem::path_join($wp_content, 'mu-plugins'));
    }

    /**
     * Update WordPress Package DIR
     *
     * @param $params
     * @param $dir
     * @param $pkg_array
     * @param string $step | (install or update)
     * @param array $temporary_dir
     */
    public static function updateDir($params, $dir, $pkg_array, $step = 'install', $temporary_dir = array())
    {
        //Load Wp-config Transform
        $config_transformer = Config::get_config_transformer();

        // Set Null if Not Found
        foreach ($params as $folder) {
            if ( ! array_key_exists($folder, $dir)) {
                $dir[$folder] = null;
            }
        }

        //WP_SITE Constant
        self::update_site_constant($dir, $pkg_array['config']['url'], $config_transformer);

        //Load Methods
        foreach ($params as $folder) {
            $method_name = 'change_' . str_replace("-", "_", $folder) . '_folder';
            self::{$method_name}($dir, $config_transformer, true, $step, $temporary_dir);
        }
    }

    /**
     * Update Command Package
     *
     * @param $pkg
     * @throws \WP_CLI\ExitException
     */
    public static function updateCommand($pkg)
    {
        // Get Package Dir
        $pkg_dir = array();
        if (isset($pkg['dir'])) {
            $pkg_dir = $pkg['dir'];
        }

        // get Temp Package
        $tmp = Package_Temporary::getTemporaryFile();

        // Get Current From Temporary
        $tmp_dir = (isset($tmp['dir']) ? $tmp['dir'] : array());

        // If Not any change
        if ($tmp_dir == $pkg_dir) {
            return;
        }

        // Run Update
        self::updateDir(array('wp-content', 'plugins', 'themes', 'uploads'), $pkg_dir, $pkg, 'update', $tmp_dir);
    }

    /**
     * Add Site Url constant
     *
     * @param $dir
     * @param $site_url
     * @param $wp_config
     */
    public static function update_site_constant($dir, $site_url, $wp_config)
    {
        //Sanitize $site url
        $site_url = rtrim($site_url, "/");

        //List Constant
        $list = array('WP_HOME', 'WP_SITEURL');

        //Check exist dir
        if (count($dir) > 0 and ! empty($site_url) and ((isset($dir['wp-content']) and ltrim($dir['wp-content'], "/") != "wp-content") || (isset($dir['plugins']) and ltrim($dir['plugins'], "/") != "plugins"))) {
            foreach ($list as $const) {
                $wp_config->update('constant', $const, $site_url, array('raw' => false, 'normalize' => true));
            }
        } else {
            // Dont Remove if Exist WP-Content
            if ($wp_config->exists('constant', 'WP_CONTENT_FOLDER')) {
                return;
            }
            foreach ($list as $const) {
                $wp_config->remove('constant', $const);
            }
        }
    }

    /**
     * Get wp_content Dir Path
     */
    public static function get_content_dir()
    {
        if ( ! defined('WP_CONTENT_DIR')) {
            return \WP_CLI_FileSystem::path_join(\WP_CLI_Util::getcwd(), 'wp-content');
        } else {
            return \WP_CLI_FileSystem::normalize_path(WP_CONTENT_DIR);
        }
    }

    /**
     * Change wp-content Folder
     *
     * @param $dir
     * @param $wp_config
     * @param bool $log
     * @param string $step
     * @param array $temporary_dir
     * @throws \WP_CLI\ExitException
     */
    public static function change_wp_content_folder($dir, $wp_config, $log = false, $step = 'install', $temporary_dir = array())
    {
        //Get base wp-content path
        $base_path = rtrim(\WP_CLI_FileSystem::path_join(getcwd(), 'wp-content'), "/");
        if ($step == "update") {
            $base_path = \WP_CLI_FileSystem::path_join(ABSPATH, 'wp-content');
            if ( ! is_null($dir['wp-content'])) {
                $base_path = \WP_CLI_FileSystem::path_join(ABSPATH, ltrim($dir['wp-content'], "/"));
            }
        }

        //Get current wp-content path
        $current_path = rtrim(self::get_content_dir(), "/") . "/";

        //Check changed wp-content dir
        $is_change = false;

        // Check Added Constant For Plugins Or Uploads in install
        $constants_list = array('WP_CONTENT_FOLDER', 'WP_CONTENT_DIR', 'WP_CONTENT_URL', 'WP_HOME', 'WP_SITEURL');
        if ( ! is_null($dir['plugins']) || ! is_null($dir['uploads']) || ! is_null($dir['wp-content'])) {
            self::updateWPContentConstant($wp_config, (is_null($dir['wp-content']) ? 'wp-content' : $dir['wp-content']));
        } else {
            foreach ($constants_list as $const) {
                $wp_config->remove('constant', $const);
            }
        }

        // Check Must Changed in Update Command
        if ($step == "update" and rtrim($base_path, "/") == rtrim($current_path, "/")) {
            return;
        }

        //Check if null value (Reset to Default)
        if (is_null($dir['wp-content'])) {
            if (rtrim($base_path, "/") != rtrim($current_path, "/") and $step != 'install') {
                $is_change = true;

                // Move or Rename Dir
                self::moveDir($current_path, $base_path);

                // First Remove Constant
                // We Dont Remove Because Plugins or Uploads Used this Constant
                if ( ! is_null($dir['plugins']) || ! is_null($dir['uploads'])) {
                    self::updateWPContentConstant($wp_config, 'wp-content');
                }

                // Set Log
                $from_path_log = $current_path;
                $to_path_log   = $base_path;
            }
        } else {
            //New Path
            $new_path = rtrim(\WP_CLI_FileSystem::path_join(getcwd(), trim($dir['wp-content'], "/")), "/") . "/";

            // New Path in Update command
            if ($step == "update") {
                $new_path = $base_path;
            }

            // Set Log
            $from_path_log = $current_path;
            $to_path_log   = $new_path;

            // run move
            if (rtrim($new_path, "/") != rtrim($current_path, "/")) {
                $is_change = true;

                //Move Folder
                self::moveDir($current_path, $new_path);

                //Add Constant
                self::updateWPContentConstant($wp_config, $dir['wp-content']);
            }
        }

        //Add Log
        if ($log and $is_change) {
            if ($step == "install") {
                Package_Install::add_detail_log(Package::_e('package', 'change_custom_folder', array("[folder]" => "wp-content")));
            } else {
                Package_Install::add_detail_log(Package::_e('package', 'update_dir_path', array("[dir]" => "wp-content", "[from]" => str_replace(ABSPATH, "", "/" . trim($from_path_log, "/")), "[to]" => str_replace(ABSPATH, "", "/" . trim($to_path_log, "/")))));

                # Update Database Attachment Link
                $site_url   = Core::get_site_url();
                $before_url = rtrim(content_url(), "/") . "/";
                $after_url  = rtrim($site_url, "/") . "/" . trim($dir['wp-content'], "/") . "/";
                // If Uploads Folder is Changed We cancel this process and run into changed_uploads_folder method
                $tmp_uploads = (isset($temporary_dir['uploads']) ? $temporary_dir['uploads'] : null);
                if ($before_url != $after_url and (trim($dir['uploads'], "/") == trim($tmp_uploads, "/"))) {
                    \WP_CLI_Helper::pl_wait_start();
                    \WP_CLI_Helper::search_replace_db($before_url, $after_url);
                    \WP_CLI_Helper::pl_wait_end();
                    Package_Install::add_detail_log(Package::_e('package', 'srdb_uploads'));
                }
            }
        }
    }

    /**
     * Update Wp-Content dir constant
     * 
     * @param $wp_config
     * @param $dirName
     */
    public static function updateWPContentConstant($wp_config, $dirName)
    {
        $wp_config->update('constant', 'WP_CONTENT_FOLDER', trim($dirName, "/"), array('raw' => false, 'normalize' => true));
        $wp_config->update('constant', 'WP_CONTENT_DIR', 'ABSPATH . WP_CONTENT_FOLDER', array('raw' => true, 'normalize' => true));
        $wp_config->update('constant', 'WP_CONTENT_URL', "WP_SITEURL . '/' . WP_CONTENT_FOLDER", array('raw' => true, 'normalize' => true));
    }

    /**
     * Get Plugin Dir Path
     */
    public static function get_plugins_dir()
    {
        if ( ! defined('WP_PLUGIN_DIR')) {
            return \WP_CLI_FileSystem::path_join(getcwd(), 'wp-content/plugins');
        } else {
            return \WP_CLI_FileSystem::normalize_path(WP_PLUGIN_DIR);
        }
    }

    /**
     * Change plugins Folder
     *
     * @param $dir
     * @param $wp_config
     * @param bool $log
     * @param string $step
     * @param array $temporary_dir
     * @throws \WP_CLI\ExitException
     */
    public static function change_plugins_folder($dir, $wp_config, $log = false, $step = 'install', $temporary_dir = array())
    {
        // Get WP-Content Dir pkg
        $wp_content = $wp_content_tmp = 'wp-content';
        $plugins    = $plugins_tmp = 'plugins';
        if ( ! is_null($dir['wp-content'])) {
            $wp_content = $dir['wp-content'];
        }
        if ( ! is_null($dir['plugins'])) {
            $plugins = $dir['plugins'];
        }
        if (isset($temporary_dir['wp-content'])) {
            $wp_content_tmp = $temporary_dir['wp-content'];
        }
        if (isset($temporary_dir['plugins'])) {
            $plugins_tmp = $temporary_dir['plugins'];
        }

        //Get base plugins path
        $base_path = rtrim(\WP_CLI_FileSystem::path_join(getcwd(), 'wp-content/plugins'), "/");
        if ($step == "update") {
            $base_path = \WP_CLI_FileSystem::path_join(ABSPATH, \WP_CLI_FileSystem::path_join($wp_content, ltrim('plugins', "/")));
            if ( ! is_null($dir['plugins'])) {
                $first_character = substr($dir['plugins'], 0, 1);
                if ($first_character == "/") {
                    $base_path = \WP_CLI_FileSystem::path_join(ABSPATH, ltrim($dir['plugins'], "/"));
                } else {
                    $base_path = \WP_CLI_FileSystem::path_join(ABSPATH, \WP_CLI_FileSystem::path_join($wp_content, ltrim($dir['plugins'], "/")));
                }
            }
        }

        //Get current plugins path
        $current_path = rtrim(self::get_plugins_dir(), "/") . "/";
        if ($step == "update" and ltrim($wp_content_tmp, "/") != ltrim($wp_content, "/")) {
            // Changed Constant If Only Change WP-content Constant
            if ( ! is_null($dir['plugins'])) {
                self::updatePluginsConstant($wp_config, (is_null($dir['plugins']) ? 'plugins' : $dir['plugins']), (is_null($dir['wp-content']) ? 'wp-content' : $dir['wp-content']));
            }

            if (substr($dir['plugins'], 0, 1) != "/" and rtrim($plugins_tmp, "/") == rtrim($plugins, "/")) {
                return;
            } else {
                if (substr($plugins_tmp, 0, 1) == "/") {
                    $current_path = \WP_CLI_FileSystem::path_join(ABSPATH, ltrim($plugins_tmp, "/"));
                } else {
                    $current_path = \WP_CLI_FileSystem::path_join(ABSPATH, \WP_CLI_FileSystem::path_join($wp_content, ltrim($plugins_tmp, "/")));
                }
            }
        }

        //Check changed wp-content dir
        $is_change = false;

        // Check Must Changed in Update Command
        if ($step == "update" and rtrim($base_path, "/") == rtrim($current_path, "/")) {
            return;
        }

        //constant list
        $constants_list = array('WP_PLUGIN_DIR', 'PLUGINDIR', 'WP_PLUGIN_URL');
        if ( ! is_null($dir['plugins'])) {
            self::updatePluginsConstant($wp_config, $dir['plugins'], (is_null($dir['wp-content']) ? 'wp-content' : $dir['wp-content']));
        } else {
            foreach ($constants_list as $const) {
                if ($wp_config->exists('constant', $const)) {
                    $wp_config->remove('constant', $const);
                }
            }
        }

        //Check if null value (Reset to Default)
        if (is_null($dir['plugins'])) {
            if (rtrim($base_path, "/") != rtrim($current_path, "/") and $step != 'install') {
                $is_change = true;

                //Move Folder
                self::moveDir($current_path, $base_path);

                // Set Log
                $from_path_log = $current_path;
                $to_path_log   = $base_path;
            }
        } else {
            //Get first Character (check in wp-content)
            $first_character = substr($dir['plugins'], 0, 1);

            //Old Path
            $old_path = $current_path;
            if ($step == "install") {
                $old_path = \WP_CLI_FileSystem::path_join(getcwd(), \WP_CLI_FileSystem::path_join($wp_content, 'plugins'));
            }

            //New Path
            if ($first_character == "/") {
                $new_path = \WP_CLI_FileSystem::path_join(getcwd(), ltrim($dir['plugins'], "/"));
            } else {
                $new_path = \WP_CLI_FileSystem::path_join(getcwd(), \WP_CLI_FileSystem::path_join($wp_content, ltrim($dir['plugins'], "/")));
            }

            // New Path in Update command
            if ($step == "update") {
                $new_path = $base_path;
            }

            // Set Log
            $from_path_log = $old_path;
            $to_path_log   = $new_path;

            // Run
            if (rtrim($new_path, "/") != rtrim($current_path, "/")) {
                $is_change = true;

                //Move Folder
                self::moveDir($old_path, $new_path);

                //Get Path and URL for Constant
                self::updatePluginsConstant($wp_config, $dir['plugins'], $dir['wp-content']);
            }
        }

        //Add Log
        if ($log and $is_change) {
            if ($step == "install") {
                Package_Install::add_detail_log(Package::_e('package', 'change_custom_folder', array("[folder]" => "plugins")));
            } else {
                Package_Install::add_detail_log(Package::_e('package', 'update_dir_path', array("[dir]" => "plugins", "[from]" => str_replace(ABSPATH, "", "/" . trim($from_path_log, "/")), "[to]" => str_replace(ABSPATH, "", "/" . trim($to_path_log, "/")))));
            }
        }
    }

    /**
     * Update Plugins Dir Constant
     *
     * @param $wp_config
     * @param $dirName
     * @param $wp_content
     */
    public static function updatePluginsConstant($wp_config, $dirName, $wp_content)
    {
        $first_character = substr($dirName, 0, 1);
        if ($first_character == "/") {
            $constant_path = "ABSPATH . '" . ltrim($dirName, "/") . "'";
            $constant_url  = "WP_SITEURL . '/" . ltrim($dirName, "/") . "'";
        } else {
            if (is_null($wp_content)) {
                $constant_path = "'wp-content/" . ltrim($dirName, "/") . "'";
                $constant_url  = "WP_SITEURL . '/wp-content/" . ltrim($dirName, "/") . "'";
            } else {
                $constant_path = "WP_CONTENT_DIR . '/" . ltrim($dirName, "/") . "'";
                $constant_url  = "WP_CONTENT_URL . '/" . ltrim($dirName, "/") . "'";
            }
        }

        //Add Constant
        $wp_config->update('constant', 'WP_PLUGIN_DIR', $constant_path, array('raw' => true, 'normalize' => true));
        $wp_config->update('constant', 'PLUGINDIR', $constant_path, array('raw' => true, 'normalize' => true));
        $wp_config->update('constant', 'WP_PLUGIN_URL', $constant_url, array('raw' => true, 'normalize' => true));
    }

    /**
     * Get themes Dir Path
     */
    public static function get_themes_dir()
    {
        if ( ! function_exists('get_theme_root')) {
            return \WP_CLI_FileSystem::path_join(getcwd(), 'wp-content/themes');
        } else {
            return \WP_CLI_FileSystem::normalize_path(get_theme_root());
        }
    }

    /**
     * Change themes Folder
     *
     * @param $dir
     * @param array $wp_config
     * @param bool $log
     * @param string $step
     * @param array $temporary_dir
     * @throws \WP_CLI\ExitException
     */
    public static function change_themes_folder($dir, $wp_config = array(), $log = false, $step = 'install', $temporary_dir = array())
    {
        // Get WP-Content Dir pkg
        $wp_content = $wp_content_tmp = 'wp-content';
        $themes     = $themes_tmp = 'themes';
        if ( ! is_null($dir['wp-content'])) {
            $wp_content = $dir['wp-content'];
        }
        if ( ! is_null($dir['themes'])) {
            $themes = $dir['themes'];
        }
        if (isset($temporary_dir['wp-content'])) {
            $wp_content_tmp = $temporary_dir['wp-content'];
        }
        if (isset($temporary_dir['themes'])) {
            $themes_tmp = $temporary_dir['themes'];
        }

        //Get base themes path
        $base_path = rtrim(\WP_CLI_FileSystem::path_join(getcwd(), 'wp-content/themes'), "/");
        if ($step == "update") {
            $base_path = \WP_CLI_FileSystem::path_join(ABSPATH, \WP_CLI_FileSystem::path_join($wp_content, ltrim('themes', "/")));
            if ( ! is_null($dir['themes'])) {
                $first_character = substr($dir['themes'], 0, 1);
                if ($first_character == "/") {
                    $base_path = \WP_CLI_FileSystem::path_join(ABSPATH, ltrim($dir['themes'], "/"));
                } else {
                    $base_path = \WP_CLI_FileSystem::path_join(ABSPATH, \WP_CLI_FileSystem::path_join($wp_content, ltrim($dir['themes'], "/")));
                }
            }
        }

        //Get current themes path
        $current_path = rtrim(self::get_themes_dir(), "/") . "/";
        if ($step == "update" and ltrim($wp_content_tmp, "/") != ltrim($wp_content, "/")) {
            if (substr($dir['themes'], 0, 1) != "/" and rtrim($themes_tmp, "/") == rtrim($themes, "/")) {
                return;
            } else {
                if (substr($themes_tmp, 0, 1) == "/") {
                    $current_path = \WP_CLI_FileSystem::path_join(ABSPATH, ltrim($themes_tmp, "/"));
                } else {
                    $current_path = \WP_CLI_FileSystem::path_join(ABSPATH, \WP_CLI_FileSystem::path_join($wp_content, ltrim($themes_tmp, "/")));
                }
            }
        }

        //Check changed themes dir
        $is_change = false;

        // Check Must Changed in Update Command
        if ($step == "update" and rtrim($base_path, "/") == rtrim($current_path, "/")) {
            return;
        }

        //Get Mu Plugins Path
        $mu_plugins_path = \WP_CLI_FileSystem::path_join(getcwd(), \WP_CLI_FileSystem::path_join($wp_content, 'mu-plugins'));

        //mu-plugins theme-dir path
        $themes_mu_plugins = \WP_CLI_FileSystem::path_join($mu_plugins_path, 'theme-dir.php');

        //Check if null value (Reset to Default)
        if (is_null($dir['themes'])) {
            if (rtrim($base_path, "/") != rtrim($current_path, "/") and $step != 'install') {
                $is_change = true;

                //Remove Mu-plugins
                if (file_exists($themes_mu_plugins)) {
                    \WP_CLI_FileSystem::remove_file($themes_mu_plugins);
                }

                //Move Folder
                self::moveDir($current_path, $base_path);

                // Set Log
                $from_path_log = $current_path;
                $to_path_log   = $base_path;
            }
        } else {
            //Get first Character (check in wp-content)
            $first_character = substr($dir['themes'], 0, 1);

            //Old Path
            $old_path = $current_path;
            if ($step == "install") {
                $old_path = \WP_CLI_FileSystem::path_join(getcwd(), \WP_CLI_FileSystem::path_join($wp_content, 'themes'));
            }

            //New Path
            if ($first_character == "/") {
                $new_path = \WP_CLI_FileSystem::path_join(getcwd(), ltrim($dir['themes'], "/"));
            } else {
                $new_path = \WP_CLI_FileSystem::path_join(getcwd(), \WP_CLI_FileSystem::path_join($wp_content, ltrim($dir['themes'], "/")));
            }

            // New Path in Update command
            if ($step == "update") {
                $new_path = $base_path;
            }

            // Set Log
            $from_path_log = $old_path;
            $to_path_log   = $new_path;

            if (rtrim($new_path, "/") != rtrim($current_path, "/")) {
                $is_change = true;

                //Move Folder
                self::moveDir($old_path, $new_path);

                //Get Path and URL for Constant
                if ($first_character == "/") {
                    $theme_directory     = $theme_directory_path = "ABSPATH . '" . ltrim($dir['themes'], "/") . "'";
                    $theme_directory_url = "home_url( '" . ltrim($dir['themes'], "/") . "' )";
                } else {
                    $theme_directory      = "'/" . ltrim($dir['themes'], "/") . "'";
                    $theme_directory_path = "WP_CONTENT_DIR . '/" . ltrim($dir['themes'], "/") . "'";
                    $theme_directory_url  = "content_url( '" . ltrim($dir['themes'], "/") . "' )";
                }

                //Upload Mu-Plugins
                $mustache = \WP_CLI_FileSystem::load_mustache(WP_CLI_PACKAGIST_TEMPLATE_PATH);
                \WP_CLI_FileSystem::file_put_content(
                    $themes_mu_plugins,
                    $mustache->render('mu-plugins/theme-dir', array(
                        'theme_directory'      => $theme_directory,
                        'theme_directory_path' => $theme_directory_path,
                        'theme_directory_url'  => $theme_directory_url,
                    ))
                );
            }
        }

        //Add Log
        if ($log and $is_change) {
            if ($step == "install") {
                Package_Install::add_detail_log(Package::_e('package', 'change_custom_folder', array("[folder]" => "themes")));
            } else {
                Package_Install::add_detail_log(Package::_e('package', 'update_dir_path', array("[dir]" => "themes", "[from]" => str_replace(ABSPATH, "", "/" . trim($from_path_log, "/")), "[to]" => str_replace(ABSPATH, "", "/" . trim($to_path_log, "/")))));
            }
        }
    }

    /**
     * Get Uploads Dir Path
     */
    public static function get_uploads_dir()
    {
        if ( ! function_exists('wp_upload_dir')) {
            return \WP_CLI_FileSystem::path_join(\WP_CLI_Util::getcwd(), 'wp-content/uploads');
        } else {
            $upload_dir = wp_upload_dir(null, false);
            return \WP_CLI_FileSystem::normalize_path($upload_dir['basedir']);
        }
    }

    /**
     * Get Uploads Dir Url
     *
     * @run after_wp_load
     * @param string $what
     * @return mixed
     */
    public static function get_wp_uploads($what = 'baseurl')
    {
        $uploads = \WP_CLI::runcommand('eval "echo json_encode( wp_upload_dir(null, false) );"', array('return' => 'stdout', 'parse' => 'json'));
        return rtrim(\WP_CLI_Util::backslash_to_slash($uploads[$what]), "/");
    }

    /**
     * Change uploads Folder
     *
     * @param $dir
     * @param $wp_config
     * @param bool $log
     * @param string $step
     * @param array $temporary_dir
     * @throws \WP_CLI\ExitException
     */
    public static function change_uploads_folder($dir, $wp_config, $log = false, $step = 'install', $temporary_dir = array())
    {
        // Get WP-Content Dir pkg
        $wp_content = $wp_content_tmp = 'wp-content';
        $uploads    = $uploads_tmp = 'uploads';
        if ( ! is_null($dir['wp-content'])) {
            $wp_content = $dir['wp-content'];
        }
        if ( ! is_null($dir['uploads'])) {
            $uploads = $dir['uploads'];
        }
        if (isset($temporary_dir['wp-content'])) {
            $wp_content_tmp = $temporary_dir['wp-content'];
        }
        if (isset($temporary_dir['uploads'])) {
            $uploads_tmp = $temporary_dir['uploads'];
        }

        //Get first Character (check in wp-content)
        $first_character = substr($dir['uploads'], 0, 1);

        //Get base uploads path
        $base_path = rtrim(\WP_CLI_FileSystem::path_join(getcwd(), 'wp-content/uploads'), "/");
        if ($step == "update") {
            $base_path = \WP_CLI_FileSystem::path_join(ABSPATH, \WP_CLI_FileSystem::path_join($wp_content, ltrim('uploads', "/")));
            if ( ! is_null($dir['uploads'])) {
                if ($first_character == "/") {
                    $base_path = \WP_CLI_FileSystem::path_join(ABSPATH, ltrim($dir['uploads'], "/"));
                } else {
                    $base_path = \WP_CLI_FileSystem::path_join(ABSPATH, \WP_CLI_FileSystem::path_join($wp_content, ltrim($dir['uploads'], "/")));
                }
            }
        }

        //Get current uploads path
        $current_path = rtrim(self::get_uploads_dir(), "/") . "/";
        if ($step == "update" and ltrim($wp_content_tmp, "/") != ltrim($wp_content, "/")) {
            // Changed Constant If Only Change WP-content Constant
            if ( ! is_null($dir['uploads'])) {
                self::updateUploadsConstant($wp_config, (is_null($dir['uploads']) ? 'uploads' : $dir['uploads']), (is_null($dir['wp-content']) ? 'wp-content' : $dir['wp-content']));
            }

            if (substr($dir['uploads'], 0, 1) != "/" and rtrim($uploads_tmp, "/") == rtrim($uploads, "/")) {
                return;
            } else {
                if (substr($uploads_tmp, 0, 1) == "/") {
                    $current_path = \WP_CLI_FileSystem::path_join(ABSPATH, ltrim($uploads_tmp, "/"));
                } else {
                    $current_path = \WP_CLI_FileSystem::path_join(ABSPATH, \WP_CLI_FileSystem::path_join($wp_content, ltrim($uploads_tmp, "/")));
                }
            }
        }

        //Check changed wp-content dir
        $is_change = false;

        // Check Must Changed in Update Command
        if ($step == "update" and rtrim($base_path, "/") == rtrim($current_path, "/")) {
            return;
        }

        //constant list
        $constants_list = array('UPLOADS');
        if ( ! is_null($dir['uploads'])) {
            self::updateUploadsConstant($wp_config, $dir['uploads'], (is_null($dir['wp-content']) ? 'wp-content' : $dir['wp-content']));
        } else {
            foreach ($constants_list as $const) {
                if ($wp_config->exists('constant', $const)) {
                    $wp_config->remove('constant', $const);
                }
            }
        }

        //Check if null value (Reset to Default)
        if (is_null($dir['uploads'])) {
            if (rtrim($base_path, "/") != rtrim($current_path, "/") and $step != 'install') {
                $is_change = true;

                //Move Folder
                self::moveDir($current_path, $base_path);

                // Set Log
                $from_path_log = $current_path;
                $to_path_log   = $base_path;
            }
        } else {
            //Old Path
            $old_path = $current_path;
            if ($step == "install") {
                $old_path = \WP_CLI_FileSystem::path_join(getcwd(), \WP_CLI_FileSystem::path_join($wp_content, 'uploads'));
            }

            //New Path
            if ($first_character == "/") {
                $new_path = \WP_CLI_FileSystem::path_join(getcwd(), ltrim($dir['uploads'], "/"));
            } else {
                $new_path = \WP_CLI_FileSystem::path_join(getcwd(), \WP_CLI_FileSystem::path_join($wp_content, ltrim($dir['uploads'], "/")));
            }

            // New Path in Update command
            if ($step == "update") {
                $new_path = $base_path;
            }

            // Set Log
            $from_path_log = $old_path;
            $to_path_log   = $new_path;

            // Run
            if (rtrim($new_path, "/") != rtrim($current_path, "/")) {
                $is_change = true;

                //Move Folder
                self::moveDir($old_path, $new_path);

                // Update Constant
                self::updateUploadsConstant($wp_config, $dir['uploads'], $dir['wp-content']);
            }
        }

        //Add Log
        if ($log and $is_change) {
            if ($step == "install") {
                Package_Install::add_detail_log(Package::_e('package', 'change_custom_folder', array("[folder]" => "uploads")));
            } else {
                Package_Install::add_detail_log(Package::_e('package', 'update_dir_path', array("[dir]" => "uploads", "[from]" => str_replace(ABSPATH, "", "/" . trim($from_path_log, "/")), "[to]" => str_replace(ABSPATH, "", "/" . trim($to_path_log, "/")))));

                # Update Database Attachment Link
                $site_url             = Core::get_site_url();
                $upload_dir           = wp_upload_dir(null, false);
                $before_url           = rtrim($upload_dir['baseurl'], "/") . "/";
                $content_dir          = (is_null($dir['wp-content']) ? 'wp-content' : $dir['wp-content']) . "/";
                $new_uploads_dir_name = (is_null($dir['uploads']) ? 'uploads' : trim($dir['uploads'], "/"));
                if ($first_character == "/") {
                    $new_upload_dir = $new_uploads_dir_name;
                } else {
                    $new_upload_dir = $content_dir . $new_uploads_dir_name;
                }
                $after_url = rtrim($site_url, "/") . "/" . rtrim($new_upload_dir, "/") . "/";
                if ($before_url != $after_url) {
                    \WP_CLI_Helper::pl_wait_start();
                    \WP_CLI_Helper::search_replace_db($before_url, $after_url);
                    \WP_CLI_Helper::pl_wait_end();
                    Package_Install::add_detail_log(Package::_e('package', 'srdb_uploads'));
                }
            }
        }
    }

    /**
     * Update Uploads dir Constant
     *
     * @param $wp_config
     * @param $dirName
     * @param $wp_content
     */
    public static function updateUploadsConstant($wp_config, $dirName, $wp_content)
    {
        $first_character = substr($dirName, 0, 1);
        if ($first_character == "/") {
            $constant_path = "''.'" . trim($dirName, "/") . "'";
        } else {
            if ( ! is_null($wp_content)) {
                $constant_path = "WP_CONTENT_FOLDER . '/" . trim($dirName, "/") . "'";
            } else {
                $constant_path = "'wp-content/" . trim($dirName, "/") . "'";
            }
        }

        $wp_config->update('constant', 'UPLOADS', $constant_path, array('raw' => true, 'normalize' => true));
    }

    /**
     * Move or Rename Directory
     *
     * @param $old_name
     * @param $new_name
     * @return bool
     * @throws \WP_CLI\ExitException
     */
    public static function moveDir($old_name, $new_name)
    {
        $targetDir = dirname($new_name);
        if ( ! file_exists($targetDir)) {
            if ( ! @mkdir($targetDir, 0777, true)) {
                $error = error_get_last();
                \WP_CLI::error("Failed to create directory '{$targetDir}': {$error['message']}.", true);
            }
        }
        $old_name = rtrim($old_name, "/");
        $new_name = rtrim($new_name, "/");
        if ( ! @rename($old_name, $new_name)) {
            \WP_CLI::error("Failed to " . (dirname($new_name) == dirname($old_name) ? 'rename' : 'move') . " directory '{$old_name}' to '{$new_name}'.", true);
        }
        sleep(2); //Pause for multiple move between wp-content and plugins/uploads ...
        return true;
    }
}