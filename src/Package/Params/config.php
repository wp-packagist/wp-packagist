<?php

namespace WP_CLI_PACKAGIST\Package\Params;

use WP_CLI_PACKAGIST\Package\Arguments\Admin;
use WP_CLI_PACKAGIST\Package\Arguments\Core;
use WP_CLI_PACKAGIST\Package\Arguments\Users;
use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Arguments\Config as Config_Arg;
use WP_CLI_PACKAGIST\Package\Arguments\Cookie;
use WP_CLI_PACKAGIST\Package\Arguments\Dir;
use WP_CLI_PACKAGIST\Package\Arguments\Timezone;
use WP_CLI_PACKAGIST\Package\Utility\install;

class config {
	/**
	 * Default Parameter
	 *
	 * @var array
	 */
	public $params_keys = array( 'site', 'admin', 'users', 'constant', 'options', 'cookie', 'rest-api', 'permalink', 'timezone', 'theme' );

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
		 * Set Global Config
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
		$valid = new \WP_CLI_ERROR();

		//Get Config parameter
		$parameter = $pkg_array['config'];

		//Require Key
		$require_key = array( 'site' );

		//Check is empty
		if ( empty( $parameter ) ) {
			$valid->add_error( Package::_e( 'package', 'empty_val', array( "[key]" => "config" ) ) );
		} else {

			//check is string
			if ( is_string( $parameter ) ) {
				$valid->add_error( Package::_e( 'package', 'is_string', array( "[key]" => "config" ) ) );
			} else {

				//Check is not Assoc array
				if ( \WP_CLI_Util::is_assoc_array( $parameter ) === false ) {
					$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config" ) ) );
				} else {

					//Convert to lowercase key
					$parameter = array_change_key_case( $parameter, CASE_LOWER );

					//Check require key
					$check_require_key = \WP_CLI_Util::check_require_array( $parameter, $require_key, false );
					if ( $check_require_key['status'] === false ) {
						foreach ( $check_require_key['data'] as $key ) {
							$valid->add_error( Package::_e( 'package', 'not_exist_key', array( "[require]" => $key, "[key]" => "config: { .. " ) ) );
							break;
						}
					}

					//Check Anonymous Parameter
					foreach ( $parameter as $k => $val ) {
						if ( ! in_array( strtolower( $k ), $this->params_keys ) ) {
							$valid->add_error( Package::_e( 'package', 'er_unknown_param', array( "[key]" => 'config: { "' . $k . '" ..' ) ) );
						}
					}

					//Validation Separate Parameter
					if ( ! $valid->is_cli_error() ) {
						$return = array();
						foreach ( $this->params_keys as $keys ) {

							//Check Exist Key
							if ( array_key_exists( $keys, $parameter ) ) {

								//Sanitize Custom Key
								$check = $this->{'sanitize_' . str_replace( "-", "_", $keys )}( $parameter[ $keys ], true );
								if ( $check['status'] === false ) {
									foreach ( $check['data'] as $error ) {
										$valid->add_error( $error );
										break;
									}
								} else {
									//Get Sanitize Data
									$return[ $keys ] = array_shift( $check['data'] );
								}
							}

							//Push Admin info if not exist after Site
							if ( $keys == "site" and ! array_key_exists( "admin", $parameter ) ) {
								$return["admin"] = $this->get_default_users_arg();
							}
						}

						//Check Network Sub-domain for localhost
						if ( ! $valid->is_cli_error() ) {
							if ( $this->check_network_subdomain( $pkg_array ) === false ) {
								$valid->add_error( Package::_e( 'package', 'network_domain_local', array( "[url]" => "localhost" ) ) );
							}
						}

						//Check Duplicate Admin email or admin user login in Users
						if ( ! $valid->is_cli_error() ) {
							$_is = Admin::check_admin_duplicate( $return );
							if ( $_is['status'] === false ) {
								$valid->add_error( array_shift( $_is['data'] ) );
							}
						}

						//Push To sanitize return data
						$valid->add_success( $return );
					}
				}
			}
		}

		return $valid->result();
	}

	/**
	 * Sanitize Site Params
	 *
	 * @param $array
	 * @param bool $validate
	 * @return string|boolean|array
	 * @since 1.0.0
	 */
	public function sanitize_site( $array, $validate = false ) {

		//List of require Key
		$require_key = array( 'title', 'url' );

		//Create new validation
		$valid = new \WP_CLI_ERROR();

		//Check is empty
		if ( empty( $array ) ) {
			$valid->add_error( Package::_e( 'package', 'empty_val', array( "[key]" => "config: { site: .." ) ) );
		} else {

			//check is string
			if ( is_string( $array ) ) {
				$valid->add_error( Package::_e( 'package', 'is_string', array( "[key]" => "config: { site: .." ) ) );
			} else {

				//Check is not Assoc array
				if ( \WP_CLI_Util::is_assoc_array( $array ) === false ) {
					$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { site: .." ) ) );
				} else {

					//Convert to lowercase key
					$parameter = array_change_key_case( $array, CASE_LOWER );

					//Check require key
					$check_require_key = \WP_CLI_Util::check_require_array( $parameter, $require_key, false );
					if ( $check_require_key['status'] === false ) {
						foreach ( $check_require_key['data'] as $key ) {
							$valid->add_error( Package::_e( 'package', 'not_exist_key', array( "[require]" => $key, "[key]" => "config: { site: { .. " ) ) );
							break;
						}
					}

					//Check Anonymous Parameter
					foreach ( $parameter as $k => $val ) {
						if ( ! in_array( strtolower( $k ), $require_key ) ) {
							$valid->add_error( Package::_e( 'package', 'er_unknown_param', array( "[key]" => 'config: { site: { "' . $k . '" ..' ) ) );
						}
					}

					//Validation Separate Parameter
					if ( ! $valid->is_cli_error() ) {

						//Validate title
						if ( empty( $parameter['title'] ) ) {

							$valid->add_error( Package::_e( 'package', 'empty_val', array( "[key]" => "config: { site: { title: .." ) ) );
						} elseif ( is_array( $parameter['title'] ) || is_object( $parameter['title'] ) ) {

							$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { site: { title: .." ) ) );
						} else {

							//save original title
							$raw_title = $parameter['title'];

							//Strip all tags
							$var = strip_tags( $raw_title );

							//Check Contain Html
							if ( \WP_CLI_Util::to_lower_string( $var ) != \WP_CLI_Util::to_lower_string( $raw_title ) ) {
								$valid->add_error( Package::_e( 'package', 'er_contain_html', array( "[key]" => "config: { site: { title: .." ) ) );
							} else {

								//Sanitize Title
								$parameter['title'] = trim( $parameter['title'] );

								//Check Website Url
								if ( empty( $parameter['url'] ) ) {
									$valid->add_error( Package::_e( 'package', 'empty_val', array( "[key]" => "config: { site: { url: .." ) ) );
								} elseif ( is_array( $parameter['url'] ) || is_object( $parameter['url'] ) ) {

									$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { site: { url: .." ) ) );
								} else {

									//Check validate Url
									if ( filter_var( $parameter['url'], FILTER_VALIDATE_URL ) === false ) {
										$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { site: { url: .." ) ) );
									} else {

										//Sanitize Website Url
										$parameter['url'] = trim( filter_var( $parameter['url'], FILTER_VALIDATE_URL ) );
										$parameter['url'] = rtrim( $parameter['url'], "/" ); //For Push to site url options

										// Check connecting to url
										if ( defined( 'WP_CLI_PACKAGIST_RUN_CHECK_SITE_URL' ) ) {
											$check_url = $this->_check_site_url( $parameter['url'] );
											if ( $check_url['status'] === false ) {
												$valid->add_error( $check_url['data'] );
											}
										}

										//Push To sanitize return data
										$valid->add_success( $parameter );
									}
								}
							}
						}
					}
				}
			}
		}

		return ( $validate === true ? $valid->result() : $array );
	}

	/**
	 * Check Connect Site Url
	 *
	 * @param $url
	 * @return array
	 */
	public function _check_site_url( $url ) {

		//Get Status
		$status = false;

		//Load Mustache
		$mustache = \WP_CLI_FileSystem::load_mustache( WP_CLI_PACKAGIST_TEMPLATE_PATH );

		//Create GET Request Key
		$get_key = strtolower( WP_CLI_Util::random_key( 80, false ) );

		//Create File name
		$file_name = strtolower( WP_CLI_Util::random_key( 40, false ) ) . ".php";
		$file_path = rtrim( str_ireplace( "\\", "/", getcwd() ), "/" ) . "/" . $file_name;

		//Get Render Data
		$data = array(
			'GET_KEY'   => $get_key,
			'FILE_NAME' => $file_name
		);

		//Add New code in Files
		$content = $mustache->render( 'check-siteurl', array_merge( $data ) );

		//Create File
		\WP_CLI_FileSystem::file_put_content( $file_path, $content );

		//Connect To file
		$response = \WP_CLI_Helper::http_request( rtrim( $url, "/" ) . "/" . $file_name . '?wp_cli_app_package_check_url=' . $get_key );

		//Check Error Connecting
		if ( $response != false ) {

			//Check Response
			$json_data = json_decode( $response, true );
			if ( $json_data != null ) {
				if ( isset( $json_data['status'] ) and $json_data['status'] == "200" ) {
					$status = true;
				}
			}
		}

		//Remove File From Server if exist
		if ( file_exists( $file_path ) ) {
			\WP_CLI_FileSystem::remove_file( $file_path );
		}

		//Return Status
		if ( $status === true ) {
			return array( 'status' => true );
		} else {
			return array( 'status' => false, 'data' => Package::_e( 'package', 'er_incorrect_site_url', array( "[url]" => preg_replace( "(^https?://)", "", $url ) ) ) );
		}
	}

	/**
	 * Sanitize Admin Params
	 *
	 * @param $array
	 * @param bool $validate
	 * @return string|boolean|array
	 * @since 1.0.0
	 */
	public function sanitize_admin( $array, $validate = false ) {

		//List of require Key
		$require_key = array( 'admin_user', 'admin_email' );

		//This field if was existed then not empty at all
		$not_empty = array( 'admin_user', 'admin_pass', 'admin_email', 'display_name', 'role', 'first_name', 'last_name' );

		//Create new validation
		$valid = new \WP_CLI_ERROR();

		//Get Default value from Config
		$config = $this->get_default_users_arg();

		//Check is String
		if ( is_string( $array ) || is_object( $array ) ) {
			$valid->add_error( Package::_e( 'package', 'is_string', array( "[key]" => "config: { admin: .." ) ) );

			//Check Empty Array
		} elseif ( empty( $array ) ) {
			$valid->add_error( Package::_e( 'package', 'empty_val', array( "[key]" => "config: { admin: .." ) ) );

		} else {

			//Check is assoc Array
			if ( \WP_CLI_Util::is_assoc_array( $array ) === false ) {
				$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { admin: .." ) ) );
			}

			if ( ! $valid->is_cli_error() ) {

				//Convert to lowercase
				$array = array_change_key_case( $array, CASE_LOWER );

				//Check Require Key
				$check_require_key = \WP_CLI_Util::check_require_array( $array, $require_key, true );
				if ( $check_require_key['status'] === false ) {
					foreach ( $check_require_key['data'] as $key ) {

						//Set Default Variable For Admin User
						if ( $key == "admin_email" ) {
							$array['admin_email'] = $config['admin_email'];
						} elseif ( $key == "admin_user" ) {
							$array['admin_user'] = $config['admin_user'];
						} else {
							$valid->add_error( Package::_e( 'package', 'not_exist_key', array( "[require]" => $key, "[key]" => "config: { admin: { [" . $key . "]" ) ) );
							break;
						}
					}
				}

				//Check Empty value Key
				foreach ( $not_empty as $a_k ) {
					if ( array_key_exists( $a_k, $array ) === true and ( empty( $array[ $a_k ] ) || is_array( $array[ $a_k ] ) ) ) {
						$valid->add_error( Package::_e( 'package', 'empty_val', array( "[key]" => "config: { admin: { ['" . $a_k . "']" ) ) );
						break;
					}
				}
			}

			//Disable 'Role' for Admin User
			if ( array_key_exists( 'role', $array ) ) {
				$valid->add_error( Package::_e( 'package', 'forbidden_role' ) );
			}

			//Check User Login
			if ( array_key_exists( "admin_user", $array ) === true and mb_strlen( $array['admin_user'] ) > 60 ) {
				$valid->add_error( Package::_e( 'package', 'nv_user_login', array( "[key]" => "config: { admin: { ..", "[which]" => "admin_user" ) ) );
			}

			//Check User Email
			if ( array_key_exists( "admin_email", $array ) === true and filter_var( $array['admin_email'], FILTER_VALIDATE_EMAIL ) === false ) {
				$valid->add_error( Package::_e( 'package', 'nv_user_email', array( "[key]" => "config: { admin: { ..", "[which]" => "admin_email" ) ) );
			}

			//Check Exist Password
			if ( ! array_key_exists( 'admin_pass', $array ) ) {
				$array['admin_pass'] = $config['admin_pass'];
			}

			//Check Meta Value
			if ( array_key_exists( 'meta', $array ) ) {

				//Get User meta
				$meta = $array['meta'];

				//check is array
				if ( empty( $meta ) ) {
					$valid->add_error( Package::_e( 'package', 'empty_val', array( "[key]" => "config: { admin: { meta: .." ) ) );
				} else {

					if ( is_string( $meta ) ) {
						$valid->add_error( Package::_e( 'package', 'is_string', array( "[key]" => "config: { admin: { meta: .." ) ) );
					} else {

						//Check is not Assoc array
						if ( \WP_CLI_Util::is_assoc_array( $meta ) === false ) {
							$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { admin: { meta: .." ) ) );
						} else {

							//Sanitize Meta Key
							foreach ( $meta as $meta_name => $meta_value ) {
								$key = trim( str_ireplace( " ", "", ( $meta_name ) ) );
								unset( $array['meta'][ $meta_name ] );
								$array['meta'][ $key ] = $meta_value;
							}
						}
					}
				}
			} # End Admin Meta
		}

		//Push To sanitize return data
		if ( ! $valid->is_cli_error() ) {
			$valid->add_success( $array );
		}

		//return
		return ( $validate === true ? $valid->result() : $array );
	}

	/**
	 * Sanitize Users Params
	 *
	 * @param $array
	 * @param bool $validate
	 * @return string|boolean|array
	 * @since 1.0.0
	 */
	public function sanitize_users( $array, $validate = false ) {

		//List of require Key
		$require_key = array( 'user_login', 'user_email' );

		//this field if was existed then not empty at all
		$not_empty = array( 'user_login', 'user_pass', 'user_email', 'display_name', 'role', 'first_name', 'last_name' );

		//List of All Unique Key [ not duplicate value ]
		$unique_key = array( 'user_login', 'user_email' );

		//Create new validation
		$valid = new \WP_CLI_ERROR();

		//Get Default value from Config
		$config = $this->get_default_users_arg();

		//Check is String
		if ( is_string( $array ) || is_object( $array ) ) {
			$valid->add_error( Package::_e( 'package', 'is_string', array( "[key]" => "config: { users: .." ) ) );

			//Check Empty Array
		} elseif ( empty( $array ) ) {
			$valid->add_error( Package::_e( 'package', 'empty_val', array( "[key]" => "config: { users: .." ) ) );

		} else {

			//Convert To Multi array
			if ( \WP_CLI_Util::is_multi_array( $array ) === false ) {
				$list   = array();
				$list[] = $array;
				$array  = $list;
			}

			//Check Require Or Empty Key in arrays
			for ( $x = 0; $x < count( $array ); $x ++ ) {

				//Convert to lowercase
				$array[ $x ] = array_change_key_case( $array[ $x ], CASE_LOWER );

				//Check Require Key
				$check_require_key = \WP_CLI_Util::check_require_array( $array[ $x ], $require_key, true );
				if ( $check_require_key['status'] === false ) {
					foreach ( $check_require_key['data'] as $key ) {
						$valid->add_error( Package::_e( 'package', 'not_exist_key', array( "[require]" => $key, "[key]" => "config: { users: { [" . ( $x + 1 ) . "]" ) ) );
						break;
					}
				}

				//Check Empty value Key
				foreach ( $not_empty as $a_k ) {
					if ( array_key_exists( $a_k, $array[ $x ] ) === true and ( empty( $array[ $x ][ $a_k ] ) || is_array( $array[ $x ][ $a_k ] ) ) ) {
						$valid->add_error( Package::_e( 'package', 'empty_val', array( "[key]" => "config: { users: { [" . ( $x + 1 ) . "]['" . $a_k . "']" ) ) );
						break;
					}
				}

				//Set Default Password
				if ( ! array_key_exists( 'user_pass', $array[ $x ] ) ) {
					$_push_pass = true;
					if ( Core::is_installed_wordpress() ) {
						$_exist_in_db = Users::check_exist_user( array( 'user_login' => $array[ $x ]['user_login'], 'user_email' => $array[ $x ]['user_email'] ) );
						if ( $_exist_in_db['status'] ) {
							$_push_pass = false;
						}
					}
					if ( $_push_pass ) {
						$array[ $x ]['user_pass'] = $config['admin_pass'];
					}
				}
			}

			//Check Duplicate value
			foreach ( $unique_key as $u_k ) {
				$before_val   = array();
				$is_duplicate = false;
				for ( $x = 0; $x < count( $array ); $x ++ ) {
					if ( array_key_exists( $u_k, $array[ $x ] ) === true ) {
						if ( ! in_array( trim( $array[ $x ][ $u_k ] ), $before_val ) ) {
							$before_val[] = trim( $array[ $x ][ $u_k ] );
						} else {
							$is_duplicate = true;
						}
					}
				}

				//Push To Error iF Duplicate
				if ( $is_duplicate === true ) {
					$valid->add_error( Package::_e( 'package', 'nv_duplicate', array( "[key]" => $u_k, "[array]" => "config: { users: .." ) ) );
				}
			}

			//Check Custom Validation
			for ( $x = 0; $x < count( $array ); $x ++ ) {

				//Check User Login
				if ( array_key_exists( "user_login", $array[ $x ] ) === true and mb_strlen( $array[ $x ]['user_login'] ) > 60 ) {
					$valid->add_error( Package::_e( 'package', 'nv_user_login', array( "[key]" => "config: { users: { [" . ( $x + 1 ) . "]", "[which]" => "user_login" ) ) );
					break;
				}

				//Check User Email
				if ( array_key_exists( "user_email", $array[ $x ] ) === true and filter_var( $array[ $x ]['user_email'], FILTER_VALIDATE_EMAIL ) === false ) {
					$valid->add_error( Package::_e( 'package', 'nv_user_email', array( "[key]" => "config: { users: { [" . ( $x + 1 ) . "]", "[which]" => "user_email" ) ) );
					break;
				}

				//Check User Meta Keys
				if ( array_key_exists( 'meta', $array[ $x ] ) ) {

					//Get User meta
					$meta = $array[ $x ]['meta'];

					//Check is empty
					if ( empty( $meta ) ) {
						$valid->add_error( Package::_e( 'package', 'empty_val', array( "[key]" => "config: { users[" . ( $x + 1 ) . "]: { meta: { .." ) ) );
					} else {

						if ( is_string( $meta ) ) {
							$valid->add_error( Package::_e( 'package', 'is_string', array( "[key]" => "config: { users[" . ( $x + 1 ) . "]: { meta: { .." ) ) );
						} else {

							//Check is not Assoc array
							if ( \WP_CLI_Util::is_assoc_array( $meta ) === false ) {
								$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { users[" . ( $x + 1 ) . "]: { meta: { .." ) ) );
							} else {

								//Sanitize Meta Key
								foreach ( $meta as $meta_name => $meta_value ) {
									$key = trim( str_ireplace( " ", "", ( $meta_name ) ) );
									unset( $array[ $x ]['meta'][ $meta_name ] );
									$array[ $x ]['meta'][ $key ] = $meta_value;
								}
							}
						}
					}
				} # End User Meta
			}
		}

		//Push To sanitize return data
		if ( ! $valid->is_cli_error() ) {
			$valid->add_success( $array );
		}

		//return
		return ( $validate === true ? $valid->result() : $array );
	}

	/**
	 * Get Default Users Value From WP_CLI_APP Config
	 */
	public function get_default_users_arg() {

		//Create Empty Obj
		$result = array();

		//Arg list
		$list = array( 'admin_user', 'admin_email', 'admin_pass' );

		//Get Default
		foreach ( $list as $k ) {

			//Check in Config
			try {
				$get = \WP_CLI_CONFIG::get( $k );
			} catch ( \Exception $e ) {
				$get = false;
			}
			if ( $get != false ) {
				$result[ $k ] = $get;
			} else {
				$result[ $k ] = $this->package_config['default'][ $k ];
			}
		}

		return $result;
	}

	/**
	 * Sanitize Constant Params
	 *
	 * @param $array
	 * @param bool $validate
	 * @return string|boolean|array
	 * @since 1.0.0
	 */
	public function sanitize_constant( $array, $validate = false ) {

		//List of forbidden Keys
		$forbidden_values = array(
			'mysql'   => array(
				'list' => array( 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST', 'DB_CHARSET', 'DB_COLLATE' ),
				'help' => "this key is used in the 'mysql: { ..' parameter."
			),
			'core'    => array(
				'list' => array( 'ABSPATH' ),
				'help' => "this key is used in the WordPress core."
			),
			'network' => array(
				'list' => array( 'WP_ALLOW_MULTISITE', 'MULTISITE', 'SUBDOMAIN_INSTALL', 'DOMAIN_CURRENT_SITE', 'PATH_CURRENT_SITE' ),
				'help' => "this key is used in the 'core: { network: { ..' parameter."
			)
		);

		//Create new validation
		$valid = new \WP_CLI_ERROR();

		//Check is empty
		if ( empty( $array ) ) {
			$valid->add_error( Package::_e( 'package', 'empty_val', array( "[key]" => "config: { constant: .." ) ) );
		} else {

			//check is string
			if ( is_string( $array ) ) {
				$valid->add_error( Package::_e( 'package', 'is_string', array( "[key]" => "config: { constant: .." ) ) );
			} else {

				//Check is not Assoc array
				if ( \WP_CLI_Util::is_assoc_array( $array ) === false ) {
					$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { constant: .." ) ) );
				} else {

					//Check Prohibit Keys
					foreach ( $forbidden_values as $p_key => $p_val ) {
						foreach ( $p_val['list'] as $l ) {
							foreach ( $array as $key => $val ) {
								if ( trim( strtoupper( $key ) ) == $l ) {
									$valid->add_error( Package::_e( 'package', 'er_forbidden', array( "[key]" => "config: { constant: { ['" . trim( strtoupper( $key ) ) . "']" ) ) . '' . $p_val['help'] );
									break;
								}
							}
						}
					}

					//Check is not Array or object value
					foreach ( $array as $key => $val ) {
						if ( is_array( $val ) || is_object( $val ) ) {
							$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { constant: { ['" . $key . "']" ) ) );
						}
					}

					//Validation Separate Parameter
					if ( ! $valid->is_cli_error() ) {

						//Sanitize Key name and value
						foreach ( $array as $k => $v ) {
							//sanitize Key
							$key = trim( str_ireplace( " ", "", ( $k ) ) );

							//sanitize value
							$val = $v;
							if ( ! is_bool( $v ) ) {
								$val = trim( $v );
							}

							//Remove and Push again
							unset( $array[ $k ] );
							$array[ $key ] = $val;
						}

						//Push To sanitize return data
						$valid->add_success( $array );
					}
				}
			}
		}

		return ( $validate === true ? $valid->result() : $array );
	}

	/**
	 * Sanitize Package Cookie parameter
	 *
	 * @param $var
	 * @param bool $validate
	 * @return string|boolean|array
	 * @since 1.0.0
	 */
	public function sanitize_cookie( $var, $validate = false ) {

		//Create new validation
		$valid = new \WP_CLI_ERROR();

		//Check is Empty
		if ( empty( $var ) ) {
			$valid->add_error( Package::_e( 'package', 'empty_val', array( "[key]" => "config: { cookie: .." ) ) );

			//Check is array
		} elseif ( is_array( $var ) ) {
			$valid->add_error( Package::_e( 'package', 'is_not_string', array( "[key]" => "config: { cookie: .." ) ) );

		} else {

			//Save original Package Cookie prefix
			$pkg_raw = $var;

			//Check Preg
			$var = preg_replace( $this->package_config['preg_prefix'], '', $var );

			//Check valid
			if ( \WP_CLI_Util::to_lower_string( $var ) != \WP_CLI_Util::to_lower_string( $pkg_raw ) ) {
				$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { cookie: .." ) ) );
			} else {

				//Return Sanitize Data
				$valid->add_success( $var );
			}
		}

		return ( $validate === true ? $valid->result() : $var );
	}

	/**
	 * Sanitize Options Params
	 *
	 * @param $array
	 * @param bool $validate
	 * @return string|boolean|array
	 * @since 1.0.0
	 */
	public function sanitize_options( $array, $validate = false ) {

		//List of forbidden Keys
		$forbidden_values = array(
			'url'      => array(
				'list' => array( 'siteurl', 'home' ),
				'help' => "this key is used in the 'config: { site: { ..' parameter."
			),
			'timezone' => array(
				'list' => array( 'timezone_string', 'gmt_offset' ),
				'help' => "this key is used in the 'config: { timezone: { ..' parameter."
			),
			'theme'    => array(
				'list' => array( 'template', 'stylesheet' ),
				'help' => "this key is used in the 'config: { theme: { ..' parameter."
			)
		);

		//Create new validation
		$valid = new \WP_CLI_ERROR();

		//Create For Empty List
		$list = array();

		//Check is empty
		if ( empty( $array ) ) {

			$valid->add_error( Package::_e( 'package', 'empty_val', array( "[key]" => "config: { options: .." ) ) );
		} else {

			//check is string
			if ( is_string( $array ) ) {
				$valid->add_error( Package::_e( 'package', 'is_string', array( "[key]" => "config: { options: .." ) ) );
			} else {

				//Check is not Assoc array
				if ( \WP_CLI_Util::is_assoc_array( $array ) === false ) {
					$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { options: .." ) ) );
				} else {

					//Check Prohibit Keys
					foreach ( $forbidden_values as $p_key => $p_val ) {
						foreach ( $p_val['list'] as $l ) {
							foreach ( $array as $key => $val ) {
								if ( trim( $key ) == $l ) {
									$valid->add_error( Package::_e( 'package', 'er_forbidden', array( "[key]" => "config: { options: { ['" . trim( $key ) . "']" ) ) . '' . $p_val['help'] );
									break;
								}
							}
						}
					}

					//Start Validation every parameter
					foreach ( $array as $option_name => $option_value ) {

						//Autoload Option
						$autoload = 'yes';

						//Check For Default Parameter (value|autoload)
						if ( is_array( $option_value ) ) {

							//Check is Assoc Array For Default Parameter
							if ( \WP_CLI_Util::is_assoc_array( $option_value ) === true ) {

								//check LowerCase
								$opt_val = array_change_key_case( $option_value, CASE_LOWER );

								//Check Default Parameter
								if ( isset( $opt_val['autoload'] ) and isset( $opt_val['value'] ) ) {

									//Check Exist Any parameter
									if ( count( $opt_val ) > 2 ) {
										$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { options: { " . $option_name . " .." ) ) );
										break;
									}

									//Check Validation autoload
									if ( is_string( $opt_val['autoload'] ) and $opt_val['autoload'] === "no" ) {
										$autoload = "no";
									}

									//Set Variable
									$option_value = ( isset( $opt_val['value'] ) ? $opt_val['value'] : '' );
								}
							}
						}

						//sanitize Key
						$key = trim( str_ireplace( " ", "", ( $option_name ) ) );

						//Push to array
						$list[] = array(
							'option_name'  => $key,
							'option_value' => $option_value,
							'autoload'     => $autoload
						);
					}

					//Validation Separate Parameter
					if ( ! $valid->is_cli_error() ) {

						//Push To sanitize return data
						$valid->add_success( $list );
					}
				}
			}
		}

		return ( $validate === true ? $valid->result() : $list );
	}

	/**
	 * Sanitize Package Rest-api parameter
	 *
	 * @param $var
	 * @param bool $validate
	 * @return string|boolean|array
	 * @since 1.0.0
	 */
	public function sanitize_rest_api( $var, $validate = false ) {

		//Create new validation
		$valid = new \WP_CLI_ERROR();

		//Check if Not Rest-Api in WordPress
		if ( ( is_bool( $var ) and $var === false ) || ( is_array( $var ) and ! empty( $var ) ) ) {

			//Check if is Network
			if ( is_array( $var ) ) {

				//Check is assoc array
				if ( \WP_CLI_Util::is_assoc_array( $var ) ) {

					//Lower case array key
					$var = array_change_key_case( $var, CASE_LOWER );

					//All accept Parameter
					$accept_params = array( 'prefix', 'disable' );

					//Require Key
					$require_key = array( 'prefix' );

					//Not empty if exist
					$not_empty = $accept_params;

					//Check Require Key
					$check_require_key = \WP_CLI_Util::check_require_array( $var, $require_key, true );
					if ( $check_require_key['status'] === false ) {
						foreach ( $check_require_key['data'] as $key ) {
							$valid->add_error( Package::_e( 'package', 'not_exist_key', array( "[require]" => $key, "[key]" => "config: { rest-api: { .." ) ) );
						}
					}

					//Check Anonymous Parameter
					foreach ( $var as $k => $val ) {
						if ( ! in_array( strtolower( $k ), $accept_params ) ) {
							$valid->add_error( Package::_e( 'package', 'er_unknown_param', array( "[key]" => 'config: { rest-api: { "' . $k . '" ..' ) ) );
						}
					}

					//Check Not Empty key
					foreach ( $not_empty as $k ) {
						if ( array_key_exists( $k, $var ) and empty( $var[ $k ] ) ) {
							$valid->add_error( Package::_e( 'package', 'empty_val', array( "[key]" => 'config: { rest-api: { "' . $k . '" ..' ) ) );
						}
					}

					// Validate Every item
					if ( ! $valid->is_cli_error() ) {

						//Check if string 'prefix'
						if ( is_string( $var['prefix'] ) ) {

							//Get user raw
							$prefix_raw = $var['prefix'];

							//Check Preg Prefix
							$var['prefix'] = preg_replace( $this->package_config['preg_prefix'], '', $var['prefix'] );

							//Check valid
							if ( \WP_CLI_Util::to_lower_string( $var['prefix'] ) != \WP_CLI_Util::to_lower_string( $prefix_raw ) ) {
								$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { rest-api: { prefix: { .." ) ) );
							}
						} else {

							$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { rest-api: { prefix: { .." ) ) );
						}

						//Validate disable route
						if ( ! $valid->is_cli_error() ) {

							//Check Exist disable List
							if ( array_key_exists( 'disable', $var ) ) {

								//Check If string
								if ( is_string( $var['disable'] ) ) {

									//Check if value === default
									if ( $var['disable'] != "default" ) {
										$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { rest-api: { disable: { .." ) ) );
									}

									//Check If Array
								} else if ( is_array( $var['disable'] ) and \WP_CLI_Util::is_assoc_array( $var['disable'] ) === false ) {

									//Check Validate every item
									$x = 0;
									foreach ( $var['disable'] as $route ) {

										//Check only accept string
										if ( is_string( $route ) ) {

											//Check if contain white space
											if ( preg_match( '/\s/', $route ) ) {

												$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { rest-api: { prefix: { [" . ( $x + 1 ) . "]" ) ) );
												break;
											} else {
												//Sanitize $route
												$var['disable'][ $x ] = "/" . ltrim( str_replace( "\\", "/", \WP_CLI_Util::to_lower_string( $route ) ), "/" );
											}
										} else {
											$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { rest-api: { prefix: { [" . ( $x + 1 ) . "]" ) ) );
											break;
										}

										$x ++;
									}

								} else {
									$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { rest-api: { disable: .." ) ) );
								}
							}
						}

						//Return sanitize output
						if ( ! $valid->is_cli_error() ) {
							$valid->add_success( $var );
						}

					} //Cli Error
				} else {
					$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { rest-api: .." ) ) );
				}
			} else {

				//Push false To Success return
				$valid->add_success( $var );
			}
		} else {
			$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { rest-api: .." ) ) );
		}

		return ( $validate === true ? $valid->result() : $var );
	}

	/**
	 * Sanitize Permalink Params
	 *
	 * @param $array
	 * @param bool $validate
	 * @return string|boolean|array
	 * @since 1.0.0
	 */
	public function sanitize_permalink( $array, $validate = false ) {

		//Accept Key
		$accept_key = array( 'common', 'category', 'tag' );

		//List of require Key
		$require_key = array( 'common' );

		//Create new validation
		$valid = new \WP_CLI_ERROR();

		//Check is empty
		if ( empty( $array ) ) {
			$valid->add_error( Package::_e( 'package', 'empty_val', array( "[key]" => "config: { permalink: .." ) ) );
		} else {

			//check is string
			if ( is_string( $array ) ) {
				$valid->add_error( Package::_e( 'package', 'is_string', array( "[key]" => "config: { permalink: .." ) ) );
			} else {

				//Check is not Assoc array
				if ( \WP_CLI_Util::is_assoc_array( $array ) === false ) {
					$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { permalink: .." ) ) );
				} else {

					//Convert to lowercase key
					$array = array_change_key_case( $array, CASE_LOWER );

					//Check is not Array or object value
					foreach ( $array as $key => $val ) {
						if ( ! is_string( $val ) ) {
							$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { permalink: { '" . $key . "'" ) ) );
							break;
						}
					}

					//Check require key
					$check_require_key = \WP_CLI_Util::check_require_array( $array, $require_key, false );
					if ( $check_require_key['status'] === false ) {
						foreach ( $check_require_key['data'] as $key ) {
							$valid->add_error( Package::_e( 'package', 'not_exist_key', array( "[require]" => $key, "[key]" => "config: { permalink: { .. " ) ) );
							break;
						}
					}

					//Check Anonymous Parameter
					foreach ( $array as $k => $val ) {
						if ( ! in_array( strtolower( $k ), $accept_key ) ) {
							$valid->add_error( Package::_e( 'package', 'er_unknown_param', array( "[key]" => 'config: { permalink: { "' . $k . '" ..' ) ) );
							break;
						}
					}

					//Check White Space
					foreach ( $array as $k => $val ) {
						if ( preg_match( '/\s/', $val ) ) {
							$valid->add_error( Package::_e( 'package', 'er_contain_space', array( "[key]" => 'config: { permalink: { "' . $k . '" ..' ) ) );
							break;
						}
					}

					//Validation Separate Parameter
					if ( ! $valid->is_cli_error() ) {

						//Sanitize
						$array = array_map( function ( $value ) {

							//Remove Quote and White Space
							$return = str_replace( array( " ", "'", "\"" ), "", \WP_CLI_Util::remove_quote( $value ) );

							//Sanitize Slash
							$return = str_replace( "\\", "/", trim( $return, "/" ) );

							//Remove Double Slash
							$return = \WP_CLI_Util::remove_double_slash( $return );

							//Add Slash
							$return = "/" . $return . "/";

							return $return;
						}, $array );

						//Push To sanitize return data
						$valid->add_success( $array );
					}
				}
			}
		}

		return ( $validate === true ? $valid->result() : $array );
	}

	/**
	 * Sanitize timezone parameter
	 *
	 * @param $var
	 * @param bool $validate
	 * @return string|boolean|array
	 * @since 1.0.0
	 */
	public function sanitize_timezone( $var, $validate = false ) {

		//Create new validation
		$valid = new \WP_CLI_ERROR();

		//Check is Empty
		if ( empty( $var ) ) {
			$valid->add_error( Package::_e( 'package', 'empty_val', array( "[key]" => "config: { timezone: .." ) ) );

			//Check is array
		} elseif ( is_array( $var ) ) {
			$valid->add_error( Package::_e( 'package', 'is_not_string', array( "[key]" => "config: { timezone: .." ) ) );

		} else {
			//Sanitize TimeZone
			$var = Timezone::sanitize_timezone( $var );

			//Check Validate TimeZone
			if ( Timezone::search_timezone( $var ) === false ) {
				$valid->add_error( Package::_e( 'package', 'wrong_timezone' ) );
			} else {
				$valid->add_success( $var );
			}
		}

		return ( $validate === true ? $valid->result() : $var );
	}

	/**
	 * Sanitize Package Theme parameter
	 *
	 * @param $var
	 * @param bool $validate
	 * @return string|boolean|array
	 * @since 1.0.0
	 */
	public function sanitize_theme( $var, $validate = false ) {

		//Create new validation
		$valid = new \WP_CLI_ERROR();

		//Check is Empty
		if ( empty( $var ) ) {
			$valid->add_error( Package::_e( 'package', 'empty_val', array( "[key]" => "config: { theme: .." ) ) );

			//Check is array
		} elseif ( is_array( $var ) ) {
			$valid->add_error( Package::_e( 'package', 'is_not_string', array( "[key]" => "config: { theme: .." ) ) );

		} else {

			//To Lowercase
			$var = \WP_CLI_Util::to_lower_string( $var );

			//Check theme slug
			$slug = \WP_CLI_Util::to_lower_string( preg_replace( Package::get_config( 'wordpress_api', 'slug' ), '', $var ) );
			if ( $slug != $var ) {
				$valid->add_error( Package::_e( 'package', 'er_valid', array( "[key]" => "config: { theme: { .." ) ) );
			} else {

				//Return Sanitize Data
				$valid->add_success( $var );
			}
		}

		return ( $validate === true ? $valid->result() : $var );
	}

	/**
	 * Create Default Value for init command
	 *
	 * @param $args
	 * @param bool $validate
	 * @return mixed
	 */
	public function init( $args, $validate = false ) {

		//Create Empty object
		$array = array();
		$error = array();

		//Get Default Users Value
		$config = $this->get_default_users_arg();

		//Create init params
		foreach ( array( 'site', 'admin' ) as $key ) {

			//Check Default Value
			switch ( $key ) {
				case "site":

					//Push To array
					$array['site'] = array(
						'title' => \WP_CLI_Helper::get_flag_value( $args, 'title', $this->package_config['default']['title'] ),
						'url'   => \WP_CLI_Helper::get_flag_value( $args, 'url', '' ),
					);

					//Sanitize
					$default = $this->sanitize_site( $array['site'], $validate );
					break;
				case "admin":

					//Push to array
					$array['admin'] = array(
						'admin_user'  => \WP_CLI_Helper::get_flag_value( $args, 'admin_user', $config['admin_user'] ),
						'admin_email' => \WP_CLI_Helper::get_flag_value( $args, 'admin_email', $config['admin_email'] ),
						'admin_pass'  => \WP_CLI_Helper::get_flag_value( $args, 'admin_pass', $config['admin_pass'] )
					);

					//Check Valid Users
					$default = $this->sanitize_admin( $array['admin'], $validate );
					break;
			}

			if ( isset( $default ) and ! empty( $default ) ) {
				$array[ $key ] = $default;

				//Check if validate
				if ( $validate and is_array( $default ) and $default['status'] === false ) {
					foreach ( $default['data'] as $text_error ) {
						$error[] = $text_error;
					}
				}
			}
		}

		return ( $validate === true ? $error : $array );
	}

	/**
	 * Check WordPress Network Sub domain in localhost
	 *
	 * @param $pkg_array
	 * @return bool
	 */
	public function check_network_subdomain( $pkg_array ) {

		//To Lowercase
		$pkg_array = \WP_CLI_Util::array_change_key_case_recursive( $pkg_array );

		//Check exist network
		if ( isset( $pkg_array['core']['network']['subdomain'] ) and $pkg_array['core']['network']['subdomain'] === true ) {

			//Check Site Url
			if ( isset( $pkg_array['config']['site']['url'] ) ) {

				//Get Domain name
				$parse = @parse_url( $pkg_array['config']['site']['url'] );
				if ( $parse != false ) {

					//Check domain is localhost
					if ( in_array( trim( $parse['host'] ), $this->package_config['localhost_domain'] ) ) {
						return false;
					}
				}
			}
		}

		return true;
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

		//Create Config File
		install::install_log( $step, $all_step, Package::_e( 'package', 'create_config' ) );
		\WP_CLI_Helper::pl_wait_start();
		Config_Arg::create_config( $pkg_array['mysql'] );
		\WP_CLI_Helper::pl_wait_end();
		install::add_detail_log( Package::_e( 'package', 'salt_generate' ) );
		install::add_detail_log( Package::_e( 'package', 'added_db_const' ) );

		//Check Extra Constant
		if ( isset( $pkg_array['config']['constant'] ) and count( $pkg_array['config']['constant'] ) > 0 ) {
			Config_Arg::add_wordpress_package_anchor();
			Config_Arg::update_constant( $pkg_array['config']['constant'], $current_const_list = array(), $options = array( 'remove' => false ) );
		}

		//Cookie Constant
		if ( isset( $pkg_array['config']['cookie'] ) and ! empty( $pkg_array['config']['cookie'] ) and isset( $pkg_array['config']['site']['url'] ) ) {
			Cookie::set_cookie_prefix( $pkg_array['config']['cookie'], $pkg_array['config']['site']['url'] );
			install::add_detail_log( Package::_e( 'package', 'change_cookie_prefix' ) );
		}

		//Create require Dir in wp-content
		Dir::create_require_folder();

		//added step
		$step ++;

		return array( 'status' => true, 'step' => $step );
	}

}