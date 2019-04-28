<?php

namespace WP_CLI_PACKAGIST\Utility;

use WP_CLI_PACKAGIST\Package\Arguments\Dir;
use WP_CLI_PACKAGIST\Package\Package;

class Wordpress {

	/**
	 * Get User Info
	 *
	 * @param $value
	 * @return bool
	 */
	public static function get_user_info( $value ) {

		if ( is_numeric( $value ) ) {
			$user = get_user_by( 'id', $value );
		} else {
			if ( is_email( $value ) ) {
				$user = get_user_by( 'email', $value );
			} else {
				$user = get_user_by( 'login', $value );
			}
		}
		if ( $user ) {
			return $user;
		} else {
			return false;
		}
	}

	/**
	 * Set Current User
	 *
	 * @param $user_id
	 * @param $redirect
	 */
	public static function set_current_user( $user_id, $redirect ) {

		$home = Wordpress::get_option( 'home' );

		//Create Login Option
		$hash = wp_generate_password( 30, false );
		update_option( Package::get_config( 'acl_opt' ), array(
			'hash'     => $hash,
			'type'     => 'login',
			'id'       => $user_id,
			'time'     => time(),
			'redirect' => $redirect
		), "no" );

		//Create Mu Plugins
		if ( FileSystem::folder_exist( self::mu_plugins_dir() ) == false ) {
			FileSystem::create_dir( "mu-plugins", Dir::get_content_dir() );
		}
		$mustache = FileSystem::load_mustache( WP_CLI_PACKAGIST_TEMPLATE_PATH );
		FileSystem::file_put_content( FileSystem::path_join( self::mu_plugins_dir(), "acl.php" ), $mustache->render( '/mu-plugins/acl', array( 'wp_acl' => Package::get_config( 'acl_opt' ) ) ) );

		//Create link to Show in browser
		$link = add_query_arg( Package::get_config( 'acl_opt' ), 'login,' . $hash, $home );
		CLI::Browser( $link );

		//Show Success
		CLI::success( "User Session was set." );
	}

	/**
	 * Check Wordpress Already exist
	 */
	public static function check_wp_exist() {
		if ( file_exists( FileSystem::path_join( getcwd(), 'wp-load.php' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get Mu Plugins Path
	 */
	public static function mu_plugins_dir() {
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
			return FileSystem::path_join( getcwd(), 'wp-content/mu-plugins' );
		} else {
			return FileSystem::normalize_path( WPMU_PLUGIN_DIR );
		}
	}

	/**
	 * Get Base Path
	 */
	public static function get_base_path() {

		//Get default path
		$path = '';
		if ( defined( 'ABSPATH' ) ) {
			$path = FileSystem::normalize_path( ABSPATH );
		}

		//GetCWD php
		if ( trim( $path ) == "" || $path == "/" || $path == "\\" ) {
			$path = FileSystem::normalize_path( getcwd() );
		}

		return $path;
	}

	/**
	 * Get site url Wordpress
	 */
	public static function get_site_url() {
		if ( function_exists( 'get_option' ) ) {
			return get_option( 'siteurl' );
		} elseif ( function_exists( 'home_url' ) ) {
			return home_url();
		} elseif ( defined( 'WP_SITEURL' ) ) {
			return WP_SITEURL;
		}

		return '';
	}

	/**
	 * Get option Wordpress
	 *
	 * @param $opt_key
	 * @return string
	 */
	public static function get_option( $opt_key ) {
		if ( function_exists( 'get_option' ) ) {
			return get_option( $opt_key );
		} else {
			die();
		}
	}


}