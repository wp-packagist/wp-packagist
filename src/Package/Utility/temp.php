<?php

namespace WP_CLI_PACKAGIST\Package\Utility;

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Utility\FileSystem;
use WP_CLI_PACKAGIST\Utility\PHP;

class temp {
	/**
	 * Convert Link To file Name
	 *
	 * @param $path
	 * @return mixed|string
	 */
	private static function convert_path_to_file( $path ) {
		$path = PHP::to_lower_string( PHP::backslash_to_slash( $path ) );
		$path = str_ireplace( "/", "--", $path );
		$path = str_ireplace( "?", "&&", $path );
		$path = str_ireplace( ":", "++", $path );

		return $path;
	}

	/**
	 * Get Temp file
	 *
	 * @param $cwd
	 * @return string
	 */
	public static function get_temp_file_name( $cwd ) {
		return FileSystem::path_join( Package::get_config( 'package', 'localTemp', 'path' ), self::convert_path_to_file( $cwd ) . Package::get_config( 'package', 'localTemp', 'type' ) );
	}

	/**
	 * Save Package Temp
	 *
	 * @param $cwd
	 * @param $pkg_array
	 * @return bool
	 */
	public static function save_temp( $cwd, $pkg_array ) {
		self::clear_temp(); # Remove Expire temp
		$file      = self::get_temp_file_name( $cwd );
		$pkg_array = self::do_hook_package( $pkg_array );
		if ( FileSystem::create_json_file( $file, $pkg_array ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Remove Custom Temp File
	 *
	 * @param $cwd
	 * @return bool
	 */
	public static function remove_temp_file( $cwd ) {
		$file = self::get_temp_file_name( $cwd );
		if ( file_exists( $file ) ) {
			if ( FileSystem::remove_file( $file ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Remove LocalTemp
	 *
	 * @param bool $force
	 */
	public static function clear_temp( $force = false ) {
		$localPath = Package::get_config( 'package', 'localTemp', 'path' );
		if ( realpath( $localPath ) ) {
			$list_file = FileSystem::get_dir_contents( $localPath );
			foreach ( $list_file as $file ) {
				$file = $file . Package::get_config( 'package', 'localTemp', 'name' );
				if ( $force === true || FileSystem::check_file_age( $file, Package::get_config( 'package', 'localTemp', 'age' ) ) ) {
					FileSystem::remove_file( $file );
				}
			}
		}
	}

	/**
	 * Get Last LocalTemp
	 *
	 * @param $cwd
	 * @return array|bool
	 */
	public static function get_temp( $cwd ) {
		self::clear_temp(); # Remove Expire Temp
		$base_file = self::get_temp_file_name( $cwd );
		$list      = FileSystem::get_dir_contents( Package::get_config( 'package', 'localTemp', 'path' ) );
		foreach ( $list as $file_path ) {
			$file_path = $file_path . Package::get_config( 'package', 'localTemp', 'name' );
			if ( FileSystem::normalize_path( $file_path ) == FileSystem::normalize_path( $base_file ) ) {
				$get_data = FileSystem::read_json_file( $file_path );
				if ( $get_data != false ) {
					return $get_data;
				}
			}
		}

		return false;
	}

	/**
	 * Add/Remove Data From WordPress Package
	 *
	 * @param $pkg_array
	 * @return mixed
	 */
	public static function do_hook_package( $pkg_array ) {
		return $pkg_array;
	}
}