<?php

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\Package_Temporary;
use WP_CLI_PACKAGIST\Package\Utility\Package_Update;
use WP_CLI_PACKAGIST\Package\Utility\Package_Validation;

/**
 * Update WordPress Package.
 *
 * ## EXAMPLES
 *
 *      # Update WordPress Package
 *      $ wp pack update
 *      Success: Updated WordPress.
 *
 */
\WP_CLI::add_command('update', function ($_, $assoc) {
    //Load Package Class
    $pkg = new Package();

    # Exist wordpress package file
    if ($pkg->exist_package_file() === false) {
        \WP_CLI_Helper::error(Package::_e('package', 'no_exist_pkg'), true);
    }

    # Check Temporary Package File
    $TemporaryPath = Package_Temporary::getTemporaryFilePath();
    if ( ! file_exists($TemporaryPath)) {
        \WP_CLI_Helper::error(Package::_e('package', 'not_find_temporary', array("[path]" => WP_CLI_PACKAGIST_HOME_PATH)), true);
    }

    # Set global run check
    $pkg->set_global_package_run_check();

    # Show Please Wait
    \WP_CLI_Helper::pl_wait_start(false);

    # Run Package Validation
    $validation_pkg = new Package_Validation();
    $get_pkg        = $validation_pkg->validation(true);
    if ($get_pkg['status'] === true) {
        $run = new Package_Update();
        $run->run($get_pkg['data']);
    }
});