<?php

namespace WP_CLI_PACKAGIST;

use WP_CLI_PACKAGIST\Package\Arguments\Core;
use WP_CLI_PACKAGIST\Package\Arguments\Permalink;
use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\help;
use WP_CLI_PACKAGIST\Package\Utility\update;
use WP_CLI_PACKAGIST\Package\Utility\validation;
use WP_CLI_PACKAGIST\Package\Utility\view;

/**
 * Management WordPress Package.
 *
 * ## EXAMPLES
 *
 *      # Show Current WordPress Package
 *      $ wp pack show
 *
 *      # Update WordPress Package
 *      $ wp pack update
 *      Success: Updated WordPress Package.
 *
 *      # Check if your WordPress Package file is valid
 *      $ wp pack validate
 *      Success: WordPress Package file is valid.
 *
 *      # Remove WordPress Pack
 *      $ wp pack remove
 *      Success: Removed WordPress Package.
 *
 *      # Export WordPress Package From Current WordPress Site
 *      $ wp pack export
 *      Success: Exported WordPress Package.
 *
 * @package wp-cli
 */
class Pack extends \WP_CLI_Command {
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
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Pack constructor.
	 */
	public function __construct() {

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
	function remove( $_, $assoc ) {

		# Exist wordpress package file
		if ( $this->package->exist_package_file() === false ) {
			\WP_CLI_Helper::error( Package::_e( 'package', 'no_exist_pkg' ) );
		}

		# Confirm Remove WordPress Package
		if ( ! isset( $assoc['force'] ) ) {
			\WP_CLI_Helper::confirm( Package::_e( 'package', 'rm_pkg_confirm' ) );
		}

		# Run Remove Package file
		$this->package->remove_package_file();

		# Show Success
		\WP_CLI_Helper::success( Package::_e( 'package', 'remove_pkg' ) );
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
	function validate( $_, $assoc ) {

		# Set global run check
		$this->package->set_global_package_run_check();

		# Show Please Wait
		\WP_CLI_Helper::pl_wait_start( false );

		# Run Package Validation
		$validation_pkg = new validation();
		$get_pkg        = $validation_pkg->validation( $log = true );
		if ( $get_pkg['status'] === true ) {
			\WP_CLI_Helper::success( Package::_e( 'package', 'pkg_is_valid' ) );
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
	function show( $_, $assoc ) {
		
		# Show Local Package
		if ( $this->package->exist_package_file() === false ) {
			\WP_CLI_Helper::error( Package::_e( 'package', 'not_exist_pkg' ) . " " . Package::_e( 'package', 'create_new_pkg' ) );
		}

		# Show Please Wait
		\WP_CLI_Helper::pl_wait_start( false );

		# Run Package Validation
		$validation_pkg = new validation();
		$get_pkg        = $validation_pkg->validation( $log = true );
		if ( $get_pkg['status'] === true ) {

			# View WordPress Package
			$view_pkg = new view();
			$view_pkg->view( $get_pkg['data'], false );
		}
	}

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
	function update( $_, $assoc ) {

		# Exist wordpress package file
		if ( $this->package->exist_package_file() === false ) {
			\WP_CLI_Helper::error( Package::_e( 'package', 'no_exist_pkg' ) );
		}

		# Set global run check
		$this->package->set_global_package_run_check();

		# Show Please Wait
		\WP_CLI_Helper::pl_wait_start( false );

		# Run Package Validation
		$validation_pkg = new validation();
		$get_pkg        = $validation_pkg->validation( true );
		if ( $get_pkg['status'] === true ) {

			# Run Update
			$run = new update();
			$run->run( $get_pkg['data'] );
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
	function docs( $_, $assoc ) {

		//Get basic docs url
		$url = Package::get_config( 'docs' );

		//Check Valid Url
		$web_url = filter_var( $url, FILTER_VALIDATE_URL );
		if ( $web_url === false ) {
			$web_url = Package::get_config( 'docs' );
		}

		//Show in browser
		\WP_CLI_Helper::Browser( $web_url );
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
	function help( $_, $assoc ) {
		help::run();
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
	public function edit( $_, $assoc ) {

		# Exist wordpress package file
		if ( $this->package->exist_package_file() === false ) {
			\WP_CLI_Helper::error( Package::_e( 'package', 'no_exist_pkg' ) );
		}

		# Lunch Editor
		\WP_CLI_Helper::lunch_editor( $this->package->package_path, ( isset( $assoc['editor'] ) ? $assoc['editor'] : false ) );
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
	function htaccess( $_, $assoc ) {

		//Check Network
		$network = Core::is_multisite();

		//Check Custom directory
		$dirs = array();
		foreach ( array( 'wp_content', 'plugins', 'uploads', 'themes' ) as $dir ) {
			if ( isset( $assoc[ $dir ] ) ) {
				$dirs[ $dir ] = $assoc[ $dir ];
			}
		}

		//Create file
		Permalink::create_permalink_file( $network['network'], $network['subdomain'], $dirs );

		//Success
		\WP_CLI_Helper::success( Package::_e( 'package', 'created_file', array( "[file]" => $network['mod_rewrite_file'] ) ) );
	}
}