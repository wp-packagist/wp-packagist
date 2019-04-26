<?php

namespace WP_CLI_PACKAGIST\Package\Params;

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Utility\CLI;
use WP_CLI_PACKAGIST\Utility\FileSystem;
use WP_CLI_PACKAGIST\Utility\PHP;
use WP_CLI_PACKAGIST\Utility\WP_CLI_ERROR;

class commands {
	/**
	 * Get Wordpress Package options
	 *
	 * @var string
	 */
	public $package_config;

	/**
	 * Core constructor.
	 */
	public function __construct() {
		/*
		 * Set config Global
		 */
		$this->package_config = Package::get_config( 'package' );
	}

	/**
	 * Validation Package
	 *
	 * @param $pkg_array
	 * @return array
	 */
	public function validation( $pkg_array ) {

		//Create new validation
		$valid = new WP_CLI_ERROR();

		//Get commands parameter
		$parameter = $pkg_array['commands'];

		//Sanitize Custom Key
		$check = $this->sanitize_commands( $parameter, true );
		if ( $check['status'] === false ) {
			foreach ( $check['data'] as $error ) {
				$valid->add_error( $error );
				break;
			}
		} else {
			//Get Sanitize Data
			$return['commands'] = array_shift( $check['data'] );

			//Push To sanitize return data
			$valid->add_success( $return['commands'] );
		}

		return $valid->result();
	}

	/**
	 * Sanitize Commands Params
	 *
	 * @param $array
	 * @param bool $validate
	 * @return string|boolean|array
	 * @since 1.0.0
	 */
	public function sanitize_commands( $array, $validate = false ) {

		//Create new validation
		$valid = new WP_CLI_ERROR();

		//Check is String
		if ( is_string( $array ) ) {

			$valid->add_error( CLI::_e( 'package', 'is_string', array( "[key]" => "commands: { .." ) ) );
		} elseif ( empty( $array ) ) {

			//Check Empty Array
			$valid->add_error( CLI::_e( 'package', 'empty_val', array( "[key]" => "commands: { .." ) ) );
		} else {

			//Check is Assoc array
			if ( PHP::is_assoc_array( $array ) === false ) {
				$valid->add_error( CLI::_e( 'package', 'er_valid', array( "[key]" => "commands: { .." ) ) );
			} else {

				//Create Array Export data
				$command_list = array();

				//Prepare Comment List
				foreach ( $array as $command => $type ) {

					//Check Empty
					if ( empty( $type ) ) {
						$valid->add_error( CLI::_e( 'package', 'empty_val', array( "[key]" => "commands: { '" . $command . "' .." ) ) );
						break;
					}

					//Check is array
					if ( is_array( $type ) ) {

						//Check is not Assoc Array
						if ( PHP::is_assoc_array( $type ) === true ) {
							$valid->add_error( CLI::_e( 'package', 'er_valid', array( "[key]" => "commands: { '" . $command . "' .." ) ) );
							break;
						} else {

							//Push to List
							foreach ( $type as $path ) {
								if ( ! empty( $path ) ) {
									$command_list[] = array( 'command' => $command, 'where' => $path );
								}
							}
						}
					} else {
						//Push to list
						$command_list[] = array( 'command' => $command, 'where' => $type );
					}
				}

				//Validation Every Comment
				if ( ! $valid->is_cli_error() ) {

					//Check Empty Value in array
					$k = 0;
					foreach ( $command_list as $commends ) {

						// Prepare Arg
						$command = $commends['command'];
						$type    = $commends['where'];

						//Check if array
						if ( is_array( $type ) ) {

							$valid->add_error( CLI::_e( 'package', 'er_string_command', array( "[key]" => $command ) ) );
							break;
						} elseif ( empty( PHP::to_lower_string( $type ) ) ) {

							//Check is empty path
							$valid->add_error( CLI::_e( 'package', 'empty_val', array( "[key]" => "commands: { '" . $command . "' .." ) ) );
							break;
						} else {

							//trim
							$type = trim( $type );

							//Check Validation Path or type
							if ( ! in_array( $type, $this->package_config['wp-cli-command'] ) ) {

								# Check Path contain Drive name
								if ( ':' === substr( $command, 1, 1 ) ) {

									$valid->add_error( CLI::_e( 'package', 'er_contain_drive_cmd', array( "[key]" => $command ) ) );
									break;
								} else {

									//Sanitize folder Path
									$type = ltrim( FileSystem::normalize_path( $type ), "/" );

									//Set Commands Type
									$command_type = 'custom';
								}
							} else {
								//Set Command Type
								$command_type = 'wp-cli';

								//Sanitize Command Type
								$type = 'wp-cli';
							}
						}

						//Check Command name is exist
						if ( CLI::command_exists( $command ) === false ) {

							//Get First Parameter
							$prompt = explode( " ", $command );
							$prompt = PHP::remove_whitespace_word( trim( $prompt[0] ) );
							$valid->add_error( CLI::_e( 'package', 'er_register_cmd', array( "[key]" => $prompt, "[where]" => "internal or external" ) ) );
							break;
						} else {

							//Check Exist wp-cli command
							if ( $command_type == "wp-cli" ) {
								$get_command = CLI::exist_wp_cli_command( $command );
								if ( $get_command['status'] === false ) {

									$valid->add_error( CLI::_e( 'package', 'er_register_cmd', array( "[key]" => PHP::substr( $command, 30 ) . " ..", "[where]" => "WP-CLI" ) ) );
									break;
								} else {

									//Check forbidden command
									$sub_command = explode( " ", $get_command['cmd'] );
									foreach ( Package::get_config( 'package', 'forbidden_wp_cli_command' ) as $wp_cli ) {
										if ( PHP::to_lower_string( $sub_command[0] ) == $wp_cli ) {
											$valid->add_error( CLI::_e( 'package', 'er_forbidden_cmd', array( "[key]" => $sub_command[0] ) ) );
											break;
										}
									}

									//set sanitize command
									$sanitize_command = $get_command['cmd'];
								}
							} else {
								//Sanitize Command
								$sanitize_command = trim( PHP::remove_whitespace_word( $command ) );
							}
						}

						//Push array sanitize data
						$command_list[ $k ] = array(
							'command' => $sanitize_command,
							'where'   => $type
						);
						$k ++;
					}
				}

				//Push To sanitize return data
				if ( ! $valid->is_cli_error() ) {
					$array = $command_list;
					$valid->add_success( $array );
				}
			}
		}

		return ( $validate === true ? $valid->result() : $array );
	}

}