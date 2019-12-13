<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\install;
use WP_CLI_PACKAGIST\Package\Utility\temp;

class Version {
	/**
	 * Download WordPress
	 *
	 * @param $version
	 */
	public static function download_wordpress( $version = 'latest' ) {
		$cmd = "core download --version=%s --force";
		\WP_CLI_Helper::run_command( \WP_CLI\Utils\esc_cmd( $cmd, $version ) );
	}

	/**
	 * Update WordPress Command
	 *
	 * @param string $version
	 * @param string $locale
	 */
	public static function update_wordpress_cmd( $version = 'latest', $locale = 'en_US' ) {
		$cmd = "core update --version=%s --locale=%s --force";
		\WP_CLI_Helper::run_command( \WP_CLI\Utils\esc_cmd( $cmd, $version, $locale ) );
		\WP_CLI_Helper::run_command( "option delete core_updater.lock", array( 'exit_error' => false ) );
	}

	/**
	 * Get log download WordPress
	 *
	 * @param $version
	 * @param string $locale
	 * @return string
	 */
	public static function get_log_download_wordpress( $version, $locale = 'en_US' ) {

		//Convert latest to version
		$version = ( $version == "latest" ? self::get_latest_version_num_wordpress() : $version );

		//Check File name
		$file_name = "wordpress-{$version}-{$locale}.[extension]";

		//Check exist File
		$exist = false;
		foreach ( array( 'zip', 'tar.gz' ) as $ext ) {
			$file = str_replace( "[extension]", $ext, $file_name );
			if ( \WP_CLI_Helper::exist_cache_file( "/core/" . $file ) != false ) {
				$exist = true;
				break;
			}
		}

		//show log
		if ( $exist === false ) {
			return Package::_e( 'package', 'get_wp', array( '[run]' => "Download", '[version]' => "v" . $version ) );
		} else {
			return Package::_e( 'package', 'get_wp', array( '[run]' => "Copy", '[version]' => "v" . $version ) );
		}
	}

	/**
	 * Check exist Url
	 *
	 * -- List Mime Type --
	 * .zip -> application/zip
	 *
	 * @param $url
	 * @param bool $what
	 * @param bool $is_zip
	 * @return array
	 */
	public static function exist_url( $url, $what = false, $is_zip = false ) {

		# Check Active curl
		if ( ! function_exists( 'curl_init' ) ) {
			return array( 'status' => false, 'data' => Package::_e( 'curl', 'er_enabled' ), 'error_type' => 'base' );
		}

		# request Start
		$curl = curl_init( $url );
		# No Body Get From Request
		curl_setopt( $curl, CURLOPT_NOBODY, true );
		# don't verify peer ssl cert
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		# Set User Agent
		curl_setopt( $curl, CURLOPT_USERAGENT, Package::get_config( 'curl', 'user_agent' ) );
		# Get Return if redirect
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		# Follow Location for redirect
		curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
		# Request Process
		$exec = curl_exec( $curl );
		if ( $exec === false ) {
			return array( 'status' => false, 'data' => Package::_e( 'curl', 'er_connect', array( "[url]" => preg_replace( "(^https?://)", "", $url ) ) ), 'error_type' => 'base' );
		}
		# Get Header info
		$code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
		# get the content type
		$file_type = curl_getinfo( $curl, CURLINFO_CONTENT_TYPE );
		# Check response status
		if ( $code == 200 ) {
			$info = curl_getinfo( $curl );
			if ( $is_zip ) {
				if ( ! in_array( strtolower( $file_type ), array( 'application/zip', 'application/octet-stream', 'application/octet', 'application/x-zip-compressed', 'multipart/x-zip' ) ) ) {
					return array( 'status' => false, 'data' => Package::_e( 'curl', 'er_zip', array( "[what]" => $what ) ) );
				}
			}
			$return = array( 'status' => true, 'data' => filter_var( $info['url'], FILTER_VALIDATE_URL ), 'file_type' => $file_type );
		} else {
			$return = array( 'status' => false, 'data' => Package::_e( 'curl', 'er_url', array( "[what]" => $what ) ) );
		}

		# Close Request
		curl_close( $curl );
		# Return data
		return $return;
	}

	/**
	 * Check Wordpress Download Url in custom versions and locale.
	 * We use Core_Command/get_download_url method.
	 *
	 * @param $version
	 * @param string $locale
	 * @param string $file_type
	 * @return array
	 */
	public static function check_download_url( $version, $locale = 'en_US', $file_type = 'zip' ) {

		//Create Object Validation
		$valid = new \WP_CLI_ERROR();

		//Check nightly Version
		if ( 'nightly' === $version && 'en_US' !== $locale ) {
			$valid->add_error( Package::_e( 'package', 'er_nightly_ver' ) );
		}

		//Prepare Download Link
		if ( 'en_US' === $locale ) {
			$url = 'https://wordpress.org/wordpress-' . $version . '.' . $file_type;
		} else {
			$url = sprintf(
				'https://%s.wordpress.org/wordpress-%s-%s.' . $file_type,
				substr( $locale, 0, 2 ),
				$version,
				$locale
			);
		}

		//Check Wordpress download url cache
		$file_path = Package::get_config( 'package', 'wordpress_core_url_file' );

		//Check Cache File exist
		if ( file_exists( $file_path ) ) {

			//Get Json data
			$json_data = \WP_CLI_FileSystem::read_json_file( $file_path );

			//Check Url in List
			if ( in_array( $url, $json_data ) and ! $valid->is_cli_error() ) {
				return $valid->result();
			}
		}

		//Check Exist Download Url
		$exist_url = Version::exist_url( $url, "core", false );
		if ( $exist_url['status'] === false ) {
			if ( isset( $exist_url['error_type'] ) and $exist_url['error_type'] == "base" ) {
				$valid->add_error( $exist_url['data'] );
			} else {
				$valid->add_error( Package::_e( 'package', 'er_found_release' ) );
			}
		}

		//Save to file if all status is ok
		if ( ! $valid->is_cli_error() ) {

			//Add Url To list
			$json_data[] = $url;

			//Push To file
			\WP_CLI_FileSystem::create_json_file( $file_path, $json_data, false );
		}

		return $valid->result();
	}

	/**
	 * Get List Wordpress Version
	 *
	 * @param bool $version
	 * @param bool $force_update
	 * @return array|bool
	 */
	public static function get_wordpress_version( $version = false, $force_update = false ) {

		//Cache File name for wordpress version
		$file_path = Package::get_config( 'package', 'version', 'file' );

		//Check Cache File exist
		if ( file_exists( $file_path ) ) {

			//if cache file exist we used same file
			$json_data = \WP_CLI_FileSystem::read_json_file( $file_path );
		}

		// if Force Update
		if ( $force_update === false ) {

			//if require update by calculate cache time
			if ( isset( $json_data ) and \WP_CLI_FileSystem::check_file_age( $file_path, Package::get_config( 'package', 'version', 'age' ) ) === false ) {
				$list = $json_data;
			}
		}

		//Fetch Versions List
		if ( ! isset( $list ) || $force_update === true ) {

			//Get Wordpress Version From API
			$versions_list = self::fetch_wordpress_versions();
			if ( $versions_list['status'] === false ) {
				if ( ! isset( $json_data ) ) {
					return $versions_list;
				} else {
					$list = $json_data;
				}
			} else {
				$list = $versions_list['data'];
			}
		}

		//Check Version number
		if ( isset( $list ) and $list != false ) {

			//Get All List
			if ( $version === false ) {
				return array( 'status' => true, 'data' => $list );
			} else {
				if ( array_key_exists( $version, $list ) ) {
					return array( 'status' => true );
				}
			}
		}

		return array( 'status' => false, 'data' => Package::_e( 'package', 'version_exist' ) );
	}

	/**
	 * Get Wordpress Version List From WordPress.org API
	 */
	public static function fetch_wordpress_versions() {

		//Cache File name for wordpress version
		$version_list = Package::get_config( 'package', 'version', 'file' );

		//Connect To Wordpress API
		$list = \WP_CLI_Helper::http_request( Package::get_config( 'wordpress_api', 'version' ) );
		if ( $list != false ) {

			//convert list to json file
			$list = json_decode( $list, true );

			//Create Cache file for wordpress version list
			\WP_CLI_FileSystem::create_json_file( $version_list, $list, false );
		} else {

			//Show Error connect to WP API
			return array( 'status' => false, 'data' => Package::_e( 'wordpress_api', 'connect' ) );
		}

		return array( 'status' => true, 'data' => $list );
	}

	/**
	 * Get Last Version of WordPress
	 */
	public static function get_latest_version_num_wordpress() {
		$version_list = self::get_wordpress_version();
		$latest       = 'latest';
		if ( $version_list['status'] ) {
			foreach ( $version_list['data'] as $version => $status ) {
				if ( $status == "latest" ) {
					$latest = $version;
				}
			}
		}

		return $latest;
	}

	/**
	 * Update Version WordPress Core in Package
	 *
	 * @param $pkg
	 */
	public static function update_version( $pkg ) {

		//Get Local Temp
		$localTemp = temp::get_temp( \WP_CLI_Util::getcwd() );
		$tmp       = ( $localTemp === false ? array() : $localTemp );

		// Get Latest Version of WordPress
		$latest_wp_version = self::get_latest_version_num_wordpress();

		// Check Tmp Version
		$tmp_version = ( isset( $tmp['core']['version'] ) ? $tmp['core']['version'] : get_bloginfo( 'version' ) );
		$tmp_version = ( $tmp_version == "latest" ? $latest_wp_version : $tmp_version );

		// Check Pkg version
		$pkg_version = ( isset( $pkg['core']['version'] ) ? $pkg['core']['version'] : '1.0.0' );
		$pkg_version = ( $pkg_version == "latest" ? $latest_wp_version : $pkg_version );

		// Check if Changed
		if ( $tmp_version != $pkg_version ) {

			//Show Please wait
			\WP_CLI_Helper::pl_wait_start();

			// Update WordPress core
			self::update_wordpress_cmd( $pkg_version );

			// Remove Security File again
			Security::remove_security_file();

			// Remove Pls wait
			\WP_CLI_Helper::pl_wait_end();

			// Add log
			install::add_detail_log( rtrim( Package::_e( 'package', 'manage_item_blue', array( "[work]" => "Changed", "[key]" => "WordPress Version", "[type]" => "to " . $pkg_version . "" ) ), "." ) );
		}
	}

}