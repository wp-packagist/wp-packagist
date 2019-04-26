<?php

namespace WP_CLI_PACKAGIST;

use WP_CLI_PACKAGIST\Command\Curl;
use WP_CLI_PACKAGIST\Package\Arguments\Core;
use WP_CLI_PACKAGIST\Package\Arguments\Permalink;
use WP_CLI_PACKAGIST\Utility\CLI;

/**
 * WP-CLI Filesystem tools.
 *
 * ## EXAMPLES
 *
 *      # Download File
 *      $ wp tools curl
 *      Success: Completed Download file.
 *
 * ## DOCUMENT
 *
 *      https://wp-packagist.com/docs/WP-CLI-tools/
 *
 * @package wp-cli
 */
class Tools extends \WP_CLI_Command {
	/**
	 * Download file with PHP Curl.
	 *
	 * ## OPTIONS
	 *
	 * <url>
	 * : File Url with Protocol.
	 *
	 * [<location>]
	 * : File location after download.
	 *
	 * [--name=<filename>]
	 * : new name of file.
	 *
	 * [--force]
	 * : Overwrites existing files, if present.
	 *
	 * ## DOCUMENT
	 *
	 *      https://wp-packagist.com/docs/curl/
	 *
	 * ## EXAMPLES
	 *
	 *      # Download file latest.tar.gz
	 *      $ wp tools curl http://wordpress.org/latest.tar.gz
	 *      Success: Completed download file.
	 *
	 *      # Download file and put in /download folder
	 *      $ wp tools curl http://wordpress.org/latest.tar.gz /download
	 *      Success: Completed download file.
	 *
	 *      # Download file and put in /download folder and rename to wp.tar.gz
	 *      $ wp tools curl http://wordpress.org/latest.tar.gz /download --name=wp.tar.gz
	 *      Success: Completed download file.
	 *
	 * @when before_wp_load
	 */
	function curl( $_, $assoc ) {

		# Ignore user aborts and allow the script
		ignore_user_abort( true );
		set_time_limit( 0 );

		# Prepare variable
		$url   = $_[0];
		$where = $new_name = '';
		if ( isset( $_[1] ) ) {
			$where = $_[1];
		}
		if ( isset( $assoc['name'] ) ) {
			$new_name = $assoc['name'];
		}

		# Start Download Class
		$download = new Curl( $url, $where, $new_name );
		$option   = isset( $assoc['force'] ) ? array( 'force' => true ) : array();
		$download->download( $option );
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
	 * ## DOCUMENT
	 *
	 *      https://realwordpress.github.io/wp-cli-application/
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp tools htaccess
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
		CLI::success( CLI::_e( 'package', 'created_file', array( "[file]" => $network['mod_rewrite_file'] ) ) );
	}



}