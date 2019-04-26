<?php

namespace WP_CLI_PACKAGIST\Package\Utility;

use WP_CLI_PACKAGIST\Utility\CLI;
use WP_CLI_PACKAGIST\Utility\PHP;
use WP_CLI_PACKAGIST\Package\Package;

/**
 * View Package Detail
 */
class view extends Package {
	/**
	 * Show current Package Detail
	 *
	 * @param $json_package | WordPress Package array data after validation
	 * @param bool $private
	 */
	public function view( $json_package, $private = false ) {

		//Remove please wait
		if ( defined( 'WP_CLI_PLEASE_WAIT_LOG' ) ) {
			CLI::pl_wait_end();
		}

		//Show Every item
		foreach ( $json_package as $name => $value ) {

			//Show Title
			CLI::line( CLI::color( "# " . ucfirst( $name ), "Y" ) );

			//Show Content
			switch ( $name ) {
				case 'name':
				case 'description':
					CLI::line( $value );
					break;

				case 'keywords':
					foreach ( $value as $keyword ) {
						CLI::line( "-" . $keyword );
					}
					break;

				case 'core':
					foreach ( $value as $k => $v ) {
						if ( $k == "locale" || $k == "version" ) {
							CLI::line( ucfirst( $k ) . ": " . $v );
						}
						if ( $k == "network" ) {
							if ( $v === false ) {
								CLI::line( ucfirst( $k ) . ": no" );
							} else {
								CLI::line( ucfirst( $k ) . ": yes" );
								CLI::line( "- use subdomain: " . ( $v['subdomain'] === false ? 'no' : 'yes' ) );
								if ( isset( $v['sites'] ) and ! empty( $v['sites'] ) ) {
									CLI::line( "- sites: " );
									$site = array();
									foreach ( $v['sites'] as $s ) {
										$site[] = array(
											'slug'   => $s['slug'],
											'public' => ( $s['public'] === true ? 'yes' : 'no' ),
											'email'  => ( isset( $s['email'] ) ? $s['email'] : '-' ),
											'title'  => ( isset( $s['title'] ) ? $s['title'] : '-' )
										);
									}
									CLI::create_table( $site );
								}
							}
						}
					}
					break;

				case 'config':
					# 'site'
					if ( isset( $value['site'] ) ) {
						foreach ( $value['site'] as $k => $v ) {
							//Private Site Url
							if ( $private and $k == "url" ) {
								$v = "[ Your site url ]";
							}

							CLI::line( ucfirst( $k ) . ": " . $v );
						}
					}

					# 'rest api'
					if ( isset( $value['rest-api'] ) and is_bool( $value['rest-api'] ) and $value['rest-api'] === false ) {
						CLI::line( ucfirst( 'Rest-Api' ) . ": no" );
					}

					# 'Cookie'
					if ( isset( $value['cookie'] ) ) {
						CLI::line( ucfirst( 'cookie prefix' ) . ": " . $value['cookie'] );
					}

					# 'TimeZone'
					if ( isset( $value['timezone'] ) and ! empty( $value['timezone'] ) ) {
						CLI::line( ucfirst( 'timezone' ) . ": " . $value['timezone'] );
					}

					# 'theme'
					if ( isset( $value['theme'] ) and ! empty( $value['theme'] ) ) {
						CLI::line( ucfirst( 'theme' ) . ": " . $value['theme'] );
					}
					CLI::br();

					# 'Admin'
					if ( isset( $value['admin'] ) ) {
						CLI::line( CLI::color( "- " . ucfirst( 'admin:' ), "C" ) );
						foreach ( $value['admin'] as $k => $v ) {

							if ( $k != "meta" ) {
								//Check Private
								if ( $private === true ) {
									$v = "[ " . $k . " config ]";
								}
								CLI::line( ucfirst( $k ) . ": " . $v );
							}
						}

						//Check Meta
						if ( isset( $value['admin']['meta'] ) ) {
							CLI::line( ucfirst( "admin meta" ) . ": " );
							$list = array();
							foreach ( $value['admin']['meta'] as $meta_name => $meta_value ) {
								$list[] = array(
									'name'  => $meta_name,
									'value' => ( is_array( $meta_value ) ? PHP::json_encode( $meta_value ) : $meta_value )
								);
							}
							CLI::create_table( $list );
						}

						CLI::br();
					}

					# 'users'
					if ( isset( $value['users'] ) ) {
						CLI::line( CLI::color( "- " . ucfirst( 'users:' ), "C" ) );
						$list = array();
						foreach ( $value['users'] as $s ) {
							$list[] = array(
								'user_login'   => $s['user_login'],
								'user_email'   => ( $private ? '****' : $s['user_email'] ),
								'role'         => ( isset( $s['role'] ) ? $s['role'] : '-' ),
								'display_name' => ( isset( $s['display_name'] ) ? $s['display_name'] : '-' ),
								'meta'         => ( isset( $s['meta'] ) ? $s['meta'] : '-' ),
							);
						}
						CLI::create_table( $list );
						CLI::br();
					}

					# 'constant'
					if ( isset( $value['constant'] ) ) {
						CLI::line( CLI::color( "- " . ucfirst( 'constant:' ), "C" ) );
						$list = array();
						foreach ( $value['constant'] as $k => $v ) {
							$list[] = array(
								'name'  => $k,
								'value' => $v
							);
						}
						CLI::create_table( $list );
						CLI::br();
					}

					# 'Options'
					if ( isset( $value['options'] ) ) {
						CLI::line( CLI::color( "- " . ucfirst( 'options:' ), "C" ) );
						$list = array();
						foreach ( $value['options'] as $meta_name ) {
							$list[] = array(
								'name'     => $meta_name['option_name'],
								'value'    => ( is_array( $meta_name['option_value'] ) ? PHP::json_encode( $meta_name['option_value'] ) : $meta_name['option_value'] ),
								'autoload' => ucfirst( $meta_name['autoload'] )
							);
						}
						CLI::create_table( $list );
						CLI::br();
					}

					# 'rest api'
					if ( isset( $value['rest-api'] ) and is_array( $value['rest-api'] ) ) {
						CLI::line( CLI::color( "- " . ucfirst( 'Rest-Api:' ), "C" ) );

						//Prefix
						CLI::line( "- Prefix url: " . $value['rest-api']['prefix'] );

						//List disable Route
						if ( isset( $value['rest-api']['disable'] ) ) {
							CLI::line( "- Disable route: " . ( is_string( $value['rest-api']['disable'] ) ? $value['rest-api']['disable'] : '' ) );
							if ( is_array( $value['rest-api']['disable'] ) ) {
								$list = array();
								foreach ( $value['rest-api']['disable'] as $k ) {
									$list[] = array(
										'route' => $k
									);
								}
								CLI::create_table( $list );
							}

							CLI::br();
						}
					}

					# 'permalink'
					if ( isset( $value['permalink'] ) and is_array( $value['permalink'] ) ) {
						CLI::line( CLI::color( "- " . ucfirst( 'permalink:' ), "C" ) );

						//List
						if ( isset( $value['permalink'] ) ) {
							$list = array();
							foreach ( $value['permalink'] as $k => $v ) {
								$list[] = array(
									'name'      => $k,
									'structure' => $v
								);
							}
							CLI::create_table( $list );
						}
					}
					break;

				case 'dir':
					foreach ( $value as $k => $v ) {
						CLI::line( $k . ": " . $v );
					}
					break;

				case 'mysql':
					$forbidden = array( 'DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME' );

					foreach ( $value as $k => $v ) {
						//Check Private Parameter
						if ( $private === true and in_array( $k, $forbidden ) ) {
							$v = '-';
						}

						CLI::line( $k . ": " . $v );
					}
					break;

				case 'plugins':
					$list = array();
					foreach ( $value as $plugin ) {
						$list[] = array(
							'plugin'   => $plugin['slug'],
							'version'  => ( isset( $plugin['version'] ) ? $plugin['version'] : '-' ),
							'source'   => ( isset( $plugin['version'] ) ? 'WordPress' : $plugin['url'] ),
							'activate' => ( $plugin['activate'] === true ? 'Yes' : 'No' )
						);
					}
					CLI::create_table( $list );
					break;

				case 'themes':
					$list = array();
					foreach ( $value as $k => $v ) {
						$list[] = array(
							'theme'   => $k,
							'version' => ( filter_var( $v, FILTER_VALIDATE_URL ) === false ? $v : '-' ),
							'source'  => ( filter_var( $v, FILTER_VALIDATE_URL ) === false ? 'WordPress' : $v ),
						);
					}
					CLI::create_table( $list );
					break;

				case 'commands':
					$list = array();
					foreach ( $value as $k ) {
						$list[] = array(
							'command' => ( $k['where'] == "wp-cli" ? "wp " . $k['command'] : $k['command'] ),
							'type'    => ( $k['where'] == "wp-cli" ? "WP-CLI" : "Global" ),
							'source'  => ( $k['where'] == "wp-cli" ? "-" : $k['where'] ),
						);
					}
					CLI::create_table( $list );
					break;
			}

			//Add br
			CLI::br();
		}

	}


}