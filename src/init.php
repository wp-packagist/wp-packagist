<?php

use WP_CLI_PACKAGIST\Package\Arguments\Core;
use WP_CLI_PACKAGIST\Package\Utility\Package_Temporary;
use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\Package_Create;

/**
 * Create a new WordPress package (wordpress.json) file.
 *
 * ## OPTIONS
 *
 * [--title=<site-title>]
 * : The title of the new site.
 *
 * [--url=<site-url>]
 * : The address of the new site.
 *
 * [--admin_user=<admin-user>]
 * : The name of the admin user.
 *
 * [--admin_email=<admin-email>]
 * : The email address for the admin user.
 *
 * [--admin_pass=<admin-password>]
 * : The password for the admin user.
 *
 * [--db_host=<mysql-server>]
 * : Database host server e.g localhost, 127.0.0.1.
 *
 * [--db_user=<database-username>]
 * : Database username.
 *
 * [--db_password=<database-password>]
 * : Mysql server password.
 *
 * [--db_name=<database-name>]
 * : Database name.
 *
 * [--table_prefix=<table-prefix>]
 * : Database table prefix.
 *
 * [--locale=<language>]
 * : Wordpress language.
 *
 * [--wp_content_dir=<wp-content>]
 * : wp-content dir path.
 *
 * [--plugins_dir=<plugins>]
 * : plugins dir path.
 *
 * [--uploads_dir=<uploads>]
 * : uploads dir path.
 *
 * [--themes_dir=<themes>]
 * : themes dir path.
 *
 * [--multisite]
 * : install WordPress Multisite.
 *
 * ## EXAMPLES
 *
 *      # Create a new WordPress package.
 *      $ wp init
 *      Success: Created WordPress package file.
 *
 * @when before_wp_load
 */
\WP_CLI::add_command('init', function ($args, $assoc_args) {
    //Load Package Class
    $pkg = new Package();

    //exist WordPress
    if (Core::check_wp_exist()) {
        \WP_CLI_Helper::error(Package::_e('package', 'exist_wp'));
        return;
    }

    //exist wordpress package file
    if ($pkg->exist_package_file()) {
        \WP_CLI_Helper::error(Package::_e('package', 'exist_pkg'));
        return;
    }

    //Get before Command
    $before_command = Package::get_command_log();

    // Force Only run With --Prompt
    if ( ! isset ($assoc_args['prompt']) and count($assoc_args) == 0) {
        if (empty($before_command) || (isset($before_command['command']) and $before_command['command'] != "init")) {
            Package::save_last_command('init', $args, $assoc_args);
            \WP_CLI::runcommand("init --prompt");
            return;
        }
    }

    //Remove command log
    Package::remove_command_log();

    //Create new package file
    $create_pkg = new Package_Create();
    $return     = $create_pkg->create($assoc_args);

    //Not Writable Error
    if ($return['status'] === false) {
        \WP_CLI_Helper::error($return['data']);
        return;
    }

    //Check Warning
    $warnings = $return['data'];

    //Show Warning
    if (count($warnings) > 0) {
        \WP_CLI_Helper::br();
        \WP_CLI_Helper::warning();
        foreach ($warnings as $text_warning) {
            \WP_CLI_Helper::line("  - " . $text_warning);
        }
        \WP_CLI_Helper::br();
    }

    //Remove Package LocalTemp
    Package_Temporary::removeTemporaryFile(\WP_CLI_Util::getcwd());

    //Show Success
    \WP_CLI_Helper::success(Package::_e('package', 'created'));
});