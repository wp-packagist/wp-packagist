<?php

namespace WP_CLI_PACKAGIST\Package\Params;

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Utility\CLI;
use WP_CLI_PACKAGIST\Utility\FileSystem;
use WP_CLI_PACKAGIST\Utility\PHP;
use WP_CLI_PACKAGIST\Utility\WP_CLI_ERROR;
use WP_CLI_PACKAGIST\Package\Utility\install;

class dir {
	/**
	 * Default Parameter
	 *
	 * @var array
	 */
	public $params_keys = array( 'wp-content', 'plugins', 'themes', 'uploads' );

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
		 * Set Config Global
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

		//Get Dir parameter
		$parameter = $pkg_array['dir'];

		//Sanitize Custom Key
		$check = $this->sanitize_dir( $parameter, true );
		if ( $check['status'] === false ) {
			foreach ( $check['data'] as $error ) {
				$valid->add_error( $error );
				break;
			}
		} else {
			//Get Sanitize Data
			$return['dir'] = array_shift( $check['data'] );

			//Push To sanitize return data
			$valid->add_success( $return['dir'] );
		}

		return $valid->result();
	}

	/**
	 * Sanitize Dir Params
	 *
	 * @param $array
	 * @param bool $validate
	 * @return string|boolean|array
	 * @since 1.0.0
	 */
	public function sanitize_dir( $array, $validate = false ) {

		//List of all Unique key
		$unique_key = $this->params_keys;

		//Create new validation
		$valid = new WP_CLI_ERROR();

		//Check is Empty
		if ( empty( $array ) ) {

			$valid->add_error( CLI::_e( 'package', 'empty_val', array( "[key]" => "dir: { .." ) ) );
		} elseif ( is_string( $array ) ) {

			//Check is String
			$valid->add_error( CLI::_e( 'package', 'is_string', array( "[key]" => "dir: { .." ) ) );
		} else {

			//Check is not assoc array
			if ( PHP::is_assoc_array( $array ) === false ) {

				$valid->add_error( CLI::_e( 'package', 'er_valid', array( "[key]" => "dir: { .." ) ) );
			} else {

				//Convert to lowercase key
				$array = array_change_key_case( $array, CASE_LOWER );

				//Check Anonymous Parameter
				foreach ( $array as $k => $val ) {
					if ( ! in_array( strtolower( $k ), $this->params_keys ) ) {
						$valid->add_error( CLI::_e( 'package', 'er_unknown_param', array( "[key]" => 'dir: { "' . $k . '" ..' ) ) );
						break;
					}
				}

				//Check Empty Value
				foreach ( $this->params_keys as $k ) {
					if ( array_key_exists( $k, $array ) === true ) {

						//Check if array show error
						if ( is_array( $array[ $k ] ) ) {

							$valid->add_error( CLI::_e( 'package', 'is_not_string', array( "[key]" => "dir: { " . $k . ": .." ) ) );
							break;
						} else {

							//Check Drive Path in Value
							if ( ':' === substr( $array[ $k ], 1, 1 ) ) {

								$valid->add_error( CLI::_e( 'package', 'path_contain_drive', array( "[key]" => "dir: { " . $k . ": .." ) ) );
								break;
							} else {

								//Sanitize folder name
								$folder = PHP::sanitize_file_name( $array[ $k ] );

								//Check is Empty
								if ( empty( $folder ) ) {
									$valid->add_error( CLI::_e( 'package', 'empty_val', array( "[key]" => "dir: { " . $k . ": .." ) ) );
									break;
								}
							}
						}
					}
				}

				//Check Duplicate value
				if ( ! $valid->is_cli_error() ) {
					$before_val = array();
					foreach ( $unique_key as $u_k ) {
						$is_duplicate = false;
						if ( array_key_exists( $u_k, $array ) === true ) {
							if ( ! in_array( trim( $array[ $u_k ] ), $before_val ) ) {
								$before_val[] = trim( $array[ $u_k ] );
							} else {
								$is_duplicate = true;
							}
						}

						//Push To Error if duplicate value
						if ( $is_duplicate === true ) {
							$valid->add_error( CLI::_e( 'package', 'nv_duplicate', array( "[key]" => $u_k, "[array]" => "dir: { .." ) ) );
						}
					}
				}
			}

			//Sanitize All Value
			if ( ! $valid->is_cli_error() ) {

				$list = array();
				foreach ( $array as $k => $v ) {

					//Sanitize Folder name
					$sanitize_file_name = self::sanitize_folder_name( $v );

					//Check Folder not empty and Folder value is not default
					if ( ! empty( $sanitize_file_name ) and $k != $sanitize_file_name ) {

						//Push to list if not any wrong
						$list[ $k ] = $sanitize_file_name;

						//For wp-content we must check without slash
						if ( $k == "wp-content" and $k == trim( $sanitize_file_name, "/" ) ) {
							unset( $list[ $k ] );
						}
					}
				}

				//Push sanitize Data
				$array = $list;
				$valid->add_success( $array );
			}
		}

		return ( $validate === true ? $valid->result() : $array );
	}

	/**
	 * Create Default Value for init command
	 *
	 * @param $args
	 * @param bool $validate
	 * @return mixed
	 */
	public function init( $args, $validate = false ) {

		//Create Default Value
		$default = array( 'wp-content' => 'wp-content' );

		//Get List Default params
		$list = array(
			'wp-content' => 'wp_content_dir',
			'plugins'    => 'plugins_dir',
			'themes'     => 'themes_dir',
			'uploads'    => 'uploads_dir'
		);
		foreach ( $list as $k => $v ) {
			//check exist key
			if ( array_key_exists( $v, $args ) ) {
				//check empty value
				if ( ! empty( trim( $args[ $v ] ) ) ) {
					$default[ $k ] = $args[ $v ];
				}
			}
		}

		//Check error
		$error   = array();
		$success = $this->sanitize_dir( $default, true );
		if ( $success['status'] === false ) {
			$error = $success['data'];
		}

		return ( $validate === true ? $error : $this->sanitize_dir( $default ) );
	}

	/**
	 * Sanitize Folder name
	 *
	 * @param $folder
	 * @return string
	 */
	public static function sanitize_folder_name( $folder ) {

		//Replace All backslash to slash
		$folder = FileSystem::normalize_path( $folder );

		//trim Slash right
		$folder = rtrim( $folder, "/" );

		//Sanitize name
		$folder = explode( "/", $folder );

		//Check Sanitize File name
		$folder_name = '';
		foreach ( $folder as $name ) {
			if ( $name == "" ) {
				$folder_name .= '/';
			} else {
				$folder_name .= PHP::to_lower_string( PHP::sanitize_file_name( $name ) ) . "/";
			}
		}

		//remove trim in last
		$folder_name = rtrim( $folder_name, "/" );

		return $folder_name;
	}

	/**
	 * install Command
	 *
	 * @param $pkg_array
	 * @param array $args
	 * @return array
	 */
	public function install( $pkg_array, $args = array() ) {

		//Prepare Step
		$step     = $args['step'];
		$all_step = $args['all_step'];

		//Check exist DIR
		if ( isset( $pkg_array['dir'] ) and count( $pkg_array['dir'] ) > 0 ) {
			install::install_log( $step, $all_step, CLI::_e( 'package', 'change_dir' ) );
			\WP_CLI_PACKAGIST\Package\Arguments\Dir::update_dir( $this->params_keys, $pkg_array['dir'], $pkg_array );
			$step ++;
		}

		return array( 'status' => true, 'step' => $step );
	}

}