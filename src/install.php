<?php

use WP_CLI_PACKAGIST\Package\Arguments\Core;
use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\install;
use WP_CLI_PACKAGIST\Package\Utility\validation;

/**
 * install WordPress Package.
 *
 * ## OPTIONS
 *
 * [--force]
 * : Remove all MySQL tables before installing if exist.
 *
 * ## EXAMPLES
 *
 *      # install WordPress Package.
 *      $ wp install
 *      Success: Completed install WordPress.
 *
 * @when before_wp_load
 */
\WP_CLI::add_command( 'install', function ( $args, $assoc_args ) {

	//Load Package Class
	$pkg = new Package();

	//exist WordPress
	if ( Core::check_wp_exist() ) {
		\WP_CLI_Helper::error( Package::_e( 'package', 'exist_wp' ) );
		return;
	}

	//exist wordpress package file
	if ( $pkg->exist_package_file() === false ) {
		\WP_CLI_Helper::error( Package::_e( 'package', 'not_exist_pkg' ) );
		return;
	}

	//Active Run Check Complete WordPress Package
	$pkg->set_global_package_run_check();

	//Check Force install
	if ( isset( $assoc_args['force'] ) ) {
		define( 'WP_CLI_APP_PACKAGE_FORCE_REMOVE_MYSQL_TABLE', true );
	}

	//Show Please Wait
	\WP_CLI_Helper::pl_wait_start( false );

	//Run Package Validation
	$valid_pkg = new validation();
	$json_pkg  = $valid_pkg->validation( $log = true );
	if ( $json_pkg['status'] === true ) {

		//Run install
		$pkg_install = new install();
		$pkg_install->install( $json_pkg['data'] );
	}
} );