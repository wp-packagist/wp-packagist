<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Utility\CLI;
use WP_CLI_PACKAGIST\Utility\FileSystem;
use WP_CLI_PACKAGIST\Utility\PHP;

class Permalink {
	/**
	 * Force Rewrite Flush
	 *
	 * @param bool $force
	 */
	public static function flush_rewrite( $force = false ) {

		$command = 'eval "flush_rewrite_rules(' . ( $force === true ? 'true' : '' ) . ');"';
		\WP_CLI::runcommand( $command );
	}

	/**
	 * Create Mod_Rewrite File For Server (.htaccess or web.config)
	 *
	 * We Use Bottom Source Code :
	 * wp-includes\class-wp-rewrite.php: 1744
	 * wp-admin\includes\network.php line : 492
	 *
	 * @see
	 * https://codex.wordpress.org/Using_Permalinks#Creating_and_editing_.28.htaccess.29
	 * https://codex.wordpress.org/Multisite_Network_Administration#.htaccess_and_Mod_Rewrite
	 *
	 * @param bool $is_network
	 * @param bool $subdomain
	 * @param array $dirs
	 * @run after_wp_load
	 */
	public static function create_permalink_file( $is_network = false, $subdomain = false, $dirs = array() ) {
		global $wp_rewrite, $wpdb;

		//Get Home Path
		$home_path = rtrim( PHP::getcwd(), "/" );

		//Include WordPress Admin File
		$admin_file = array( 'admin.php', 'network.php' );
		foreach ( $admin_file as $file ) {

			$path = $home_path . '/wp-admin/includes/' . $file;
			if ( file_exists( $path ) ) {
				require_once $path;
			}
		}

		//Get Htaccess File
		$htaccess_file = $home_path . '/.htaccess';

		//Get Web.config
		$web_config_file = $home_path . '/web.config';

		//If Single WordPress install
		if ( $is_network === false ) {

			//Check if Linux Or Windows
			# https://developer.wordpress.org/reference/functions/iis7_supports_permalinks/
			if ( iis7_supports_permalinks() ) {

				// Using win_is_writable() instead of is_writable() because of a bug in Windows PHP
				if ( iis7_supports_permalinks() ) {
					$rule = $wp_rewrite->iis7_url_rewrite_rules( false, '', '' );
					if ( ! empty( $rule ) ) {
						iis7_add_rewrite_rule( $web_config_file, $rule );
					} else {
						iis7_delete_rewrite_rule( $web_config_file );
					}
				}

			} else {

				//Create Htaccess File
				$rules = explode( "\n", $wp_rewrite->mod_rewrite_rules() );
				insert_with_markers( $htaccess_file, 'WordPress', $rules );
			}

		} else {
			//If WordPress Network
			$slashed_home      = trailingslashit( get_option( 'home' ) );
			$base              = parse_url( $slashed_home, PHP_URL_PATH );
			$document_root_fix = str_replace( '\\', '/', realpath( $_SERVER['DOCUMENT_ROOT'] ) );
			$abspath_fix       = str_replace( '\\', '/', ABSPATH );
			$home_path         = 0 === strpos( $abspath_fix, $document_root_fix ) ? $document_root_fix . $base : get_home_path();
			$wp_siteurl_subdir = preg_replace( '#^' . preg_quote( $home_path, '#' ) . '#', '', $abspath_fix );
			$rewrite_base      = ! empty( $wp_siteurl_subdir ) ? ltrim( trailingslashit( $wp_siteurl_subdir ), '/' ) : '';

			$subdir_match          = $subdomain ? '' : '([_0-9a-zA-Z-]+/)?';
			$subdir_replacement_01 = $subdomain ? '' : '$1';
			$subdir_replacement_12 = $subdomain ? '$1' : '$2';

			if ( iis7_supports_permalinks() ) {

				//File name
				$file_path = $web_config_file;

				//Content File
				// IIS doesn't support RewriteBase, all your RewriteBase are belong to us
				$iis_subdir_match       = ltrim( $base, '/' ) . $subdir_match;
				$iis_rewrite_base       = ltrim( $base, '/' ) . $rewrite_base;
				$iis_subdir_replacement = $subdomain ? '' : '{R:1}';

				$file_content = '<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="WordPress Rule 1" stopProcessing="true">
                    <match url="^index\.php$" ignoreCase="false" />
                    <action type="None" />
                </rule>';
				if ( get_site_option( 'ms_files_rewriting' ) ) {
					$file_content .= '
                <rule name="WordPress Rule for Files" stopProcessing="true">
                    <match url="^' . $iis_subdir_match . 'files/(.+)" ignoreCase="false" />
                    <action type="Rewrite" url="' . $iis_rewrite_base . WPINC . '/ms-files.php?file={R:1}" appendQueryString="false" />
                </rule>';
				}
				$file_content .= '
                <rule name="WordPress Rule 2" stopProcessing="true">
                    <match url="^' . $iis_subdir_match . 'wp-admin$" ignoreCase="false" />
                    <action type="Redirect" url="' . $iis_subdir_replacement . 'wp-admin/" redirectType="Permanent" />
                </rule>
                <rule name="WordPress Rule 3" stopProcessing="true">
                    <match url="^" ignoreCase="false" />
                    <conditions logicalGrouping="MatchAny">
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" ignoreCase="false" />
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" ignoreCase="false" />
                    </conditions>
                    <action type="None" />
                </rule>
                <rule name="WordPress Rule 4" stopProcessing="true">
                    <match url="^' . $iis_subdir_match . '(wp-(content|admin|includes).*)" ignoreCase="false" />
                    <action type="Rewrite" url="' . $iis_rewrite_base . '{R:1}" />
                </rule>
                <rule name="WordPress Rule 5" stopProcessing="true">
                    <match url="^' . $iis_subdir_match . '([_0-9a-zA-Z-]+/)?(.*\.php)$" ignoreCase="false" />
                    <action type="Rewrite" url="' . $iis_rewrite_base . '{R:2}" />
                </rule>
                <rule name="WordPress Rule 6" stopProcessing="true">
                    <match url="." ignoreCase="false" />
                    <action type="Rewrite" url="index.php" />
                </rule>
            </rules>
        </rewrite>
    </system.webServer>
</configuration>
';

			} else {

				// Extra Dir
				$extra_dirs = self::extra_dirs_in_network( $subdir_match, $rewrite_base, $subdir_replacement_12, $dirs );

				//File name
				$file_path = $htaccess_file;

				//File Content
				$ms_files_rewriting = '';
				if ( get_site_option( 'ms_files_rewriting' ) ) {
					$ms_files_rewriting = "\n# uploaded files\nRewriteRule ^";
					$ms_files_rewriting .= $subdir_match . "files/(.+) {$rewrite_base}" . WPINC . "/ms-files.php?file={$subdir_replacement_12} [L]" . "\n";
				}

				$file_content = <<<EOF
RewriteEngine On
RewriteBase {$base}
RewriteRule ^index\.php$ - [L]
{$ms_files_rewriting}
# add a trailing slash to /wp-admin
RewriteRule ^{$subdir_match}wp-admin$ {$subdir_replacement_01}wp-admin/ [R=301,L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^{$subdir_match}(wp-(content|admin|includes).*) {$rewrite_base}{$subdir_replacement_12} [L]{$extra_dirs}
RewriteRule ^{$subdir_match}(.*\.php)$ {$rewrite_base}$subdir_replacement_12 [L]
RewriteRule . index.php [L]

EOF;
				// Sanitize Path
				$file_content = str_replace( "//", "/", rtrim( str_replace( PHP::getcwd(), $base, $file_content ), "/" ) );
			}

			//Create File
			FileSystem::file_put_content( $file_path, $file_content );
		}
	}

	/**
	 * Added Extra Dirs in WordPress Network if user changed dirs
	 *
	 * @param $subdir_match
	 * @param $rewrite_base
	 * @param $subdir_replacement_12
	 * @param array $dirs
	 * @return string
	 */
	public static function extra_dirs_in_network( $subdir_match, $rewrite_base, $subdir_replacement_12, $dirs = array() ) {
		$extra_dirs = '';

		$wp_content = 'wp-content';
		$uploads    = $plugins = $themes = '';
		if ( empty( $dirs ) ) {

			// Get All WordPress default theme
			$wp_content = str_ireplace( PHP::getcwd() . "/", "", Dir::get_content_dir() );
			$uploads    = str_ireplace( PHP::getcwd() . "/", "", Dir::get_uploads_dir() );
			$plugins    = str_ireplace( PHP::getcwd() . "/", "", Dir::get_plugins_dir() );
			$themes     = str_ireplace( PHP::getcwd() . "/", "", Dir::get_themes_dir() );
		} else {

			// Get Dir Data From WordPress Package
			foreach ( array( 'wp_content', 'uploads', 'plugins', 'themes' ) as $dir ) {
				if ( isset( $dirs[ $dir ] ) ) {
					${$dir} = $dirs[ $dir ];
				}
			}
		}

		// Check Wp-content
		if ( substr( $wp_content, 0, 10 ) != 'wp-content' ) {
			$extra_dirs .= "RewriteRule ^{$subdir_match}({$wp_content}.*) {$rewrite_base}{$subdir_replacement_12} [L]" . "\n";
		}

		// Check another dir
		foreach ( array( $uploads, $plugins, $themes ) as $dir ) {
			if ( ( empty( $dirs ) and stristr( $dir, $wp_content ) === false ) || ( ! empty( $dirs ) and substr( $dir, 0, 1 ) == "/" ) ) {
				if ( ! empty( $dirs ) ) {
					$dir = ltrim( $dir, "/" ); # Remove first Slash form dir Package
				}
				$extra_dirs .= "RewriteRule ^{$subdir_match}({$dir}.*) {$rewrite_base}{$subdir_replacement_12} [L]" . "\n";
			}
		}

		$extra_dirs = rtrim( $extra_dirs, "\n" );

		return ( $extra_dirs != '' ? "\n" . $extra_dirs : '' );
	}

	/**
	 * Change Permalink Structure
	 *
	 * @param $pkg_array
	 */
	public static function change_permalink_structure( $pkg_array ) {
		//Run Command
		$cmd = "rewrite structure " . $pkg_array['config']['permalink']['common'] . "";

		//Check Category
		if ( isset( $pkg_array['config']['permalink']['category'] ) ) {
			$cmd .= " --category-base={$pkg_array['config']['permalink']['category']}";
		}

		//Check Tag
		if ( isset( $pkg_array['config']['permalink']['tag'] ) ) {
			$cmd .= " --tag-base={$pkg_array['config']['permalink']['tag']}";
		}

		//Run Command
		CLI::run_command( $cmd, array( 'exit_error' => false ) );
	}

	/**
	 * Create Rewrite file (.htaccess or web.config)
	 *
	 * @param array $pkg
	 */
	public static function run_permalink_file( $pkg = array() ) {

		// Check Custom dir
		$custom_dir = '';
		if ( isset( $pkg['dir'] ) and ! empty( $pkg['dir'] ) ) {
			foreach ( array( "wp-content", "plugins", "themes", "uploads" ) as $dir ) {
				if ( isset( $pkg['dir'][ $dir ] ) ) {
					$custom_dir .= ' --' . str_replace( "-", "_", $dir ) . '=' . $pkg['dir'][ $dir ] . ' ';
				}
			}
			$custom_dir = rtrim( $custom_dir );
		}

		//Run Command
		CLI::run_command( "pack htaccess{$custom_dir}", array( 'exit_error' => false ) );
	}

}