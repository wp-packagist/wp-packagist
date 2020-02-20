<?php

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\Package_Temporary;

/**
 * Uninstall WordPress Site.
 *
 * ## OPTIONS
 *
 * [--backup]
 * : Get Full Backup of WordPress Database and files before Uninstall Process.
 *
 * ## EXAMPLES
 *
 *      # Uninstall WordPress Site.
 *      $ wp uninstall
 *      Success: Completed uninstall WordPress.
 *
 *      # Get Full BackUp From WordPress Database and files, then uninstalled.
 *      $ wp uninstall --backup
 *      Success: Completed uninstall WordPress.
 *
 */
\WP_CLI::add_command('uninstall', function ($args, $assoc_args) {
    // Get Current WordPress.json Path
    $pkg_file = Package::get_config('package', 'file');

    // Backup name
    $sql = strtolower(DB_NAME) . ".sql";
    $zip = "backup-" . date("Y-m-d") . ".zip";

    // Check Number Step
    $num_step = 2;
    $step     = 1;
    if (isset($assoc_args['backup'])) {
        $num_step = 4;
    }

    // Show Confirm if Not Backup
    if ( ! isset($assoc_args['backup'])) {
        \WP_CLI_Helper::confirm("Are you sure you want to drop the database and all Wordpress files?");
    }

    // Create Backup before remove
    if (isset($assoc_args['backup'])) {
        // Get BackUp From Database
        \WP_CLI_Helper::pl_wait_start();
        \WP_CLI_Helper::run_command("db export " . $sql);
        \WP_CLI_Helper::pl_wait_end();
        \WP_CLI_Helper::line(\WP_CLI_Helper::color("Step {$step}/{$num_step}:", "Y") . " Created Backup Database " . \WP_CLI_Helper::color("[" . $sql . "]", "B") . ".");
        $step++;

        // Get BackUp WordPress files
        \WP_CLI_Helper::pl_wait_start();
        sleep(3);
        $create_zip = \WP_CLI_FileSystem::create_zip(
            array(
                'source'     => ABSPATH,
                'new_name'   => $zip,
                'baseFolder' => false,
                'except'     => array($sql, $pkg_file)
            )
        );
        if ($create_zip['status'] === false) {
            \WP_CLI_Helper::error($create_zip['message']);
        }
        \WP_CLI_Helper::pl_wait_end();
        \WP_CLI_Helper::line(\WP_CLI_Helper::color("Step {$step}/{$num_step}:", "Y") . " Created Backup WordPress files " . \WP_CLI_Helper::color("[" . $zip . "]", "B") . ".");
        $step++;
    }

    // Drop the Database
    \WP_CLI_Helper::pl_wait_start();
    \WP_CLI_Helper::run_command("db drop --yes");
    \WP_CLI_Helper::pl_wait_end();
    \WP_CLI_Helper::line(\WP_CLI_Helper::color("Step {$step}/{$num_step}:", "Y") . " Database dropped.");
    $step++;

    // Remove WordPress All Files
    \WP_CLI_Helper::pl_wait_start();
    $removed_wp_folder = \WP_CLI_FileSystem::remove_dir(ABSPATH, true, array($zip, $sql, $pkg_file));
    if ($removed_wp_folder['status'] === false) {
        \WP_CLI_Helper::pl_wait_end();
        \WP_CLI_Helper::error($removed_wp_folder['message']);
        return;
    }
    \WP_CLI_Helper::pl_wait_end();
    \WP_CLI_Helper::line(\WP_CLI_Helper::color("Step {$step}/{$num_step}:", "Y") . " Removed WordPress files.");

    //Remove Package LocalTemp
    Package_Temporary::removeTemporaryFile(\WP_CLI_Util::getcwd());

    // Show success
    \WP_CLI_Helper::success("Completed uninstall WordPress.");
});