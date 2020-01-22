<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\install;

class Security {
	/**
	 * Security File to Remove
	 *
	 * @var array
	 */
	public static $security_file = array( 'wp-config-sample.php', 'license.txt', 'readme.html', '.maintenance' );

	/**
	 * Add Mu-Plugins for Security WordPress Package
	 *
	 * @param $pkg_array
	 * @param bool $log
	 */
	public static function wordpress_package_security_plugin( $pkg_array, $log = false ) {

		//get mu-plugins path
		$mu_plugins_path = \WP_CLI_FileSystem::normalize_path( Dir::eval_get_mu_plugins_path() );
		if ( ! empty( $mu_plugins_path ) ) {
			if ( $log ) {
				\WP_CLI_Helper::pl_wait_start();
			}

			//Upload Mu-Plugins
			$mustache      = \WP_CLI_FileSystem::load_mustache( WP_CLI_PACKAGIST_TEMPLATE_PATH );
			$htaccess_code = $mustache->render( 'mu-plugins/access-package' );
			\WP_CLI_FileSystem::file_put_content(
				\WP_CLI_FileSystem::path_join( $mu_plugins_path, 'wordpress-package.php' ),
				$mustache->render( 'mu-plugins/wordpress-package', array(
					'code' => $htaccess_code
				) )
			);

			//Added Code to htaccess
			$htaccess = \WP_CLI_Util::getcwd( ".htaccess" );
			if ( self::iis7_supports_permalinks( $pkg_array ) === false || file_exists( $htaccess ) ) {
				$file_content = $htaccess_code;
				if ( file_exists( $htaccess ) ) {

					$contents = @file( $htaccess, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
					if ( $contents != false ) {
						$size                  = count( $contents );
						$contents[ $size - 2 ] = $htaccess_code;
						$file_content          = implode( "\n", $contents );
					}

					\WP_CLI_FileSystem::file_put_content( $htaccess, $file_content );
				}
			}

			//log
			if ( $log ) {
				install::add_detail_log( Package::_e( 'package', 'sec_mu_plugins', array( "[file]" => Package::get_config( 'package', 'file' ) ) ) );
				\WP_CLI_Helper::pl_wait_end();
			}
		}

	}

	/**
	 * Remove Security File
	 *
	 * @param bool $log
	 */
	public static function remove_security_file( $log = false ) {

		// Remove Files For Security
		foreach ( self::$security_file as $file ) {

			//Check Exist File
			$file_path = \WP_CLI_FileSystem::path_join( \WP_CLI_Util::getcwd(), $file );
			if ( file_exists( $file_path ) ) {

				//Remove File
				\WP_CLI_FileSystem::remove_file( $file_path );

				//Add Log
				if ( $log ) {
					install::add_detail_log( Package::_e( 'package', 'removed_file', array( "[file]" => $file ) ) );
				}
			}
		}

	}

	/**
	 * Check Support iis permalink
	 *
	 * @param $pkg_array
	 * @return bool
	 */
	public static function iis7_supports_permalinks( $pkg_array ) {
		$iis7_supports_permalinks = false;

		//Upload Plugin file
		$mustache        = \WP_CLI_FileSystem::load_mustache( WP_CLI_PACKAGIST_TEMPLATE_PATH );
		$mu_plugins_path = Dir::eval_get_mu_plugins_path();
		$get_key         = strtolower( \WP_CLI_Util::random_key( 80, false ) );
		$data            = array(
			'GET_KEY'   => $get_key,
			'file_name' => 'pretty-permalinks.php',
		);
		$text            = $mustache->render( 'mu-plugins/pretty-permalinks', $data );
		\WP_CLI_FileSystem::file_put_content( \WP_CLI_FileSystem::path_join( $mu_plugins_path, 'pretty-permalinks.php' ), $text );

		//Connect to WordPress
		$url     = $pkg_array['config']['url'];
		$request = \WP_CLI_Helper::http_request( rtrim( $url, "/" ) . "/?wp_cli_iis7_check=" . $get_key );
		if ( $request != false ) {
			if ( isset( $request['is_iis7'] ) and $request['is_iis7'] == "true" ) {
				$iis7_supports_permalinks = true;
			}
		}

		return $iis7_supports_permalinks;
	}
}