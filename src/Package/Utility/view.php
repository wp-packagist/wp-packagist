<?php

namespace WP_CLI_PACKAGIST\Package\Utility;

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
			\WP_CLI_Helper::pl_wait_end();
		}
		\WP_CLI_Helper::br();

		//Show Every item
		foreach ( $json_package as $name => $value ) {

			//Show Title
			\WP_CLI_Helper::line( \WP_CLI_Helper::color( "# " . ucfirst( $name ), "Y" ) );

			//Show Content
			switch ( $name ) {
				case 'name':
				case 'description':
					\WP_CLI_Helper::line( $value );
					break;

				case 'keywords':
					foreach ( $value as $keyword ) {
						\WP_CLI_Helper::line( "-" . $keyword );
					}
					break;

				case 'core':
					foreach ( $value as $k => $v ) {
						if ( $k == "locale" || $k == "version" ) {
							\WP_CLI_Helper::line( ucfirst( $k ) . ": " . $v );
						}
						if ( $k == "network" ) {
							if ( $v === false ) {
								\WP_CLI_Helper::line( ucfirst( $k ) . ": no" );
							} else {
								\WP_CLI_Helper::line( ucfirst( $k ) . ": yes" );
								\WP_CLI_Helper::line( "- use subdomain: " . ( $v['subdomain'] === false ? 'no' : 'yes' ) );
								if ( isset( $v['sites'] ) and ! empty( $v['sites'] ) ) {
									\WP_CLI_Helper::line( "- sites: " );
									$site = array();
									foreach ( $v['sites'] as $s ) {
										$site[] = array(
											'slug'   => $s['slug'],
											'public' => ( $s['public'] === true ? 'yes' : 'no' ),
											'email'  => ( isset( $s['email'] ) ? $s['email'] : '-' ),
											'title'  => ( isset( $s['title'] ) ? $s['title'] : '-' )
										);
									}
									\WP_CLI_Helper::create_table( $site );
								}
							}
						}
					}
					break;

				case 'config':
					# 'URL'
					if ( isset( $value['url'] ) ) {
						//Private Site Url
						$v = $value['url'];
						if ( $private ) {
							$v = "[ Your site url ]";
						}

						\WP_CLI_Helper::line( ucfirst( 'URL' ) . ": " . $v );
					}

					# 'Title'
					if ( isset( $value['title'] ) ) {
						\WP_CLI_Helper::line( ucfirst( 'title' ) . ": " . $value['title'] );
					}

					# 'rest api'
					if ( isset( $value['rest-api'] ) and is_bool( $value['rest-api'] ) and $value['rest-api'] === false ) {
						\WP_CLI_Helper::line( ucfirst( 'Rest-Api' ) . ": no" );
					}

					# 'Cookie'
					if ( isset( $value['cookie'] ) ) {
						\WP_CLI_Helper::line( ucfirst( 'cookie prefix' ) . ": " . $value['cookie'] );
					}

					# 'TimeZone'
					if ( isset( $value['timezone'] ) and ! empty( $value['timezone'] ) ) {
						\WP_CLI_Helper::line( ucfirst( 'timezone' ) . ": " . $value['timezone'] );
					}

					# 'theme'
					if ( isset( $value['theme'] ) and ! empty( $value['theme'] ) ) {
						\WP_CLI_Helper::line( ucfirst( 'theme' ) . ": " . $value['theme'] );
					}
					\WP_CLI_Helper::br();

					# 'Admin'
					if ( isset( $value['admin'] ) ) {
						\WP_CLI_Helper::line( \WP_CLI_Helper::color( "- " . ucfirst( 'admin:' ), "C" ) );
						foreach ( $value['admin'] as $k => $v ) {

							if ( $k != "meta" ) {
								//Check Private
								if ( $private === true ) {
									$v = "[ " . $k . " config ]";
								}
								\WP_CLI_Helper::line( ucfirst( $k ) . ": " . $v );
							}
						}

						//Check Meta
						if ( isset( $value['admin']['meta'] ) ) {
							\WP_CLI_Helper::line( ucfirst( "admin meta" ) . ": " );
							$list = array();
							foreach ( $value['admin']['meta'] as $meta_name => $meta_value ) {
								$list[] = array(
									'name'  => $meta_name,
									'value' => ( is_array( $meta_value ) ? \WP_CLI_Util::json_encode( $meta_value ) : $meta_value )
								);
							}
							\WP_CLI_Helper::create_table( $list );
						}

						\WP_CLI_Helper::br();
					}

					# 'users'
					if ( isset( $value['users'] ) ) {
						\WP_CLI_Helper::line( \WP_CLI_Helper::color( "- " . ucfirst( 'users:' ), "C" ) );
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
						\WP_CLI_Helper::create_table( $list );
						\WP_CLI_Helper::br();
					}

					# 'constant'
					if ( isset( $value['constant'] ) ) {
						\WP_CLI_Helper::line( \WP_CLI_Helper::color( "- " . ucfirst( 'constant:' ), "C" ) );
						$list = array();
						foreach ( $value['constant'] as $k => $v ) {
							$list[] = array(
								'name'  => $k,
								'value' => $v
							);
						}
						\WP_CLI_Helper::create_table( $list );
						\WP_CLI_Helper::br();
					}

					# 'Options'
					if ( isset( $value['options'] ) ) {
						\WP_CLI_Helper::line( \WP_CLI_Helper::color( "- " . ucfirst( 'options:' ), "C" ) );
						$list = array();
						foreach ( $value['options'] as $meta_name ) {
							$list[] = array(
								'name'     => $meta_name['option_name'],
								'value'    => ( is_array( $meta_name['option_value'] ) ? \WP_CLI_Util::json_encode( $meta_name['option_value'] ) : $meta_name['option_value'] ),
								'autoload' => ucfirst( $meta_name['autoload'] )
							);
						}
						\WP_CLI_Helper::create_table( $list );
						\WP_CLI_Helper::br();
					}

					# 'rest api'
					if ( isset( $value['rest-api'] ) and is_array( $value['rest-api'] ) ) {
						\WP_CLI_Helper::line( \WP_CLI_Helper::color( "- " . ucfirst( 'REST-API:' ), "C" ) );

						//Prefix
						\WP_CLI_Helper::line( "- Prefix URL: " . $value['rest-api']['prefix'] );

						//List disable Route
						if ( isset( $value['rest-api']['disable'] ) ) {
							\WP_CLI_Helper::line( "- Disable route: " . ( is_string( $value['rest-api']['disable'] ) ? $value['rest-api']['disable'] : '' ) );
							if ( is_array( $value['rest-api']['disable'] ) ) {
								$list = array();
								foreach ( $value['rest-api']['disable'] as $k ) {
									$list[] = array(
										'route' => $k
									);
								}
								\WP_CLI_Helper::create_table( $list );
							}

							\WP_CLI_Helper::br();
						}
					}

					# 'permalink'
					if ( isset( $value['permalink'] ) and is_array( $value['permalink'] ) ) {
						\WP_CLI_Helper::line( \WP_CLI_Helper::color( "- " . ucfirst( 'permalink:' ), "C" ) );

						//List
						if ( isset( $value['permalink'] ) ) {
							$list = array();
							foreach ( $value['permalink'] as $k => $v ) {
								$list[] = array(
									'name'      => $k,
									'structure' => $v
								);
							}
							\WP_CLI_Helper::create_table( $list );
						}
					}
					break;

				case 'dir':
					foreach ( $value as $k => $v ) {
						\WP_CLI_Helper::line( $k . ": " . $v );
					}
					break;

				case 'mysql':
					$forbidden = array( 'DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME' );

					foreach ( $value as $k => $v ) {
						//Check Private Parameter
						if ( $private === true and in_array( $k, $forbidden ) ) {
							$v = '-';
						}

						\WP_CLI_Helper::line( $k . ": " . $v );
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
					\WP_CLI_Helper::create_table( $list );
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
					\WP_CLI_Helper::create_table( $list );
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
					\WP_CLI_Helper::create_table( $list );
					break;
			}

			//Add br
			\WP_CLI_Helper::br();
		}

	}


}