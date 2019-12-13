<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Package\Package;

class Timezone {
	/**
	 * Get List WordPress TimeZone
	 *
	 * @return mixed
	 */
	public static function get_timezone_list() {

		//Get List Of Wordpress Time Zone json
		$list = Package::get_config( 'package', 'wordpress_timezone' );

		//Return Array List
		return json_decode( $list, true );
	}

	/**
	 * Sanitize WordPress TimeZone
	 *
	 * -- Example --
	 * Convert +3.5        -> UTC+3.5
	 * Convert  3.5        -> UTC+3.5
	 * Convert  3:5        -> UTC+3.5
	 * Convert -3.5        -> UTC-3.5
	 * Convert asia/tehran -> Asia/Tehran
	 * Convert Asia\Tehran -> Asia/Tehran
	 *
	 * @param $timezone
	 * @return string
	 */
	public static function sanitize_timezone( $timezone ) {
		//Trim
		$timezone = trim( $timezone );

		//Get First Character
		$first_character = substr( $timezone, 0, 1 );

		// Check First Character is + or -
		if ( $first_character == "+" || $first_character == "-" ) {
			$timezone = "UTC" . $timezone;
		}

		// Check First Character is Number ( 3:5 -> UTC+3.5 )
		if ( is_numeric( $first_character ) ) {
			$timezone = "UTC+" . $timezone;
		}

		// Convert \ to /
		$timezone = \WP_CLI_Util::backslash_to_slash( $timezone );

		// Uppercase First character if Wordpress
		if ( stristr( $timezone, "/" ) != false ) {
			$exp      = explode( "/", $timezone );
			$timezone = ucfirst( $exp[0] ) . "/" . ucfirst( $exp[1] );
		}

		// Convert : to .
		return str_ireplace( ":", ".", $timezone );
	}

	/**
	 * Search in Wordpress TimeZone.
	 *
	 * @param $search
	 * @return bool
	 */
	public static function search_timezone( $search ) {

		//Get List
		$get_list = self::get_timezone_list();

		//Search
		if ( in_array( $search, $get_list ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Update WordPress TimeZone
	 *
	 * @param $timezone
	 */
	public static function update_timezone( $timezone ) {
		/**
		 * Check WordPress timezone in DB
		 * SELECT * FROM `{prefix}_options` WHERE `option_name` ='timezone_string' OR `option_name` = 'gmt_offset'
		 */

		//Check Validate
		if ( self::search_timezone( $timezone ) ) {

			//Check Options is timezone_string or gmt_offset
			$opt_name = 'timezone_string';
			if ( stristr( $timezone, "UTC+" ) != false || stristr( $timezone, "UTC-" ) != false ) {
				$opt_name = 'gmt_offset';
			}

			//Reset WordPress Option
			foreach ( array( 'timezone_string', 'gmt_offset' ) as $opt ) {
				\WP_CLI_Helper::run_command( 'option update ' . $opt . ' ""', array( 'exit_error' => false ) );
			}

			//Update TimeZone
			if ( $opt_name == "timezone_string" ) {
				\WP_CLI_Helper::run_command( 'option update timezone_string "' . $timezone . '"', array( 'exit_error' => false ) );

			} else {

				//Sanitize UTC Number
				if ( stristr( $timezone, "UTC+" ) != false ) {
					$gmt_offset = str_ireplace( "UTC+", "", $timezone );
				} else {
					$gmt_offset = str_ireplace( "UTC", "", $timezone );
				}

				\WP_CLI_Helper::run_command( 'option update gmt_offset "' . $gmt_offset . '"', array( 'exit_error' => false ) );
			}
		}
	}

}