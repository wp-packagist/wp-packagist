<?php

use WP_CLI_PACKAGIST\Package\Utility\temp;
use WP_CLI_PACKAGIST\Utility\PHP;

/**
 * Delete WordPress Site and Package.
 *
 * ## OPTIONS
 *
 * [--force]
 * : force remove.
 *
 * [--backup]
 * : get Backup before removing WordPress.
 *
 * ## DOCUMENT
 *
 *      https://wp-packagist.com/docs/uninstall
 *
 * ## EXAMPLES
 *
 *      # Delete WordPress Package.
 *      $ wp app install
 *      Success: Completed install WordPress.
 *
 * @when before_wp_load
 */
\WP_CLI::add_command( 'uninstall', function ( $args, $assoc_args ) {

	//Remove Package LocalTemp
	temp::remove_temp_file( PHP::getcwd() );


} );