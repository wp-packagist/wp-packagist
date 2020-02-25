<?php

namespace WP_CLI_PACKAGIST;

use WP_CLI_PACKAGIST\Package\Arguments\Core;
use WP_CLI_PACKAGIST\Package\Arguments\Permalink;
use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\Package_Help;
use WP_CLI_PACKAGIST\Package\Utility\Package_Validation;
use WP_CLI_PACKAGIST\Package\Utility\Package_View;

/**
 * Management WordPress Package.
 *
 * ## EXAMPLES
 *
 *      # Show Current WordPress Package
 *      $ wp pack show
 *
 *      # Check if your WordPress Package file is valid
 *      $ wp pack validate
 *      Success: WordPress Package file is valid.
 *
 *      # Remove WordPress Pack
 *      $ wp pack remove
 *      Success: Removed WordPress Package.
 *
 *     # Search `cms` in the /wp-content/test.php file and replace to `wordpress`
 *     $ wp pack tools find-replace /wp-content/test.php --find=cms --replace=wordpress
 *     Success: File placement successful.
 *
 *
 * @package wp-cli
 */
class Pack extends \WP_CLI_Command
{
    /**
     * The single instance of the class.
     */
    protected static $_instance = null;

    /**
     * WordPress Package bootstrap class.
     */
    private $package;

    /**
     * Main Instance.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Pack constructor.
     */
    public function __construct()
    {
        # Create new obj package class
        $this->package = new Package();
    }

    /**
     * Remove WordPress Package.
     *
     * ## OPTIONS
     *
     * [--force]
     * : Delete the file without question.
     *
     * ## EXAMPLES
     *
     *      # Remove WordPress Package
     *      $ wp pack remove
     *      Success: Removed WordPress Package file.
     *
     * @when before_wp_load
     */
    function remove($_, $assoc)
    {
        # Exist wordpress package file
        if ($this->package->exist_package_file() === false) {
            \WP_CLI_Helper::error(Package::_e('package', 'no_exist_pkg'));
        }

        # Confirm Remove WordPress Package
        if ( ! isset($assoc['force'])) {
            \WP_CLI_Helper::confirm(Package::_e('package', 'rm_pkg_confirm'));
        }

        # Run Remove Package file
        $this->package->remove_package_file();

        # Show Success
        \WP_CLI_Helper::success(Package::_e('package', 'remove_pkg'));
    }

    /**
     * Check if your WordPress Package file is valid.
     *
     * ## EXAMPLES
     *
     *      # Validation WordPress Package
     *      $ wp pack validate
     *      Success: WordPress Package is valid.
     *
     * @when before_wp_load
     */
    function validate($_, $assoc)
    {
        # Set global run check
        $this->package->set_global_package_run_check();

        # Show Please Wait
        \WP_CLI_Helper::pl_wait_start(false);

        # Run Package Validation
        $validation_pkg = new Package_Validation();
        $get_pkg        = $validation_pkg->validation($log = true);
        if ($get_pkg['status'] === true) {
            \WP_CLI_Helper::success(Package::_e('package', 'pkg_is_valid'));
        }
    }

    /**
     * Show WordPress Package file.
     *
     * ## EXAMPLES
     *
     *      # Show Current WordPress Package
     *      $ wp pack show
     *
     * @when before_wp_load
     */
    function show($_, $assoc)
    {
        # Show Local Package
        if ($this->package->exist_package_file() === false) {
            \WP_CLI_Helper::error(Package::_e('package', 'not_exist_pkg') . " " . Package::_e('package', 'create_new_pkg'));
        }

        # Show Please Wait
        \WP_CLI_Helper::pl_wait_start(false);

        # Run Package Validation
        $validation_pkg = new Package_Validation();
        $get_pkg        = $validation_pkg->validation($log = true);
        if ($get_pkg['status'] === true) {
            # View WordPress Package
            $view_pkg = new Package_View();
            $view_pkg->view($get_pkg['data'], false);
        }
    }

    /**
     * Show Documentation in the web browser.
     *
     * ## EXAMPLES
     *
     *      # Show WP-CLI PACKAGIST Documentation in the web browser.
     *      $ wp app docs
     *
     * @when before_wp_load
     * @alias doc
     */
    function docs($_, $assoc)
    {
        //Get basic docs url
        $url = Package::get_config('docs');

        //Check Valid Url
        $web_url = filter_var($url, FILTER_VALIDATE_URL);
        if ($web_url === false) {
            $web_url = Package::get_config('docs');
        }

        //Show in browser
        \WP_CLI_Helper::Browser($web_url);
    }

    /**
     * WordPress Package Helper.
     *
     * ## EXAMPLES
     *
     *      # WordPress Package Helper.
     *      $ wp pack help
     *
     * @alias helper
     * @when before_wp_load
     */
    function help($_, $assoc)
    {
        Package_Help::run();
    }

    /**
     * Launches system editor to edit the WordPress Package file.
     *
     * ## OPTIONS
     *
     * [--editor=<name>]
     * : Editor name.
     * ---
     * options:
     *   - notepad++
     *   - atom
     *   - vscode
     * ---
     *
     * ## EXAMPLES
     *
     *     # Launch system editor to edit wordpress.json file
     *     $ wp pack edit
     *
     *     # Edit wordpress.json file in a specific editor in macOS/linux
     *     $ EDITOR=vim wp pack edit
     *
     *     # Edit wordpress.json file in a specific editor in windows
     *     $ wp pack edit --editor=notepad++
     *
     * @when before_wp_load
     */
    public function edit($_, $assoc)
    {
        # Exist wordpress package file
        if ($this->package->exist_package_file() === false) {
            \WP_CLI_Helper::error(Package::_e('package', 'no_exist_pkg'));
        }

        # Lunch Editor
        \WP_CLI_Helper::lunch_editor($this->package->package_path, (isset($assoc['editor']) ? $assoc['editor'] : false));
    }

    /**
     * Tools commands for WordPress Package Management.
     *
     * ## OPTIONS
     *
     * <type>
     * : the type of Tools command.
     * ---
     * options:
     *   - find-replace
     *   - find-replace-files
     *   - find-replace-files-name
     * ---
     *
     * [<file>]
     * : the file path
     *
     * [--extension=<extension>]
     * : the file extensions that want to find and replace text e.g. php or js.
     *
     * [--find=<find>]
     * : Search in File.
     *
     * [--replace=<replace>]
     * : Replace in File.
     *
     * ## EXAMPLES
     *
     *     # Search `cms` in the /wp-content/test.php file and replace to `wordpress`
     *     $ wp pack tools find-replace /wp-content/test.php --find=cms --replace=wordpress
     *     Success: File placement successful.
     *
     * @when before_wp_load
     */
    public function tools($_, $assoc)
    {
        $command   = strtolower($_[0]);
        $find      = \WP_CLI_Helper::get_flag_value($assoc, 'find', null);
        $replace   = \WP_CLI_Helper::get_flag_value($assoc, 'replace', null);
        $extension = \WP_CLI_Helper::get_flag_value($assoc, 'extension', "php");

        switch ($command) {
            case "find-replace":
                // Check File Path
                if ( ! isset($_[1])) {
                    \WP_CLI_Helper::error("Please select a file.", true);
                }

                // File Path
                $file = \WP_CLI_FileSystem::path_join(\WP_CLI_Util::getcwd(), trim($_[1]));

                // Sanitize Input find and replace
                $inputs = self::SanitizeInput($find, $replace);

                // Start Search and Replace
                $_run = \WP_CLI_FileSystem::search_replace_file($file, $inputs['find'], $inputs['replace'], true);
                if ($_run['status'] === false) {
                    \WP_CLI_Helper::error($_run['message'], true);
                }

                \WP_CLI_Helper::success("File placement successful.");
                break;
            case "find-replace-files":
                // Check folder Path
                if ( ! isset($_[1])) {
                    \WP_CLI_Helper::error("Please select a dir path.", true);
                }

                // Sanitize Input find and replace
                $inputs = self::SanitizeInput($find, $replace);

                // Get List Files
                $file_lists_in_ext = self::getFileListInDirByExtension(trim($_[1]), $extension);

                // Start Search and Replace
                \WP_CLI_Helper::pl_wait_start();
                foreach ($file_lists_in_ext as $file_path) {
                    $_run = \WP_CLI_FileSystem::search_replace_file($file_path, $inputs['find'], $inputs['replace'], true);
                    if ($_run['status'] === false) {
                        \WP_CLI_Helper::error($_run['message'], true);
                    }
                }

                \WP_CLI_Helper::pl_wait_end();
                \WP_CLI_Helper::success("Completed process in the files.");
                break;
            case "find-replace-files-name":
                // Check folder Path
                if ( ! isset($_[1])) {
                    \WP_CLI_Helper::error("Please select a dir path.", true);
                }

                // Sanitize Input find and replace
                $inputs = self::SanitizeInput($find, $replace);

                // Get List Files
                $file_lists_in_ext = self::getFileListInDirByExtension(trim($_[1]), $extension);

                // Start Search and Replace
                \WP_CLI_Helper::pl_wait_start();
                foreach ($file_lists_in_ext as $file_path) {
                    // Get File Name
                    $dirPath     = dirname($file_path);
                    $before_name = basename($file_path);
                    $new_name    = str_replace($inputs['find'], $inputs['replace'], $before_name);

                    // Run Rename File
                    $_run = \WP_CLI_FileSystem::rename(\WP_CLI_FileSystem::path_join($dirPath, $before_name), \WP_CLI_FileSystem::path_join($dirPath, $new_name));
                    if ($_run === false) {
                        \WP_CLI_Helper::error($_run['message'], true);
                    }
                }

                \WP_CLI_Helper::pl_wait_end();
                \WP_CLI_Helper::success("Completed rename process.");
                break;
            default:
                // WP-CLI Error
        }
    }

    /**
     * Get List Files in the diy by Extensions
     *
     * @param $dir_path
     * @param $extension
     * @return array
     * @throws \WP_CLI\ExitException
     */
    private static function getFileListInDirByExtension($dir_path, $extension)
    {
        // Dir Path
        $dir = \WP_CLI_FileSystem::path_join(\WP_CLI_Util::getcwd(), trim($dir_path));

        // Exist dir
        if ( ! file_exists($dir)) {
            \WP_CLI_Helper::error("The dir path not found.", true);
        }

        //is dir
        if ( ! is_dir($dir)) {
            \WP_CLI_Helper::error("The path is not a dir.", true);
        }

        //is Writable
        if ( ! is_writable($dir)) {
            \WP_CLI_Helper::error("Permission denied.The '$dir' path not writable.", true);
        }

        // Get All List File From custom Extensions
        $list_files        = \WP_CLI_FileSystem::get_dir_contents($dir, true);
        $file_lists_in_ext = array();
        $extension         = ltrim($extension, ".");
        foreach ($list_files as $f) {
            if (is_dir($f)) {
                continue;
            }
            $file_ext = pathinfo($f, PATHINFO_EXTENSION);
            if ($file_ext != $extension) {
                continue;
            }
            if ( ! @is_writable($f)) {
                \WP_CLI_Helper::error("Permission denied.The '$f' file not writable.", true);
            }
            $file_lists_in_ext[] = $f;
        }

        // Check Not Found any File
        if (empty($file_lists_in_ext)) {
            \WP_CLI_Helper::error("Not Found any `.{$extension}` files in this dir.", true);
        }

        return $file_lists_in_ext;
    }

    /**
     * Sanitize find and Replace
     *
     * @param $find
     * @param $replace
     * @return array
     * @throws \WP_CLI\ExitException
     */
    private static function SanitizeInput($find, $replace)
    {
        if (is_null($find)) {
            \WP_CLI_Helper::error("Please fill --find and --replace flag.", true);
        }
        return array('find' => array_filter(explode(",", $find)), 'replace' => array_filter(explode(",", $replace)));
    }

    /**
     * Create Htaccess or Web.config For Pretty Permalink WordPress.
     *
     * ## OPTIONS
     *
     * [--wp_content=<wp-content>]
     * : wp-content dir path.
     *
     * [--plugins=<plugins>]
     * : plugins dir path.
     *
     * [--uploads=<uploads>]
     * : uploads dir path.
     *
     * [--themes=<themes>]
     * : themes dir path.
     *
     * ## EXAMPLES
     *
     *     $ wp pack htaccess
     *
     * @alias webconfig
     */
    function htaccess($_, $assoc)
    {
        //Check Network
        $network = Core::is_multisite();

        //Check Custom directory
        $dirs = array();
        foreach (array('wp_content', 'plugins', 'uploads', 'themes') as $dir) {
            if (isset($assoc[$dir])) {
                $dirs[$dir] = $assoc[$dir];
            }
        }

        //Create file
        Permalink::create_permalink_file($network['network'], $network['subdomain'], $dirs);

        //Success
        \WP_CLI_Helper::success(Package::_e('package', 'created_file', array("[file]" => $network['mod_rewrite_file'])));
    }
}