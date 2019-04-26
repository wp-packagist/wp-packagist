<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Utility\FileSystem;

class Cookie {
	/**
	 * Change WordPress Cookie Constant
	 *
	 * @param $cookie_prefix
	 * @param $site_url
	 * @param bool $hash_test_cookie
	 * @return array
	 */
	public static function set_cookie_prefix( $cookie_prefix, $site_url, $hash_test_cookie = true ) {
		/**
		 * Default constant
		 *
		 * @see https://developer.wordpress.org/reference/functions/wp_cookie_constants/
		 */
		$list = array(
			"COOKIEHASH",
			"USER_COOKIE",
			"PASS_COOKIE",
			"AUTH_COOKIE",
			"SECURE_AUTH_COOKIE",
			"LOGGED_IN_COOKIE",
			"TEST_COOKIE"
		);

		//Load WP-config Transform
		$config_transformer = Config::get_config_transformer();

		//First Remove All Cookie Constant if exist
		foreach ( $list as $const ) {
			$config_transformer->remove( 'constant', $const );
		}

		//Hash url
		if ( function_exists( 'wp_hash_password' ) ) {
			$hash_url = wp_hash_password( $site_url );
		} else {
			$hash_url = sha1( $site_url );
		}

		//Sanitize Cookie prefix
		$last_character = substr( $cookie_prefix, - 1 );
		if ( $last_character != "_" || $last_character != "-" ) {
			$cookie_prefix = $cookie_prefix . '_';
		}

		//Added constant
		if ( trim( $cookie_prefix ) != "wordpress" ) { //wordpress is a default value
			foreach ( $list as $const ) {

				switch ( $const ) {
					case "COOKIEHASH":
						$config_transformer->update( 'constant', $const, $hash_url, array( 'raw' => false, 'normalize' => true ) );
						break;
					case "USER_COOKIE":
						$config_transformer->update( 'constant', $const, "'" . $cookie_prefix . "user_' . COOKIEHASH", array( 'raw' => true, 'normalize' => true ) );
						break;
					case "PASS_COOKIE":
						$config_transformer->update( 'constant', $const, "'" . $cookie_prefix . "pass_' . COOKIEHASH", array( 'raw' => true, 'normalize' => true ) );
						break;
					case "AUTH_COOKIE":
						$config_transformer->update( 'constant', $const, "'" . $cookie_prefix . "' . COOKIEHASH", array( 'raw' => true, 'normalize' => true ) );
						break;
					case "SECURE_AUTH_COOKIE":
						$config_transformer->update( 'constant', $const, "'" . $cookie_prefix . "sec_' . COOKIEHASH", array( 'raw' => true, 'normalize' => true ) );
						break;
					case "LOGGED_IN_COOKIE":
						$config_transformer->update( 'constant', $const, "'" . $cookie_prefix . "login_' . COOKIEHASH", array( 'raw' => true, 'normalize' => true ) );
						break;
					case "TEST_COOKIE":
						if ( $hash_test_cookie ) {
							$test_cookie = "'" . FileSystem::random_key( 30, false ) . "'";
						} else {
							$test_cookie = "'" . $cookie_prefix . "_cookie_test'";
						}
						$config_transformer->update( 'constant', $const, $test_cookie, array( 'raw' => true, 'normalize' => true ) );
						break;
				}
			}
		}

		return array( 'status' => true );
	}
}