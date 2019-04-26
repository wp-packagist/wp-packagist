<?php

namespace WP_CLI_PACKAGIST\Utility;

use WP_CLI_PACKAGIST\Package\Package;

class PHP {
	/**
	 * Get List of Method From Class
	 *
	 * @param $obj | (new class_name)
	 * @return array
	 * @see http://php.net/manual/en/function.get-class-methods.php
	 */
	public static function class_methods_list( $obj ) {

		$list          = array();
		$class_methods = get_class_methods( $obj );
		foreach ( $class_methods as $method_name ) {
			$list[] = $method_name;
		}

		return $list;
	}

	/**
	 * Search Method From Class
	 *
	 * @param $obj
	 * @param $method
	 * @return bool
	 */
	public static function search_method_from_class( $obj, $method ) {
		//Get List Of Methods
		$list = self::class_methods_list( $obj );
		if ( in_array( $method, $list ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Sanitize lowercase string
	 *
	 * @param $value
	 * @return string
	 */
	public static function to_lower_string( $value ) {
		if ( is_string( $value ) || is_numeric( $value ) ) {
			return trim( mb_strtolower( $value ) );
		}

		return "";
	}

	/**
	 * Sanitize File name
	 *
	 * @param $name
	 * @param string $allowed_character
	 * @return string
	 */
	public static function sanitize_file_name( $name, $allowed_character = '' ) {
		return preg_replace( '/[^a-zA-Z0-9-_.' . $allowed_character . ']/', '', $name );
	}

	/**
	 * Sanitize array Key
	 *
	 * @param $key
	 * @return null|string|string[]
	 *
	 */
	public static function sanitize_key( $key ) {
		$key = strip_tags( $key );
		$key = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '', $key );
		$key = preg_replace( '/&.+?;/', '', $key );
		$key = trim( $key );
		$key = preg_replace( '|\s+|', '', $key );
		$key = preg_replace( '/\s\s+/', '', $key );

		return $key;
	}

	/**
	 * Check Require keys in array
	 *
	 * @param $array
	 * @param $keys
	 * @param bool $case | Case sensitive is false automatic
	 * @return array
	 */
	public static function check_require_array( $array, $keys, $case = false ) {

		//Create Empty Return Object
		$return = array( 'status' => true, 'data' => array() );

		//Check Case sensitive
		if ( $case === false ) {
			$array = array_change_key_case( $array, CASE_LOWER );
		}

		//Check Require arg
		foreach ( $keys as $k ) {
			if ( $case === false ) {
				$k = self::to_lower_string( $k );
			}
			if ( ! array_key_exists( $k, $array ) ) {
				$return['data'][] = $k;
				$return['status'] = false;
			}
		}

		return $return;
	}

	/**
	 * Convert text to array
	 *
	 * @param $obj
	 * @return array
	 */
	public static function to_array( $obj ) {
		if ( is_string( $obj ) ) {
			return array( $obj );
		}

		return $obj;
	}

	/**
	 * Remove Quote From String
	 *
	 * @param $text
	 * @return string
	 */
	public static function remove_quote( $text ) {
		return trim( trim( $text, '"' ), "'" );
	}

	/**
	 * Check is multi array
	 *
	 * @param $array
	 * @return bool
	 */
	public static function is_multi_array( $array ) {
		return ( count( $array ) == count( $array, COUNT_RECURSIVE ) ? false : true );
	}

	/**
	 * Check Array is assoc
	 *
	 * @param array $arr
	 * @return bool
	 */
	public static function is_assoc_array( array $arr ) {
		if ( array() === $arr ) {
			return false;
		}
		return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
	}

	/**
	 * Remove White Space From Word
	 *
	 * @param $word
	 * @return null|string|string[]
	 */
	public static function remove_whitespace_word( $word ) {
		return preg_replace( '/\s\s+/', ' ', $word );
	}

	/**
	 * Remove All Space From Word
	 *
	 * @param $word
	 * @return mixed
	 */
	public static function remove_all_space( $word ) {
		return str_replace( ' ', '', $word );
	}

	/**
	 * Remove All Special character
	 *
	 * @param $word
	 * @return string|string[]|null
	 */
	public static function remove_all_special_chars( $word ) {
		return preg_replace( '/[^a-zA-Z0-9]/', '', $word );
	}

	/**
	 * Calculate the number of variable characters (UTF-8 Support)
	 *
	 * @param $string
	 * @return int
	 */
	public static function strlen( $string ) {
		return mb_strlen( $string, 'UTF-8' );
	}

	/**
	 * Sub str With UTF-8 support
	 *
	 * @param $string
	 * @param int $number
	 * @param int $start
	 * @return string
	 */
	public static function substr( $string, $number = 100, $start = 0 ) {
		return mb_substr( $string, $start, $number, "utf-8" );
	}

	/**
	 * Check Semver Version is validate
	 *
	 * @see https://semver.org/
	 * @param $version
	 * @return bool
	 */
	public static function is_semver_version( $version ) {
		if ( preg_match( '/^(\d+\.)?(\d+\.)?(\*|\d+)$/', $version ) ) {
			return true;
		}

		return false;
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
			return array( 'status' => false, 'data' => CLI::_e( 'curl', 'er_enabled' ), 'error_type' => 'base' );
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
			return array( 'status' => false, 'data' => CLI::_e( 'curl', 'er_connect', array( "[url]" => preg_replace( "(^https?://)", "", $url ) ) ), 'error_type' => 'base' );
		}
		# Get Header info
		$code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
		# get the content type
		$file_type = curl_getinfo( $curl, CURLINFO_CONTENT_TYPE );
		# Check response status
		if ( $code == 200 ) {
			$info = curl_getinfo( $curl );
			if ( $is_zip ) {
				if ( ! in_array( strtolower( $file_type ), Package::get_config( 'package', 'zip_mime_type' ) ) ) {
					return array( 'status' => false, 'data' => CLI::_e( 'curl', 'er_zip', array( "[what]" => $what ) ) );
				}
			}
			$return = array( 'status' => true, 'data' => filter_var( $info['url'], FILTER_VALIDATE_URL ), 'file_type' => $file_type );
		} else {
			$return = array( 'status' => false, 'data' => CLI::_e( 'curl', 'er_url', array( "[what]" => $what ) ) );
		}

		# Close Request
		curl_close( $curl );
		# Return data
		return $return;
	}

	/**
	 * Json Encode With Pretty Format
	 *
	 * @param $array
	 * @return array|false|string
	 */
	public static function json_encode( $array ) {
		if ( is_array( $array ) ) {
			return json_encode( $array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK );
		}

		return array();
	}

	/**
	 * converts all keys in a multidimensional array to lower or upper case
	 *
	 * @param $arr
	 * @param int $case
	 * @return array
	 */
	public static function array_change_key_case_recursive( $arr, $case = CASE_LOWER ) {
		return array_map( function ( $item ) use ( $case ) {
			if ( is_array( $item ) ) {
				$item = self::array_change_key_case_recursive( $item, $case );
			}
			return $item;
		}, array_change_key_case( $arr, $case ) );
	}

	/**
	 * Remove Double Slash
	 *
	 * @param $str
	 * @return string|string[]|null
	 */
	public static function remove_double_slash( $str ) {
		return preg_replace( '#/+#', '/', $str );
	}

	/**
	 * Convert all backslash to slash
	 *
	 * @param $string
	 * @return mixed
	 */
	public static function backslash_to_slash( $string ) {
		return str_replace( "\\", "/", $string );
	}

	/**
	 * Get Base Path
	 *
	 * @param string $path
	 * @return mixed
	 */
	public static function getcwd( $path = '' ) {
		return rtrim( self::backslash_to_slash( getcwd() ), "/" ) . ( ! empty( $path ) ? "/" . ltrim( self::backslash_to_slash( $path ), "/" ) : '' );
	}

	/**
	 * Check Validate Url
	 *
	 * @param $url
	 * @return bool|mixed
	 */
	public static function is_url( $url ) {
		$link = filter_var( $url, FILTER_VALIDATE_URL );
		if ( $link === false ) {
			return false;
		} else {
			return $link;
		}
	}

	/**
	 * Remove (-master) from Github
	 *
	 * @param $path
	 */
	public static function sanitize_github_dir( $path ) {

		//Sanitize path
		$path = rtrim( FileSystem::normalize_path( $path ), "/" ) . "/";

		//Check Real path
		if ( realpath( $path ) and is_dir( $path ) ) {

			//Get folder name
			$dir        = basename( $path );
			$first_path = str_ireplace( $dir, "", $path );

			//Check find (-master)
			if ( substr( $dir, - 7 ) == "-master" ) {
				$new_dir_name = str_ireplace( "-master", "", $dir );
				$new_path     = FileSystem::path_join( $first_path, $new_dir_name );

				//Rename
				FileSystem::rename( $path, $new_path );
			}
		}
	}

	/**
	 * Parse Arg Array
	 *
	 * @see https://developer.wordpress.org/reference/functions/wp_parse_args/
	 * @param $args
	 * @param string $defaults
	 * @return array
	 */
	public static function parse_args( $args, $defaults = '' ) {
		$r = $args;
		if ( is_object( $args ) ) {
			$r = get_object_vars( $args );
		} elseif ( is_array( $args ) ) {
			$r =& $args;
		}
		if ( is_array( $defaults ) ) {
			return array_merge( $defaults, $r );
		}
		return $args;
	}

	/**
	 * Define STDIN
	 */
	public static function define_stdin() {
		if ( ! defined( "STDIN" ) ) {
			define( "STDIN", fopen( 'php://stdin', 'rb' ) );
		}
	}

	/**
	 * Check Nested Key Exist in array
	 *
	 * @param $key
	 * @param $array
	 * @return bool
	 */
	public static function check_exist_key( $key, $array ) {
		if ( is_array( $key ) ) {
			$curArray = $array;
			$lastKey  = array_pop( $key );
			foreach ( $key as $oneKey ) {
				if ( ! self::check_exist_key( $oneKey, $curArray ) ) {
					return false;
				}
				$curArray = $curArray[ $oneKey ];
			}
			return is_array( $curArray ) && self::check_exist_key( $lastKey, $curArray );
		} else {
			return isset( $array[ $key ] ) || array_key_exists( $key, $array );
		}
	}

	/**
	 * Get Path of Url
	 *
	 * @param $url
	 * @return mixed
	 */
	public static function get_path_url( $url ) {
		$url   = rtrim( $url, "/" ) . "/";
		$parts = parse_url( $url );
		return $parts['path'];
	}

}