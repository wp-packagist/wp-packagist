<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\install;

class Dir {
	/**
	 * List of dir that create in wp-content
	 * @see https://codex.wordpress.org/Determining_Plugin_and_Content_Directories
	 *
	 * @var array
	 */
	public static $dirs = array( 'uploads', 'mu-plugins', 'languages' );

	/**
	 * Create Directory in wp-content
	 */
	public static function create_require_folder() {

		$wp_content = \WP_CLI_Util::getcwd( 'wp-content' );
		foreach ( self::$dirs as $folder ) {
			if ( \WP_CLI_FileSystem::folder_exist( \WP_CLI_FileSystem::path_join( $wp_content, $folder ) ) === false ) {
				\WP_CLI_FileSystem::create_dir( $folder, $wp_content );
			}
		}
	}

	/**
	 * Get MU-PLUGINS path
	 *
	 * @return mixed
	 */
	public static function eval_get_mu_plugins_path() {
		return \WP_CLI::runcommand( 'eval "if(defined(\'WPMU_PLUGIN_DIR\')) { echo WPMU_PLUGIN_DIR; } else { echo \'\'; }"', array( 'return' => 'stdout' ) );
	}

	/**
	 * Get mu-plugin path
	 *
	 * @param $pkg_array
	 * @return bool|string
	 */
	public static function get_mu_plugins_path( $pkg_array ) {
		//Get wp-content path
		$wp_content = 'wp-content';
		if ( isset( $pkg_array['dir']['wp-content'] ) ) {
			$wp_content = $pkg_array['dir']['wp-content'];
		}

		//Get Mu Plugins Path
		return \WP_CLI_FileSystem::path_join( \WP_CLI_Util::getcwd(), \WP_CLI_FileSystem::path_join( $wp_content, 'mu-plugins' ) );
	}

	/**
	 * Update WordPress Package DIR
	 *
	 * @param $params
	 * @param $dir
	 * @param $pkg_array
	 * @param string $step | (install or update)
	 */
	public static function update_dir( $params, $dir, $pkg_array, $step = 'install' ) {

		//Load Wp-config Transform
		$config_transformer = Config::get_config_transformer();

		//Add Site Constant
		self::update_site_constant( $dir, $pkg_array['config']['url'], $config_transformer );

		//Change folders
		foreach ( $params as $folder ) {

			//Check exist value
			if ( ! array_key_exists( $folder, $dir ) ) {
				$dir[ $folder ] = null;
			}

			//Load Method
			$method_name = 'change_' . str_replace( "-", "_", $folder ) . '_folder';
			self::{$method_name}( $dir, $config_transformer, true, $step );
		}
	}

	/**
	 * Add Site Url constant
	 *
	 * @param $dir
	 * @param $site_url
	 * @param $wp_config
	 */
	public static function update_site_constant( $dir, $site_url, $wp_config ) {

		//Sanitize $site url
		$site_url = rtrim( $site_url, "/" );

		//List Constant
		$list = array( 'WP_HOME', 'WP_SITEURL' );

		//Check exist dir
		if ( count( $dir ) > 0 and ! empty( $site_url ) and ( ( isset( $dir['wp-content'] ) and $dir['wp-content'] != "wp-content" ) || ( isset( $dir['plugins'] ) and $dir['plugins'] != "plugins" ) ) ) {
			foreach ( $list as $const ) {
				$wp_config->update( 'constant', $const, $site_url, array( 'raw' => false, 'normalize' => true ) );
			}
		} else {
			foreach ( $list as $const ) {
				$wp_config->remove( 'constant', $const );
			}
		}

	}

	/**
	 * Get wp_content Dir Path
	 */
	public static function get_content_dir() {
		if ( ! defined( 'WP_CONTENT_DIR' ) ) {
			return \WP_CLI_FileSystem::path_join( \WP_CLI_Util::getcwd(), 'wp-content' );
		} else {
			return \WP_CLI_FileSystem::normalize_path( WP_CONTENT_DIR );
		}
	}

	/**
	 * Change wp-content Folder
	 *
	 * @param $dir
	 * @param $wp_config
	 * @param bool $log
	 * @param $step
	 */
	public static function change_wp_content_folder( $dir, $wp_config, $log = false, $step = 'install' ) {

		//Get base wp-content path
		$base_path = rtrim( \WP_CLI_FileSystem::path_join( getcwd(), 'wp-content' ), "/" );

		//Get current wp-content path
		$current_path = rtrim( self::get_content_dir(), "/" ) . "/";

		//Check changed wp-content dir
		$is_change = false;

		//constant list
		$constants_list = array( 'WP_CONTENT_FOLDER', 'WP_CONTENT_DIR', 'WP_CONTENT_URL' );

		//Check if null value (Reset to Default)
		if ( is_null( $dir['wp-content'] ) ) {
			if ( $base_path != $current_path and $step != 'install' ) {
				$is_change = true;

				//First Remove Constant
				foreach ( $constants_list as $const ) {
					$wp_config->remove( 'constant', $const );
				}

				//Move Folder
				\WP_CLI_FileSystem::move( $current_path, $base_path );
			}
		} else {

			//New Path
			$new_path = rtrim( \WP_CLI_FileSystem::path_join( getcwd(), trim( $dir['wp-content'], "/" ) ), "/" ) . "/";
			if ( $new_path != $current_path ) {
				$is_change = true;

				//Move Folder
				\WP_CLI_FileSystem::move( $current_path, $new_path );

				//Add Constant
				$wp_config->update( 'constant', 'WP_CONTENT_FOLDER', trim( $dir['wp-content'], "/" ), array( 'raw' => false, 'normalize' => true ) );
				$wp_config->update( 'constant', 'WP_CONTENT_DIR', 'ABSPATH . WP_CONTENT_FOLDER', array( 'raw' => true, 'normalize' => true ) );
				$wp_config->update( 'constant', 'WP_CONTENT_URL', "WP_SITEURL . '/' . WP_CONTENT_FOLDER", array( 'raw' => true, 'normalize' => true ) );
			}
		}

		//Add Log
		if ( $log and $is_change ) {
			install::add_detail_log( Package::_e( 'package', 'change_custom_folder', array( "[folder]" => "wp-content" ) ) );
		}
	}

	/**
	 * Get Plugin Dir Path
	 */
	public static function get_plugins_dir() {
		if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
			return \WP_CLI_FileSystem::path_join( getcwd(), 'wp-content/plugins' );
		} else {
			return \WP_CLI_FileSystem::normalize_path( WP_PLUGIN_DIR );
		}
	}

	/**
	 * Change plugins Folder
	 *
	 * @param $dir
	 * @param $wp_config
	 * @param bool $log
	 * @param string $step
	 */
	public static function change_plugins_folder( $dir, $wp_config, $log = false, $step = 'install' ) {

		//Get base plugins path
		$base_path = rtrim( \WP_CLI_FileSystem::path_join( getcwd(), 'wp-content/plugins' ), "/" );

		//Get current plugins path
		$current_path = rtrim( self::get_plugins_dir(), "/" ) . "/";

		//Check changed wp-content dir
		$is_change = false;

		//constant list
		$constants_list = array( 'WP_PLUGIN_DIR', 'PLUGINDIR', 'WP_PLUGIN_URL' );

		//Check if null value (Reset to Default)
		if ( is_null( $dir['plugins'] ) ) {
			if ( $base_path != $current_path and $step != 'install' ) {
				$is_change = true;

				//First Remove Constant
				foreach ( $constants_list as $const ) {
					$wp_config->remove( 'constant', $const );
				}

				//Move Folder
				\WP_CLI_FileSystem::move( $current_path, $base_path );
			}
		} else {

			//Get first Character (check in wp-content)
			$first_character = substr( $dir['plugins'], 0, 1 );

			//Get wp-content path
			$wp_content = 'wp-content';
			if ( ! is_null( $dir['wp-content'] ) ) {
				$wp_content = $dir['wp-content'];
			}

			//Old Path
			$old_path = $current_path;
			if ( $step == "install" ) {
				$old_path = \WP_CLI_FileSystem::path_join( getcwd(), \WP_CLI_FileSystem::path_join( $wp_content, 'plugins' ) );
			}

			//New Path
			if ( $first_character == "/" ) {
				$new_path = \WP_CLI_FileSystem::path_join( getcwd(), ltrim( $dir['plugins'], "/" ) );
			} else {
				$new_path = \WP_CLI_FileSystem::path_join( getcwd(), \WP_CLI_FileSystem::path_join( $wp_content, ltrim( $dir['plugins'], "/" ) ) );
			}

			if ( rtrim( $new_path, "/" ) != rtrim( $current_path, "/" ) ) {
				$is_change = true;

				//Move Folder
				\WP_CLI_FileSystem::move( $old_path, $new_path );

				//Get Path and URL for Constant
				if ( $first_character == "/" ) {
					$constant_path = "ABSPATH . '" . ltrim( $dir['plugins'], "/" ) . "'";
					$constant_url  = "WP_SITEURL . '/" . ltrim( $dir['plugins'], "/" ) . "'";
				} else {

					if ( is_null( $dir['wp-content'] ) ) {
						$constant_path = "'wp-content/" . ltrim( $dir['plugins'], "/" ) . "'";
						$constant_url  = "WP_SITEURL . '/wp-content/" . ltrim( $dir['plugins'], "/" ) . "'";
					} else {
						$constant_path = "WP_CONTENT_DIR . '/" . ltrim( $dir['plugins'], "/" ) . "'";
						$constant_url  = "WP_CONTENT_URL . '/" . ltrim( $dir['plugins'], "/" ) . "'";
					}
				}

				//Add Constant
				$wp_config->update( 'constant', 'WP_PLUGIN_DIR', $constant_path, array( 'raw' => true, 'normalize' => true ) );
				$wp_config->update( 'constant', 'PLUGINDIR', $constant_path, array( 'raw' => true, 'normalize' => true ) );
				$wp_config->update( 'constant', 'WP_PLUGIN_URL', $constant_url, array( 'raw' => true, 'normalize' => true ) );
			}
		}

		//Add Log
		if ( $log and $is_change ) {
			install::add_detail_log( Package::_e( 'package', 'change_custom_folder', array( "[folder]" => "plugins" ) ) );
		}

	}

	/**
	 * Get themes Dir Path
	 */
	public static function get_themes_dir() {
		if ( ! function_exists( 'get_theme_root' ) ) {
			return \WP_CLI_FileSystem::path_join( getcwd(), 'wp-content/themes' );
		} else {
			return \WP_CLI_FileSystem::normalize_path( get_theme_root() );
		}
	}

	/**
	 * Change themes Folder
	 *
	 * @param $dir
	 * @param $wp_config
	 * @param bool $log
	 * @param string $step
	 */
	public static function change_themes_folder( $dir, $wp_config, $log = false, $step = 'install' ) {

		//Get base themes path
		$base_path = rtrim( \WP_CLI_FileSystem::path_join( getcwd(), 'wp-content/themes' ), "/" );

		//Get current themes path
		$current_path = rtrim( self::get_themes_dir(), "/" ) . "/";

		//Check changed themes dir
		$is_change = false;

		//Get Mu Plugins Path
		$wp_content = 'wp-content';
		if ( ! is_null( $dir['wp-content'] ) ) {
			$wp_content = $dir['wp-content'];
		}
		$mu_plugins_path = \WP_CLI_FileSystem::path_join( getcwd(), \WP_CLI_FileSystem::path_join( $wp_content, 'mu-plugins' ) );

		//mu-plugins theme-dir path
		$themes_mu_plugins = \WP_CLI_FileSystem::path_join( $mu_plugins_path, 'theme-dir.php' );

		//Check if null value (Reset to Default)
		if ( is_null( $dir['themes'] ) ) {
			if ( $base_path != $current_path and $step != 'install' ) {
				$is_change = true;

				//Remove Mu-plugins
				if ( file_exists( $themes_mu_plugins ) ) {
					\WP_CLI_FileSystem::remove_file( $themes_mu_plugins );
				}

				//Move Folder
				\WP_CLI_FileSystem::move( $current_path, $base_path );
			}
		} else {

			//Get first Character (check in wp-content)
			$first_character = substr( $dir['themes'], 0, 1 );

			//Old Path
			$old_path = $current_path;
			if ( $step == "install" ) {
				$old_path = \WP_CLI_FileSystem::path_join( getcwd(), \WP_CLI_FileSystem::path_join( $wp_content, 'themes' ) );
			}

			//New Path
			if ( $first_character == "/" ) {
				$new_path = \WP_CLI_FileSystem::path_join( getcwd(), ltrim( $dir['themes'], "/" ) );
			} else {
				$new_path = \WP_CLI_FileSystem::path_join( getcwd(), \WP_CLI_FileSystem::path_join( $wp_content, ltrim( $dir['themes'], "/" ) ) );
			}

			if ( rtrim( $new_path, "/" ) != rtrim( $current_path, "/" ) ) {
				$is_change = true;

				//Move Folder
				\WP_CLI_FileSystem::move( $old_path, $new_path );

				//Get Path and URL for Constant
				if ( $first_character == "/" ) {
					$theme_directory     = $theme_directory_path = "ABSPATH . '" . ltrim( $dir['themes'], "/" ) . "'";
					$theme_directory_url = "home_url( '" . ltrim( $dir['themes'], "/" ) . "' )";
				} else {
					$theme_directory      = "'/" . ltrim( $dir['themes'], "/" ) . "'";
					$theme_directory_path = "WP_CONTENT_DIR . '/" . ltrim( $dir['themes'], "/" ) . "'";
					$theme_directory_url  = "content_url( '" . ltrim( $dir['themes'], "/" ) . "' )";
				}

				//Upload Mu-Plugins
				$mustache = \WP_CLI_FileSystem::load_mustache( WP_CLI_PACKAGIST_TEMPLATE_PATH );
				\WP_CLI_FileSystem::file_put_content(
					$themes_mu_plugins,
					$mustache->render( 'mu-plugins/theme-dir', array(
						'theme_directory'      => $theme_directory,
						'theme_directory_path' => $theme_directory_path,
						'theme_directory_url'  => $theme_directory_url,
					) )
				);
			}

		}

		//Add Log
		if ( $log and $is_change ) {
			install::add_detail_log( Package::_e( 'package', 'change_custom_folder', array( "[folder]" => "themes" ) ) );
		}
	}

	/**
	 * Get Uploads Dir Path
	 */
	public static function get_uploads_dir() {
		if ( ! function_exists( 'wp_upload_dir' ) ) {
			return \WP_CLI_FileSystem::path_join( \WP_CLI_Util::getcwd(), 'wp-content/uploads' );
		} else {
			$upload_dir = wp_upload_dir();
			return \WP_CLI_FileSystem::normalize_path( $upload_dir['basedir'] );
		}
	}

	/**
	 * Get Uploads Dir Url
	 *
	 * @run after_wp_load
	 * @param string $what
	 * @return mixed
	 */
	public static function get_wp_uploads( $what = 'baseurl' ) {
		$uploads = \WP_CLI::runcommand( 'eval "echo json_encode( wp_upload_dir() );"', array( 'return' => 'stdout', 'parse' => 'json' ) );
		return rtrim( \WP_CLI_Util::backslash_to_slash( $uploads[ $what ] ), "/" );
	}

	/**
	 * Change uploads Folder
	 *
	 * @param $dir
	 * @param $wp_config
	 * @param bool $log
	 * @param string $step
	 */
	public static function change_uploads_folder( $dir, $wp_config, $log = false, $step = 'install' ) {

		//Get base uploads path
		$base_path = rtrim( \WP_CLI_FileSystem::path_join( getcwd(), 'wp-content/uploads' ), "/" );

		//Get current uploads path
		$current_path = rtrim( self::get_uploads_dir(), "/" ) . "/";

		//Check changed wp-content dir
		$is_change = false;

		// Get Current Base Uploads Url
		if ( $step == "update" ) {
			$before_uploads_url = self::get_wp_uploads();
		}

		//constant list
		$constants_list = array( 'UPLOADS' );

		//Check if null value (Reset to Default)
		if ( is_null( $dir['uploads'] ) ) {
			if ( $base_path != $current_path and $step != 'install' ) {
				$is_change = true;

				//First Remove Constant
				foreach ( $constants_list as $const ) {
					$wp_config->remove( 'constant', $const );
				}

				//Move Folder
				\WP_CLI_FileSystem::move( $current_path, $base_path );
			}
		} else {

			//Get first Character (check in wp-content)
			$first_character = substr( $dir['uploads'], 0, 1 );

			//Get wp-content path
			$wp_content = 'wp-content';
			if ( ! is_null( $dir['wp-content'] ) ) {
				$wp_content = $dir['wp-content'];
			}

			//Old Path
			$old_path = $current_path;
			if ( $step == "install" ) {
				$old_path = \WP_CLI_FileSystem::path_join( getcwd(), \WP_CLI_FileSystem::path_join( $wp_content, 'uploads' ) );
			}

			//New Path
			if ( $first_character == "/" ) {
				$new_path = \WP_CLI_FileSystem::path_join( getcwd(), ltrim( $dir['uploads'], "/" ) );
			} else {
				$new_path = \WP_CLI_FileSystem::path_join( getcwd(), \WP_CLI_FileSystem::path_join( $wp_content, ltrim( $dir['uploads'], "/" ) ) );
			}

			if ( rtrim( $new_path, "/" ) != rtrim( $current_path, "/" ) ) {
				$is_change = true;

				//Move Folder
				\WP_CLI_FileSystem::move( $old_path, $new_path );

				//Get Path and URL for Constant
				if ( $first_character == "/" ) {
					$constant_path = "''.'" . trim( $dir['uploads'], "/" ) . "'";
				} else {
					if ( ! is_null( $dir['wp-content'] ) ) {
						$constant_path = "WP_CONTENT_FOLDER . '/" . trim( $dir['uploads'], "/" ) . "'";
					} else {
						$constant_path = "'wp-content/" . trim( $dir['uploads'], "/" ) . "'";
					}
				}

				//Add Constant
				$wp_config->add( 'constant', 'UPLOADS', $constant_path, array( 'raw' => true, 'normalize' => true ) );
			}
		}

		//Add Log
		if ( $log and $is_change ) {

			// change folder Log
			install::add_detail_log( Package::_e( 'package', 'change_custom_folder', array( "[folder]" => "uploads" ) ) );

			// fix Attachment Link in DB
			if ( $step == "update" and isset( $before_uploads_url ) ) {
				\WP_CLI_Helper::pl_wait_start();

				# Get New Uploads dir url
				$new_uploads_dir = self::get_wp_uploads();

				# Search-replace in DB
				\WP_CLI_Helper::search_replace_db( $before_uploads_url, $new_uploads_dir );

				# Show Log
				\WP_CLI_Helper::pl_wait_end();
				install::add_detail_log( Package::_e( 'package', 'srdb_uploads' ) );
			}
		}
	}

}