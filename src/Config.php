<?php

namespace WP_CLI_PACKAGIST;

use WP_CLI_PACKAGIST\Utility\CLI;
use WP_CLI_PACKAGIST\Utility\WP_CLI_CONFIG;

/**
 * Global Configuration.
 *
 * ## EXAMPLES
 *
 *      # Set new config
 *      $ wp global-config set path ~/wp-cli/site
 *      Success: Saved path config.
 *
 *      # Reset Configuration
 *      $ wp global-config reset
 *      Success: Configuration reset.
 *
 *      # get Configuration list
 *      $ wp global-config list
 *
 *      # get list of local Configuration file
 *      $ wp global-config reset --local
 *
 *      # get config
 *      $ wp global-config get port
 *
 * ## DOCUMENT
 *
 *      https://wp-packagist.com/docs/global-config/
 *
 */
class Config extends \WP_CLI_Command {
	/**
	 * Set new config.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : Config key
	 *
	 * <value>
	 * : Config Value
	 *
	 * [--local]
	 * : Set parameter to current working directory local config.
	 *
	 * ## EXAMPLES
	 *
	 *      # Set new config
	 *      $ wp global-config set path ~/wp-cli/site
	 *      Success: Saved path config.
	 *
	 *      # Set new config with space in key
	 *      $ wp global-config set "config create" new_value
	 *      Success: Saved config create config.
	 *
	 *      # Set new config with boolean value
	 *      $ wp global-config set color false
	 *      Success: Saved color config.
	 *
	 *      # Set new config with number value
	 *      $ wp global-config set num-cache-reference 100
	 *      Success: Saved num-cache-reference config.
	 *
	 *      # Set new config with array value
	 *      $ wp global-config set disabled_commands '["db drop","plugin install"]'
	 *      Success: Saved disabled_commands config.
	 *
	 *      # Set new config with null value
	 *      $ wp global-config set db_pass null
	 *      Success: Saved db_pass config.
	 *
	 *      # Set new config with null value
	 *      $ wp global-config set db_pass ''
	 *      Success: Saved db_pass config.
	 *
	 *      # Set new config with nested array key
	 *      $ wp global-config set @staging:user wpcli
	 *      Success: Saved @staging:user config.
	 *
	 *      # Set new config with nested array key
	 *      $ wp global-config set "config create:dbuser" my_user
	 *      Success: Saved config create:dbuser config.
	 *
	 * @when before_wp_load
	 */
	function set( $_, $assoc ) {

		//Check local or global config file
		$type = ( isset( $assoc['local'] ) ? 'local' : 'global' );

		//Load WP-CLI-CONFIG class
		$wp_cli_config = new WP_CLI_CONFIG( $type );

		//Load config File
		$current_config = $wp_cli_config->load_config_file();

		//sanitize value
		$value = trim( $_[1] );
		$key   = $_[0];

		//check bool
		if ( $value == "false" || $value == "true" ) {
			$value = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
		}

		//Check INT
		if ( is_numeric( $value ) ) {
			$value = filter_var( $value, FILTER_VALIDATE_INT );
		}

		//Check Array Value
		if ( json_decode( $value ) != null ) {
			$value = json_decode( $value, true );
		}

		//Check null value
		if ( empty( $value ) || $value == "null" ) {
			$value = null;
		}

		//check nested
		if ( stristr( $_[0], ":" ) != false ) {
			$exp = explode( ":", $_[0] );
			$exp = array_filter( $exp, function ( $value ) {
				return $value !== '';
			} );

			$count = count( $exp );
			if ( $count == 2 ) {
				$array[ reset( $exp ) ] = array( end( $exp ) => $value );
			} elseif ( $count == 3 ) {
				$array[ reset( $exp ) ] = array( $exp[1] => array( end( $exp ) => $value ) );
			} elseif ( $count == 4 ) {
				$array[ reset( $exp ) ] = array( $exp[1] => array( $exp[2] => array( end( $exp ) => $value ) ) );
			} else {
				CLI::error( CLI::_e( 'config', 'key_valid' ) );
			}

			$current_config = array_replace_recursive( $current_config, $array );
		} else {
			$current_config[ $key ] = $value;
		}

		if ( $wp_cli_config->save_config_file( $current_config ) ) {
			CLI::success( CLI::_e( 'config', 'add', array( "[key]" => $_[0] ) ) );
		} else {
			CLI::error( CLI::_e( 'config', 'filed_update' ) );
		}
	}

	/**
	 * Remove Config Parameter.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : Config key
	 *
	 * [--local]
	 * : Remove parameter from current working directory local config.
	 *
	 * ## EXAMPLES
	 *
	 *      # Remove config parameter
	 *      $ wp global-config remove path
	 *      Success: Removed path config.
	 *
	 *      # Remove config parameter from local file
	 *      $ wp global-config remove path --local
	 *      Success: Removed path config.
	 *
	 * @when before_wp_load
	 */
	function remove( $_, $assoc ) {

		//Check local or global config file
		$type = ( isset( $assoc['local'] ) ? 'local' : 'global' );

		//Load WP-CLI-CONFIG class
		$wp_cli_config = new WP_CLI_CONFIG( $type );

		//Load config File
		$current_config = $wp_cli_config->load_config_file();

		//sanitize value
		$key = $_[0];

		//check nested
		if ( stristr( $_[0], ":" ) != false ) {
			$exp = explode( ":", $_[0] );
			$exp = array_filter( $exp, function ( $value ) {
				return $value !== '';
			} );

			$count = count( $exp );
			if ( $count == 2 ) {
				if ( isset( $current_config[ $exp[0] ][ $exp[1] ] ) ) {
					unset( $current_config[ $exp[0] ][ $exp[1] ] );
				} else {
					$error = true;
				}
			} elseif ( $count == 3 ) {
				if ( isset( $current_config[ $exp[0] ][ $exp[1] ][ $exp[2] ] ) ) {
					unset( $current_config[ $exp[0] ][ $exp[1] ][ $exp[2] ] );
				} else {
					$error = true;
				}
			} elseif ( $count == 4 ) {
				if ( isset( $current_config[ $exp[0] ][ $exp[1] ][ $exp[2] ][ $exp[3] ] ) ) {
					unset( $current_config[ $exp[0] ][ $exp[1] ][ $exp[2] ][ $exp[3] ] );
				} else {
					$error = true;
				}
			}

			if ( isset( $error ) ) {
				CLI::error( CLI::_e( 'config', 'er_not_find', array( "[key]" => $key ) ) );
			}
		} else {

			if ( isset( $current_config[ $key ] ) ) {
				unset( $current_config[ $key ] );
			} else {
				CLI::error( CLI::_e( 'config', 'er_not_find', array( "[key]" => $key ) ) );
			}
		}

		if ( $wp_cli_config->save_config_file( $current_config ) ) {
			CLI::success( CLI::_e( 'config', 'remove', array( "[key]" => $_[0] ) ) );
		} else {
			CLI::error( CLI::_e( 'config', 'filed_update' ) );
		}
	}

	/**
	 * Reset Configuration.
	 *
	 * ## OPTIONS
	 *
	 * [--local]
	 * : Reset Configuration current working directory file
	 *
	 * ## EXAMPLES
	 *
	 *      # Reset Configuration
	 *      $ wp global-config reset
	 *      Success: Configuration reset.
	 *
	 *      # Reset local Configuration
	 *      $ wp global-config reset --local
	 *      Success: Local configuration reset.
	 *
	 * @when before_wp_load
	 */
	function reset( $_, $assoc ) {

		//Check local or global config file
		$type = ( isset( $assoc['local'] ) ? 'local' : 'global' );

		//Load WP-CLI-CONFIG class
		$wp_cli_config = new WP_CLI_CONFIG( $type );

		//Check File Exist
		if ( ! file_exists( $wp_cli_config->config_path ) ) {
			CLI::error( CLI::_e( 'config', 'empty_config' ) );
		}

		CLI::confirm( CLI::_e( 'config', 'confirm_reset' ) );
		$wp_cli_config->remove_config_file();
		CLI::success( CLI::_e( 'config', 'reset_config' ) );
	}

	/**
	 * Show Configuration list.
	 *
	 * ## OPTIONS
	 *
	 * [--local]
	 * : list of Configuration current working directory file.
	 *
	 * ## EXAMPLES
	 *
	 *      # get Configuration list
	 *      $ wp global-config list
	 *
	 *      # get list of local Configuration file
	 *      $ wp global-config reset --local
	 *
	 * @when before_wp_load
	 * @subcommand list
	 */
	function list_( $_, $assoc ) {

		//Check local or global config file
		$type = ( isset( $assoc['local'] ) ? array( 'local' ) : array( 'global', 'local' ) );

		//Load WP-CLI-CONFIG class
		$list = array();
		foreach ( $type as $t ) {
			$wp_cli_config = new WP_CLI_CONFIG( $t );
			$config_list   = $wp_cli_config->load_config_file();
			$list          = array_merge( $list, $config_list );
		}

		//Check Empty list
		if ( empty( $list ) ) {
			CLI::error( CLI::_e( 'config', 'empty_config' ) );
		}

		$list = WP_CLI_CONFIG::array_to_yaml( $list );
		CLI::log( $list );
	}

	/**
	 * Show Custom Config.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : name of parameter
	 *
	 * [--local]
	 * : get custom config from current working directory file.
	 *
	 * ## EXAMPLES
	 *
	 *      # get config
	 *      $ wp global-config get port
	 *
	 *      # get config from local
	 *      $ wp global-config get url --local
	 *
	 * @when before_wp_load
	 */
	function get( $_, $assoc ) {

		//Check local or global config file
		$type = ( isset( $assoc['local'] ) ? array( 'local' ) : array( 'global' ) );

		//Load WP-CLI-CONFIG class
		$list = array();
		foreach ( $type as $t ) {
			$wp_cli_config = new WP_CLI_CONFIG( $t );
			$config_list   = $wp_cli_config->load_config_file();
			$list          = array_merge( $list, $config_list );
		}

		//Check Empty list
		if ( empty( $list ) ) {
			CLI::error( CLI::_e( 'config', 'empty_config' ) );
		}

		//Check exist key
		if ( ! isset( $list[ $_[0] ] ) ) {
			CLI::error( CLI::_e( 'config', 'er_not_find', array( "[key]" => $_[0] ) ) );
		}

		$list = ( ! is_array( $list[ $_[0] ] ) ? $list[ $_[0] ] : WP_CLI_CONFIG::array_to_yaml( $list[ $_[0] ] ) );
		CLI::log( $list );
	}


}