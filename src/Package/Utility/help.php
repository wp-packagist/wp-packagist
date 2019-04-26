<?php

namespace WP_CLI_PACKAGIST\Package\Utility;

use WP_CLI_PACKAGIST\API\WP_Plugins_Api;
use WP_CLI_PACKAGIST\API\WP_Themes_Api;
use WP_CLI_PACKAGIST\Package\Arguments\Locale;
use WP_CLI_PACKAGIST\Package\Arguments\Version;
use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Utility\CLI;
use WP_CLI_PACKAGIST\Utility\PHP;

class help {
	/**
	 * List Of WordPress Package Help List
	 *
	 * @var array
	 */
	public static $list = array(
		'version'  => array(
			'title' => 'WordPress versions',
			'desc'  => 'Show list of WordPress core versions.'
		),
		'locale'   => array(
			'title' => 'WordPress Languages',
			'desc'  => 'Show list of WordPress core languages.'
		),
		'timezone' => array(
			'title' => 'Timezone',
			'desc'  => 'Show list of WordPress Timezone.'
		),
		'plugin'   => array(
			'title' => 'Plugin information',
			'desc'  => 'Show plugin information from WordPress directory.'
		),
		'theme'    => array(
			'title' => 'Theme information',
			'desc'  => 'Show theme information from WordPress directory.'
		),
	);

	/**
	 * WordPress version
	 */
	public static function version() {

		# Remove please wait
		if ( defined( 'WP_CLI_PLEASE_WAIT_LOG' ) ) {
			CLI::pl_wait_end();
		}

		# Get List of Wordpress version
		$list = Version::get_wordpress_version();

		# Check Error
		if ( $list['status'] === false ) {
			CLI::error( $list['data'] );
		} else {

			# Show List
			foreach ( $list['data'] as $key => $val ) {
				CLI::line( $key . ( $val == "latest" ? " " . CLI::color( "(Latest)", "B" ) : '' ) );
			}
		}

	}

	/**
	 * WordPress language
	 */
	public static function locale() {

		# Remove please wait
		if ( defined( 'WP_CLI_PLEASE_WAIT_LOG' ) ) {
			CLI::pl_wait_end();
		}

		# Get List of Wordpress locale
		$list = Locale::get_wordpress_locale();

		# Check Error
		if ( $list['status'] === false ) {
			CLI::error( $list['data'] );
		} else {

			//Create List
			$i     = 0;
			$array = array();
			foreach ( $list['data'] as $key => $val ) {
				$array[ $i ] = array(
					'language' => $key,
					'name'     => $val
				);
				$i ++;
			}

			//Show List
			CLI::format_items( 'table', $array, false );
		}
	}

	/**
	 * WordPress Timezone
	 */
	public static function timezone() {

		# Remove please wait
		if ( defined( 'WP_CLI_PLEASE_WAIT_LOG' ) ) {
			CLI::pl_wait_end();
		}

		# Get List of Wordpress TimeZone
		$list = json_decode( Package::get_config( 'package', 'wordpress_timezone' ), true );

		# Create List
		$array = array();
		foreach ( $list as $key ) {
			$array[] = array( 'Timezone' => $key );
		}

		# Show List
		CLI::format_items( 'table', $array, false );
	}

	/**
	 * WordPress plugin
	 *
	 * @param bool $plugin
	 * @throws \WP_CLI\ExitException
	 */
	public static function plugin( $plugin ) {

		# Remove please wait
		if ( defined( 'WP_CLI_PLEASE_WAIT_LOG' ) ) {
			CLI::pl_wait_end();
		}

		# Get Plugin Data
		$plugins_api = new WP_Plugins_Api();
		$plugin_inf  = $plugins_api->get_plugin_data( $plugin );

		# Check Error
		if ( $plugin_inf['status'] === false ) {
			CLI::error( $plugin_inf['data'] );
		} else {

			// Show Title Plugin information
			CLI::br();
			CLI::line( CLI::color( "# Plugin information", "Y" ) );
			CLI::br();

			//Name
			CLI::line( ucfirst( "name" ) . "           " . CLI::color( $plugin_inf['data']['name'], "P" ) . " by " . CLI::color( strip_tags( $plugin_inf['data']['author'] ), "B" ) );

			//Url
			CLI::line( ucfirst( "url" ) . "            https://fa.wordpress.org/plugins/" . ( isset( $plugin_inf['data']['slug'] ) ? $plugin_inf['data']['slug'] : $plugin ) );

			//Author
			if ( isset( $plugin_inf['data']['author_profile'] ) ) {
				CLI::line( ucfirst( "author" ) . "         " . ucfirst( basename( $plugin_inf['data']['author_profile'] ) ) . " (" . $plugin_inf['data']['author_profile'] . ")" );
			}

			//HomePage
			if ( isset( $plugin_inf['data']['homepage'] ) ) {
				CLI::line( ucfirst( "home page" ) . "      " . $plugin_inf['data']['homepage'] );
			}

			//Downloaded
			if ( isset( $plugin_inf['data']['downloaded'] ) ) {
				CLI::line( ucfirst( "downloaded" ) . "     " . number_format( $plugin_inf['data']['downloaded'] ) );
			}

			//Tags
			if ( isset( $plugin_inf['data']['tags'] ) ) {
				$tags = '';
				$i    = 0;
				foreach ( $plugin_inf['data']['tags'] as $key => $value ) {
					$tags .= $value;
					if ( ++ $i != count( $plugin_inf['data']['tags'] ) ) {
						$tags .= ', ';
					}
				}
				CLI::line( ucfirst( "tags" ) . "           " . $tags );
			}

			//download Link
			if ( isset( $plugin_inf['data']['download_link'] ) ) {
				CLI::line( ucfirst( "download link" ) . "  " . $plugin_inf['data']['download_link'] );
			}

			//last_updated
			if ( isset( $plugin_inf['data']['last_updated'] ) ) {
				CLI::line( ucfirst( "last updated" ) . "   " . str_replace( "GMT", "", $plugin_inf['data']['last_updated'] ) );
			}

			//description
			if ( isset( $plugin_inf['data']['sections']['description'] ) ) {
				CLI::br();
				CLI::line( CLI::color( "# Description", "Y" ) );
				CLI::br();
				$desc = PHP::substr( strip_tags( $plugin_inf['data']['sections']['description'] ), 3000 );
				CLI::line( $desc . '..' );
			}

			// Show List Versions
			CLI::br();
			CLI::line( CLI::color( "# List Versions", "Y" ) );
			CLI::br();

			# Create List version
			$version_list = $plugins_api->get_list_plugin_versions( $plugin );
			if ( $version_list['status'] != false ) {
				$array = array();
				foreach ( $version_list['data'] as $key ) {
					$array[] = array( 'versions' => $key . ( $key == $plugin_inf['data']['version'] ? " " . "(Latest)" : '' ) );
				}

				# Show List
				CLI::format_items( 'table', $array, false );
			}
		}
	}

	/**
	 * WordPress theme
	 *
	 * @param bool $theme
	 * @throws \WP_CLI\ExitException
	 */
	public static function theme( $theme ) {

		# Remove please wait
		if ( defined( 'WP_CLI_PLEASE_WAIT_LOG' ) ) {
			CLI::pl_wait_end();
		}

		# Get Plugin Data
		$themes_api = new WP_Themes_Api();
		$theme_inf  = $themes_api->get_theme_data( $theme );

		# Check Error
		if ( $theme_inf['status'] === false ) {
			CLI::error( $theme_inf['data'] );
		} else {

			// Show Title theme information
			CLI::br();
			CLI::line( CLI::color( "# Theme information", "Y" ) );
			CLI::br();

			//Name
			CLI::line( ucfirst( "name" ) . "            " . CLI::color( $theme_inf['data']['name'], "P" ) . " by " . CLI::color( strip_tags( $theme_inf['data']['author'] ), "B" ) );

			//preview_url
			if ( isset( $theme_inf['data']['preview_url'] ) ) {
				CLI::line( ucfirst( "preview url" ) . "     " . $theme_inf['data']['preview_url'] );
			}

			//HomePage
			if ( isset( $theme_inf['data']['homepage'] ) ) {
				CLI::line( ucfirst( "home page" ) . "       " . $theme_inf['data']['homepage'] );
			}

			//num_ratings
			if ( isset( $theme_inf['data']['num_ratings'] ) ) {
				$num_rating = (int) $theme_inf['data']['num_ratings'];
				if ( $num_rating > 0 ) {
					CLI::line( ucfirst( "number ratings" ) . "  " . number_format( $theme_inf['data']['num_ratings'] ) );
				}
			}

			//description
			if ( isset( $theme_inf['data']['description'] ) ) {
				CLI::br();
				CLI::line( CLI::color( "# Description", "Y" ) );
				CLI::br();
				$desc = PHP::substr( strip_tags( $theme_inf['data']['description'] ), 3000 );
				CLI::line( $desc . '..' );
			}

			// Show List Versions
			CLI::br();
			CLI::line( CLI::color( "# List Versions", "Y" ) );
			CLI::br();

			# Create List version
			$version_list = $themes_api->get_list_theme_versions( $theme );
			if ( $version_list['status'] != false ) {
				$array = array();
				foreach ( $version_list['data'] as $key ) {
					$array[] = array( 'versions' => $key . ( $key == $theme_inf['data']['version'] ? " " . "(Latest)" : '' ) );
				}

				# Show List
				CLI::format_items( 'table', $array, false );
			}
		}

	}

	/**
	 * Run WordPress Package Helper
	 */
	public static function run() {

		// Get Helper List
		$help_list = self::$list;
		$x         = 1;
		CLI::br();
		foreach ( $help_list as $name => $value ) {
			CLI::line( "{$x}. " . CLI::color( $value['title'], "Y" ) );
			CLI::line( CLI::color( "     " . $value['desc'], "P" ) );
			CLI::br();
			$x ++;
		}

		//Define STDIN
		PHP::define_stdin();

		// Input Get ID
		while ( true ) {
			echo "Please type ID and press enter key :  ";
			$ID = fread( STDIN, 80 );
			if ( is_numeric( trim( $ID ) ) ) {
				if ( $ID <= count( $help_list ) ) {
					break;
				}
			}
		}

		//Check How function
		if ( isset( $ID ) ) {
			$method = false;
			$y      = 1;
			foreach ( $help_list as $function => $value ) {
				if ( $y == $ID ) {
					$method = $function;
				}
				$y ++;
			}

			// Run Method
			if ( ! empty( $method ) ) {

				switch ( $method ) {
					case "version":
					case "locale":
					case "timezone":
						CLI::pl_wait_start();
						help::{$method}();
						break;

					case "plugin":
						self::input( "plugin", array( 'woocommerce', 'advanced-custom-fields' ) );
						break;

					case "theme":
						self::input( "theme", array( 'twentynineteen', 'ascension' ) );
						break;
				}
			}
		}

	}

	/**
	 * Create input for plugin/theme WordPress
	 *
	 * @param string $type
	 * @param array $example
	 */
	public static function input( $type = 'theme', $example = array() ) {

		// Show separator
		CLI::line( "--------------------------------" );
		CLI::br();

		// Input Get Slug
		while ( true ) {

			// Helper log
			echo "Please type " . $type . " slug e.g " . CLI::color( $example[0], "B" ) . " or " . CLI::color( $example[1], "B" ) . " and press enter key :  ";

			// Get input
			$slug = fread( STDIN, 100 );

			// Check Slug
			if ( is_string( $slug ) and ! empty( $slug ) and ! is_numeric( $slug ) ) {
				break;
			}
		}

		// Sanitize Slug
		if ( isset( $slug ) ) {
			$slug = strtolower( str_ireplace( array( "_", " " ), "-", trim( $slug ) ) );

			//Get Plugin Data
			CLI::pl_wait_start();
			self::{$type}( $slug );
		}

	}

}