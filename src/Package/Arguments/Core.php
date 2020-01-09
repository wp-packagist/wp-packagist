<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\install;
use WP_CLI_PACKAGIST\Package\Utility\temp;

class Core {
	/**
	 * List Of WordPress Multi-site Tables
	 *
	 * @var array
	 */
	public static $multi_site_tables = array(
		'blogs',
		'blog_versions',
		'blogmeta',
		'registration_log',
		'signups',
		'site',
		'sitemeta',
		'sitecategories',
		'domain_mapping',
		'domain_maping_logins',
	);

	/**
	 * install WordPress
	 *
	 * @param $pkg_array
	 * @param bool $is_network
	 */
	public static function install_wordpress( $pkg_array, $is_network = false ) {

		// Check sub-domain for network
		$is_sub_domains = '';
		if ( $is_network === true and isset( $pkg_array['core']['network']['subdomain'] ) and $pkg_array['core']['network']['subdomain'] === true ) {
			$is_sub_domains = ' --subdomains';
		}

		// Check Url Path for Network
		$network_url_path = '';
		if ( $is_network === true and isset( $pkg_array['config']['url'] ) and \WP_CLI_Util::get_path_url( $pkg_array['config']['url'] ) != "/" ) {
			$network_url_path = ' --base=' . \WP_CLI_Util::get_path_url( $pkg_array['config']['url'] );
		}

		// Prepare Command
		$cmd = "core " . ( $is_network === false ? 'install' : 'multisite-install' ) . " --url=%s --title=%s --admin_user=%s --admin_password=%s --admin_email=%s --skip-email{$is_sub_domains}{$network_url_path}";

		// Run WP-CLI
		\WP_CLI_Helper::run_command( \WP_CLI\Utils\esc_cmd( $cmd, $pkg_array['config']['url'], $pkg_array['config']['title'], $pkg_array['config']['admin']['admin_user'], $pkg_array['config']['admin']['admin_pass'], $pkg_array['config']['admin']['admin_email'] ) );
	}

	/**
	 * install network Sites
	 *
	 * @param array $sites
	 * @param string $when | run in installing Package or update package
	 */
	public static function install_network_sites( $sites = array(), $when = 'install' ) {
		foreach ( $sites as $site ) {
			self::add_new_blog( $sites );
			install::add_detail_log( Package::_e( 'package', 'created_site', array( "[slug]" => $site['slug'] ) ), ( $when == "update" ? 5 : 1 ) );
		}
	}

	/**
	 * Add new Blog in WordPress Network
	 *
	 * @param array $site
	 */
	public static function add_new_blog( $site = array() ) {

		//Prepare command
		$cmd = "site create --slug=" . $site['slug'];

		//Check Title
		if ( isset( $site['title'] ) and ! empty( $site['title'] ) ) {
			$cmd .= ' --title="' . $site['title'] . '"';
		}

		//Check Email
		if ( isset( $site['email'] ) and ! empty( $site['email'] ) ) {
			$cmd .= " --email=" . $site['email'] . "";
		}

		//Check Private
		if ( isset( $site['public'] ) and $site['public'] === false ) {
			$cmd .= " --private";
		}

		//Run
		\WP_CLI_Helper::run_command( $cmd );
	}

	/**
	 * Check is Multi Site
	 */
	public static function is_multisite() {

		//Check Network
		$network = false;
		if ( defined( 'MULTISITE' ) ) {
			if ( MULTISITE ) {
				$network = true;
			}
		}

		//Check sub domain
		$sub_domain = false;
		if ( defined( 'SUBDOMAIN_INSTALL' ) ) {
			if ( SUBDOMAIN_INSTALL ) {
				$sub_domain = true;
			}
		}

		//Check Rewrite File
		$file = '.htaccess';
		if ( function_exists( 'iis7_supports_permalinks' ) ) {
			if ( iis7_supports_permalinks() ) {
				$file = 'web.config';
			}
		}

		return array( 'network' => $network, 'subdomain' => $sub_domain, 'mod_rewrite_file' => $file );
	}

	/**
	 * Get Site Url
	 *
	 * @run after_wp_load
	 */
	public static function get_site_url() {
		return rtrim( \WP_CLI_Util::backslash_to_slash( get_option( 'siteurl' ) ), "/" );
	}

	/**
	 * Check exist installed WordPress
	 *
	 * @return bool
	 */
	public static function is_installed_wordpress() {
		if ( function_exists( 'is_blog_installed' ) and file_exists( Config::get_wp_config_path() ) ) {
			if ( is_blog_installed() ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Convert Domain Type in WordPress Multi-site (sub-domain to path or reverse)
	 *
	 * @param $table
	 * @param $ID
	 */
	public static function convert_path_to_subdomain( $table, $ID ) {
		global $wpdb;

		$table_name = $wpdb->prefix . $table;
		$unique     = array(
			'blogs'   => 'blog_id',
			'signups' => 'signup_id',
		);
		$blog       = $wpdb->get_row( "SELECT * FROM `" . $table_name . "` WHERE `{$unique[$table]}` = {$ID}", ARRAY_A );

		$is_current_subdomain = self::is_active_subdomain();
		$domain               = self::get_base_domain_network();
		$base_path            = self::get_base_path_network();

		if ( $is_current_subdomain === true ) {

			// Remove Sub-domain from Path
			// if subdomains = true all domain path => /
			$exp  = explode( ".", $blog['domain'] );
			$path = ( count( $exp ) > 2 ? '/' . $exp[0] . '/' : '/' );

			$wpdb->update( $table_name, array( 'domain' => $domain, 'path' => $path ), array( $unique[ $table ] => $ID ) );
		} else {

			// Add Sub-domain to domain
			// if sub-domain = false all domain is base_domain
			$new_domain_with_sub = $domain;
			$sub_name            = str_replace( "/", "/", str_replace( $base_path, "", $blog['path'] ) );
			if ( ! empty( $sub_name ) ) {
				$new_domain_with_sub = $sub_name . "." . $domain;
			}

			$wpdb->update( $table_name, array( 'domain' => $new_domain_with_sub, 'path' => "/" ), array( $unique[ $table ] => $ID ) );
		}
	}

	/**
	 * Change Sub-domain Type in WordPress Multi-Site
	 *
	 * @param bool $sub_domain
	 * @param bool $log
	 */
	public static function change_network_sub_domain( $sub_domain = false, $log = false ) {
		global $wpdb;

		$is_current_subdomain = self::is_active_subdomain();
		$domain               = self::get_base_domain_network();
		$base_path            = self::get_base_path_network();

		// Check if has Changed
		if ( $sub_domain != $is_current_subdomain ) {

			// Change *_blogs table
			$blogs = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}blogs`", ARRAY_A );
			foreach ( $blogs as $blog ) {
				$blog_id    = $blog['blog_id'];
				$before_url = rtrim( get_site_url( $blog_id ), "/" );

				// Convert Domain
				self::convert_path_to_subdomain( 'blogs', $blog_id );

				if ( $is_current_subdomain === true ) {

					// Get Sub-Domain
					$exp  = explode( ".", $blog['domain'] );
					$path = ( count( $exp ) > 2 ? '/' . $exp[0] . '/' : '/' );

					// Get New Url
					$new_url = rtrim( rtrim( network_site_url(), "/" ) . ( $path !== "/" ? $path : "" ), "/" );

					// Prepare log
					$before_log_url = $blog['domain'];
					$after_log_url  = $domain . $path;

				} else {

					// Get new Domain
					$new_domain_with_sub = $domain;
					$sub_name            = str_replace( "/", "/", str_replace( $base_path, "", $blog['path'] ) );
					if ( ! empty( $sub_name ) ) {
						$new_domain_with_sub = $sub_name . "." . $domain;
					}

					// Get New Url
					$protocol = parse_url( network_site_url(), PHP_URL_SCHEME );
					$new_url  = rtrim( $protocol . "://" . $new_domain_with_sub, "/" );

					// Prepare log
					$before_log_url = $blog['domain'] . $blog['path'];
					$after_log_url  = $new_domain_with_sub;
				}

				if ( $before_url != $new_url ) {

					// Search-replace All Table for this domain
					\WP_CLI_Helper::search_replace_db( $before_url, $new_url );

					// Add log
					if ( $log ) {
						install::add_detail_log( "Changed '{$before_log_url}' url to '{$after_log_url}'." . \WP_CLI_Helper::color( "[blog_id: {$blog_id}]", "B" ) . "", 5 );
					}
				}

			}

			// Change *_signups table
			$signups = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}signups` WHERE domain != ''", ARRAY_A );
			foreach ( $signups as $signup ) {
				self::convert_path_to_subdomain( 'signups', $signup['signup_id'] );
			}

			// Remove User meta 'source_domain'
			$wpdb->query("DELETE FROM `{$wpdb->usermeta}` WHERE `meta_key` = 'source_domain'");
		}
	}

	/**
	 * Check is Active Network Sub-domain
	 *
	 * @return mixed
	 */
	public static function is_active_subdomain() {
		if ( function_exists( 'is_subdomain_install' ) ) {
			return is_subdomain_install();
		} else {
			$status = self::is_multisite();
			return $status['subdomain'];
		}
	}

	/**
	 * Get Base Domain in WordPress Multi-Site
	 *
	 * @see https://developer.wordpress.org/reference/functions/get_network/
	 */
	public static function get_base_domain_network() {
		if ( defined( 'DOMAIN_CURRENT_SITE' ) ) {
			return DOMAIN_CURRENT_SITE;
		} else {
			$network_domain = network_site_url(); // also use get_network()->path
			return parse_url( $network_domain, PHP_URL_HOST );
		}
	}

	/**
	 * Get Base Path Domain in WordPress Multi-Site
	 */
	public static function get_base_path_network() {
		global $wpdb;
		if ( defined( 'PATH_CURRENT_SITE' ) ) {
			return PATH_CURRENT_SITE;
		} else {
			return $wpdb->get_var( "SELECT `path` FROM `{$wpdb->prefix}blogs` WHERE `blog_id` = 1" );
		}
	}

	/**
	 * Check Exist Blog by Slug
	 *
	 * @param $slug
	 * @return array|bool
	 * @see https://developer.wordpress.org/reference/functions/get_id_from_blogname/
	 * @see https://developer.wordpress.org/reference/functions/get_blog_details/
	 */
	public static function exist_blog( $slug ) {
		$blog_id = get_id_from_blogname( $slug );
		if ( is_int( $blog_id ) and $blog_id > 0 ) {
			return array_merge( array( 'slug' => $slug ), self::get_blog_by_id( $blog_id ) );
		}

		return false;
	}

	/**
	 * Get Blog Detail by ID
	 *
	 * @param $blog_id
	 * @return array
	 * @see https://developer.wordpress.org/reference/functions/get_site/
	 */
	public static function get_blog_by_id( $blog_id ) {
		return get_object_vars( get_site( $blog_id ) );
	}

	/**
	 * Update Network parameter in WordPress Package
	 *
	 * @param $pkg
	 */
	public static function update_network( $pkg ) {

		//Get Local Temp
		$localTemp = temp::get_temp( \WP_CLI_Util::getcwd() );
		$tmp       = ( $localTemp === false ? array() : $localTemp );

		// Check site Status in Tmp
		$before_status_network = is_multisite();
		if ( isset( $tmp['core']['network'] ) ) {
			$before_status_network = ( $tmp['core']['network'] === false ? false : true );
			$tmp_network           = ( $tmp['core']['network'] === false ? false : $tmp['core']['network'] );
		}

		// Check Site Status in Pkg
		$now_status_network = false;
		if ( isset( $pkg['core']['network'] ) ) {
			$now_status_network = ( $pkg['core']['network'] === false ? false : true );
			$pkg_network        = ( $pkg['core']['network'] === false ? false : $pkg['core']['network'] );
		}

		// Get Current Site title
		$site_title = get_bloginfo( 'name' );
		if ( isset( $pkg_array ) and isset( $pkg_array['config']['title'] ) and trim( $pkg_array['config']['title'] ) != "" ) {
			$site_title = $pkg_array['config']['title'];
		}

		// Convert Single to Multi-Site
		if ( $before_status_network === false and $now_status_network === true ) {

			//log
			install::add_detail_log( Package::_e( 'package', 'convert_single_multi' ) );
			\WP_CLI_Helper::pl_wait_start();

			// Check sub-domain for network
			$is_sub_domains = '';
			if ( isset( $pkg_network ) and isset( $pkg_network['subdomain'] ) and $pkg_network['subdomain'] === true ) {
				$is_sub_domains = ' --subdomains';
			}

			// Check Path for network
			$network_url_path = '';
			if ( isset( $pkg['config']['url'] ) and \WP_CLI_Util::get_path_url( $pkg['config']['url'] ) != "/" ) {
				$network_url_path = ' --base=' . \WP_CLI_Util::get_path_url( $pkg['config']['url'] );
			}

			// Run Multi-site Convert
			$cmd = "core multisite-convert --title=%s{$is_sub_domains}{$network_url_path}";
			\WP_CLI_Helper::run_command( \WP_CLI\Utils\esc_cmd( $cmd, $site_title ) );
			\WP_CLI_Helper::pl_wait_start();

			// Create Htaccess multi-site
			$mod_network = Core::is_multisite();
			Permalink::run_permalink_file();
			install::add_detail_log( Package::_e( 'package', 'created_file', array( "[file]" => $mod_network['mod_rewrite_file'] ) ), 5 );

			// Create Multi-Site blog List
			if ( isset( $pkg_network ) and isset( $pkg_network['sites'] ) and count( $pkg_network['sites'] ) > 0 ) {
				Core::install_network_sites( $pkg_network['sites'], 'update' );
			}

			// Run WordPress Security ( Use For Htaccess )
			Security::remove_security_file();
			Security::wordpress_package_security_plugin( $pkg );
		}

		// Change Multi-Site Params
		if ( $before_status_network === true and $now_status_network === true and isset( $tmp_network ) and isset( $pkg_network ) ) {

			// Check Change sub-domain Type
			$tmp_subdomain = ( isset( $tmp_network['subdomain'] ) ? $tmp_network['subdomain'] : self::is_active_subdomain() );
			$pkg_subdomain = ( isset( $pkg_network['subdomain'] ) ? $pkg_network['subdomain'] : false );
			if ( $tmp_subdomain != $pkg_subdomain ) {

				// Add log
				install::add_detail_log( Package::_e( 'package', 'change_subdomain_type', array( "[work]" => ( $pkg_subdomain === true ? "Enabled" : "Disabled" ) ) ) );

				// Change SUBDOMAIN_INSTALL constant
				$wp_config = Config::get_config_transformer();
				$wp_config->update( 'constant', 'SUBDOMAIN_INSTALL', ( $pkg_subdomain === true ? 'true' : 'false' ), array( 'raw' => true ) );

				// Create Again Htaccess
				Permalink::run_permalink_file();

				// Change Url of Multi-site Blogs
				self::change_network_sub_domain( $pkg_subdomain, true );

				// Run WordPress Security ( Use For Htaccess )
				Security::remove_security_file();
				Security::wordpress_package_security_plugin( $pkg );
			}

			// Check Network Site Params
			$tmp_blogs = isset( $tmp_network['sites'] ) ? $tmp_network['sites'] : array();
			$pkg_blogs = isset( $pkg_network['sites'] ) ? $pkg_network['sites'] : array();

			// Remove WordPress Multi-site Blog
			if ( count( $tmp_blogs ) > count( $pkg_blogs ) ) {
				foreach ( $tmp_blogs as $tmp_blog ) {
					$_exist = false;

					// Check Exist Blog in Pkg
					foreach ( $pkg_blogs as $pkg_blog ) {
						if ( \WP_CLI_Util::to_lower_string( $pkg_blog['slug'] ) == \WP_CLI_Util::to_lower_string( $tmp_blog['slug'] ) ) {
							$_exist = true;
						}
					}

					if ( ! $_exist ) {

						// Check Blog Exist in WordPress Database then Deleted
						$_exist_DB = self::exist_blog( $tmp_blog['slug'] );
						if ( $_exist_DB != false ) {

							wpmu_delete_blog( $_exist_DB['blog_id'], true );
							install::add_detail_log( Package::_e( 'package', 'manage_item_red', array( "[work]" => "Removed", "[key]" => $tmp_blog['slug'], "[type]" => "site" ) ) );
						}
					}
				}
			}

			// Add new WordPress Multi-site Blog
			if ( count( $pkg_blogs ) > count( $tmp_blogs ) ) {
				foreach ( $pkg_blogs as $pkg_blog ) {
					$_exist = false;

					// Check Exist Blog in Pkg
					foreach ( $tmp_blogs as $tmp_blog ) {
						if ( \WP_CLI_Util::to_lower_string( $pkg_blog['slug'] ) == \WP_CLI_Util::to_lower_string( $tmp_blog['slug'] ) ) {
							$_exist = true;
						}
					}

					if ( ! $_exist ) {

						$_exist_DB = self::exist_blog( $pkg_blog['slug'] );
						if ( $_exist_DB === false ) {
							self::add_new_blog( $pkg_blog );
							install::add_detail_log( Package::_e( 'package', 'created_site', array( "[slug]" => $pkg_blog['slug'] ) ) );
						}

					}
				}
			}

			// Add OR Edit Blog item
			$x_pkg = 0;
			foreach ( $pkg_blogs as $pkg_blog ) {
				$_exist = $tmp_key = $pkg_key = false;

				// Check Exist Blog in Tmp
				$x_tmp = 0;
				foreach ( $tmp_blogs as $tmp_blog ) {
					if ( \WP_CLI_Util::to_lower_string( $pkg_blog['slug'] ) == \WP_CLI_Util::to_lower_string( $tmp_blog['slug'] ) ) {
						$_exist  = true;
						$tmp_key = $x_tmp;
						$pkg_key = $x_pkg;
					}
					$x_tmp ++;
				}

				if ( $_exist === true ) {

					// Search in WordPress DB
					$_exist_DB = self::exist_blog( $pkg_blog['slug'] );
					if ( $_exist_DB != false ) {
						$blog_id          = $_exist_DB['blog_id'];
						$current_pkg_blog = $pkg_blogs[ $pkg_key ];
						$current_tmp_blog = $tmp_blogs[ $tmp_key ];











					}
				}


				$x_pkg ++;
			}


		}

	}

	/**
	 * Check Wordpress Already exist
	 */
	public static function check_wp_exist() {
		if ( file_exists( \WP_CLI_FileSystem::path_join( getcwd(), 'wp-load.php' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get Base Path
	 */
	public static function get_base_path() {

		//Get default path
		$path = '';
		if ( defined( 'ABSPATH' ) ) {
			$path = \WP_CLI_FileSystem::normalize_path( ABSPATH );
		}

		//GetCWD php
		if ( trim( $path ) == "" || $path == "/" || $path == "\\" ) {
			$path = \WP_CLI_FileSystem::normalize_path( getcwd() );
		}

		return $path;
	}

}