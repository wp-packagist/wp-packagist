<?php

use WP_CLI_PACKAGIST\Package\Utility\temp;

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
	temp::remove_temp_file( \WP_CLI_Util::getcwd() );
} );