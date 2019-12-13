<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\install;

/**
 * Class Config
 */
class Config {
	/**
	 * Get WordPress wp-config.php path
	 * @return mixed
	 */
	public static function get_wp_config_path() {
		return \WP_CLI_Util::getcwd( 'wp-config.php' );
	}

	/**
	 * Get Config Transformer
	 * @see https://github.com/wp-cli/wp-config-transformer
	 */
	public static function get_config_transformer() {
		$file = self::get_wp_config_path();
		if ( file_exists( $file ) and class_exists( '\WPConfigTransformer' ) ) {
			$config_transformer = new \WPConfigTransformer( $file );
			return $config_transformer;
		} else {
			return false;
		}
	}

	/**
	 * Create config File
	 *
	 * @param array $mysql
	 */
	public static function create_config( $mysql = array() ) {
		//Run Create Config File
		$cmd = "config create --dbname=%s --dbuser=%s --dbpass=%s --dbhost=%s --dbprefix=%s --skip-check";
		$cmd = \WP_CLI\Utils\esc_cmd( $cmd, $mysql['DB_NAME'], $mysql['DB_USER'], $mysql['DB_PASSWORD'], $mysql['DB_HOST'], $mysql['table_prefix'] );

		//Check DB Charset
		if ( isset( $mysql['DB_CHARSET'] ) ) {
			$cmd .= " --dbcharset={$mysql['DB_CHARSET']}";
		}

		//Check DB Collate
		if ( isset( $mysql['DB_COLLATE'] ) and ! empty( $mysql['DB_COLLATE'] ) ) {
			$cmd .= " --dbcollate={$mysql['DB_COLLATE']}";
		}

		//Run Command
		\WP_CLI_Helper::run_command( $cmd );
	}

	/**
	 * Add WordPress Package anchor to wp-config.php
	 */
	public static function add_wordpress_package_anchor() {
		\WP_CLI_FileSystem::search_replace_file(
			self::get_wp_config_path(),
			array(
				"/* That's all",
				" * * Database table prefix"
			),
			array(
				"" . Package::get_config( 'package', 'wordpress_package_anchor' ) . "\n" . Package::get_config( 'package', 'wp_package_end_anchor' ) . "\n\n/* That's all",
				" * * Database table prefix\n * * WordPress Package Constant"
			) );
	}

	/**
	 * Get Line Of WordPress Package anchor
	 *
	 * @return array
	 */
	public static function get_line_of_package_anchor() {
		$source = file( self::get_wp_config_path() );
		$line   = array();
		$x      = 1;
		foreach ( $source as $l ) {
			if ( stristr( trim( $l ), Package::get_config( 'package', 'wordpress_package_anchor' ) ) != false ) {
				$line['from'] = $x;
			}
			if ( stristr( trim( $l ), Package::get_config( 'package', 'wp_package_end_anchor' ) ) != false ) {
				$line['to'] = $x;
			}
			$x ++;
		}

		//TODO add status false if not found for update
		return $line;
	}

	/**
	 * Get List Of constant and Variable wp-config.php file
	 *
	 * @return mixed
	 */
	public static function get_list_config() {
		$config_transformer = new Constant( self::get_wp_config_path() );
		$wp_config_src      = file_get_contents( self::get_wp_config_path() );
		$wp_config_src      = str_replace( array( "\n\r", "\r" ), "\n", $wp_config_src );
		return $config_transformer->parse_wp_config( $wp_config_src );
	}

	/**
	 * Get Table Prefix in WordPress
	 *
	 * @return string
	 */
	public static function get_tbl_prefix() {
		$wp_config = self::get_list_config();
		$variable  = $wp_config['variable'];
		return isset( $variable['table_prefix']['value'] ) ? \WP_CLI_Util::remove_quote( $variable['table_prefix']['value'] ) : Package::get_config( 'package', 'default', 'table_prefix' );
	}

	/**
	 * Get List all constant in WordPress wp-config.php
	 *
	 * @return array
	 */
	public static function get_list_package_constant() {
		//Get List All Constant
		$wp_config = self::get_list_config();
		$constants = $wp_config['constant'];

		//Get Line of WordPress Package anchor
		$line           = self::get_line_of_package_anchor();
		$list           = array();
		$read_wp_config = file( self::get_wp_config_path() );
		foreach ( $constants as $const_name => $const_val ) {
			$x = 1;
			foreach ( $read_wp_config as $l ) {
				if ( stristr( trim( $l ), $const_val['src'] ) != false and $x >= $line['from'] and $x < $line['to'] ) {
					$list[ $const_name ] = $const_val['value'];
				}
				$x ++;
			}
		}

		return $list;
	}

	/**
	 * Update Constant List
	 *
	 * @param $pkg_constant
	 * @param array $current_const_list
	 * @param array $options
	 */
	public static function update_constant( $pkg_constant, $current_const_list = array(), $options = array() ) {

		//Load Wp-config Transform
		$config_transformer = self::get_config_transformer();

		//Constant argument
		$constant_arg = array( 'anchor' => Package::get_config( 'package', 'wordpress_package_anchor' ), 'placement' => 'after', 'raw' => true, 'normalize' => true );

		//Default Params
		$defaults = array(
			'force'  => false,
			'log'    => true,
			'remove' => true
		);
		$args     = \WP_CLI_Util::parse_args( $options, $defaults );

		//Check Removed Const
		if ( isset( $args['remove'] ) and ! empty( $current_const_list ) ) {

			foreach ( $current_const_list as $key => $value ) {

				//if not exist in Package const list then be Removed
				$exist = false;
				foreach ( $pkg_constant as $pk_key => $pk_value ) {
					$exist = ( $key == $pk_key ? true : false );
				}

				if ( $exist === false ) {
					//Removed From Current const
					unset( $current_const_list[ $key ] );

					//Run Removed const
					$config_transformer->remove( 'constant', $key );

					//Log
					if ( isset( $args['log'] ) and $args['log'] === true ) {
						install::add_detail_log( "Removed " . \WP_CLI_Helper::color( $key, "R" ) . " Constant." );
					}
				}
			}
		}

		//Check Add or Update Const
		foreach ( $pkg_constant as $pk_key => $pk_value ) {

			//Check Exist Const
			$wp_exist = false;
			foreach ( $current_const_list as $key => $value ) {
				$wp_exist = ( $key == $pk_key ? true : false );
			}

			# add const
			if ( $wp_exist === false ) {
				$config_transformer->add( 'constant', $pk_key, $pk_value, $constant_arg );
				if ( isset( $args['log'] ) and $args['log'] === true ) {
					install::add_detail_log( Package::_e( 'package', 'item_log', array( "[what]" => "Constant", "[key]" => $pk_key, "[run]" => "Added" ) ) );
				}

				# Updated constant
			} else {
				$config_transformer->update( 'constant', $pk_key, $pk_value, $constant_arg );
				if ( isset( $args['log'] ) and $args['log'] === true ) {
					install::add_detail_log( Package::_e( 'package', 'item_log', array( "[what]" => "Constant", "[key]" => $pk_key, "[run]" => "Updated" ) ) );
				}
			}
		}

	}

}