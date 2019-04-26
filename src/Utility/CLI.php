<?php

namespace WP_CLI_PACKAGIST\Utility;

use WP_CLI;
use WP_CLI_PACKAGIST\Package\Package;

class CLI {
	/**
	 * Colorize Text
	 *
	 * @see https://make.wordpress.org/cli/handbook/internal-api/wp-cli-colorize/
	 * @param $text
	 * @param $color
	 * @return string
	 */
	public static function color( $text, $color ) {
		return \WP_CLI::colorize( "%{$color}$text%n" );
	}

	/**
	 * Get log Text
	 *
	 * @param $section
	 * @param $key
	 * @param array $replace
	 * @return string
	 */
	public static function _e( $section, $key, $replace = array() ) {

		// Get Config
		$config = Package::get_config( $section );

		//Check Exist Text Log
		$log = null;
		if ( isset( $config['log'][ $key ] ) ) {
			$log = $config['log'][ $key ];
		}

		//Check replace
		if ( ! is_null( $log ) and ! empty( $replace ) ) {
			$log = str_ireplace( array_keys( $replace ), array_values( $replace ), $log );
		}

		return $log;
	}

	/**
	 * Please Wait ...
	 *
	 */ //TODO remove At last
	public static function PleaseWait() {
		\WP_CLI::log( "\n" . self::color( "Please Wait ...", "Y" ) );
	}

	/**
	 * please wait ...
	 *
	 * @param bool $new_line
	 * @param string $color
	 */
	public static function pl_wait_start( $new_line = false, $color = 'B' ) {

		# Create Global Pl Constant
		if ( ! defined( 'WP_CLI_PLEASE_WAIT_LOG' ) ) {
			define( 'WP_CLI_PLEASE_WAIT_LOG', true );
		}

		# Check in new Line
		if ( $new_line ) {
			echo "\n";
		}

		# Show in php Cli
		echo self::color( "Please wait ...", $color ) . "\r";
	}

	/**
	 * Remove please wait ..
	 */
	public static function pl_wait_end() {
		self::remove_space_character( 50 );
	}

	/**
	 * Remove Space Character
	 *
	 * @param int $num
	 */
	public static function remove_space_character( $num = PHP_INT_MAX ) {
		echo str_repeat( " ", $num ) . "\r";
	}

	/**
	 * <br> in Text
	 *
	 * @param int $num
	 */
	public static function br( $num = 1 ) {
		echo str_repeat( "\n", $num );
	}

	/**
	 * Log Command
	 *
	 * @param $text
	 * @param bool $br
	 */
	public static function log( $text, $br = false ) {
		\WP_CLI::log( ( $br === true ? "\n" : "" ) . $text );
	}

	/**
	 * Line Command
	 *
	 * @param $text
	 * @param bool $br
	 */
	public static function line( $text, $br = false ) {
		\WP_CLI::line( ( $br === true ? "\n" : "" ) . $text );
	}

	/**
	 * Error Command
	 *
	 * @param $text
	 * @param bool $exit
	 * @throws WP_CLI\ExitException
	 */
	public static function error( $text = null, $exit = true ) {
		\WP_CLI::error( $text, $exit );
	}

	/**
	 * Success Command
	 *
	 * @param $text
	 * @param bool $br
	 */
	public static function success( $text, $br = false ) {
		\WP_CLI::success( ( $br === true ? "\n" : "" ) . $text );
	}

	/**
	 * Confirm Command
	 *
	 * @param $text
	 */
	public static function confirm( $text ) {
		\WP_CLI::confirm( $text );
	}

	/**
	 * Warning Text
	 *
	 * @param $text
	 */
	public static function warning( $text = '' ) {
		self::line( self::color( "Warning: ", "Y" ) . $text );
	}

	/**
	 * Run wp-cli command
	 *
	 * @param $command
	 * @param bool $is_export
	 */ //TODO Remove At last
	public static function run( $command, $is_export = false ) {
		$options = array(
			'return'     => true,
			'parse'      => 'json',
			'launch'     => true,
			'exit_error' => false,
		);
		if ( $is_export ) {
			\WP_CLI::runcommand( $command );
		} else {
			\WP_CLI::runcommand( $command, $options );
		}
	}

	/**
	 * Run WP-CLI Command
	 *
	 * @param $command
	 * @param array $option
	 * @see https://make.wordpress.org/cli/handbook/internal-api/wp-cli-runcommand/
	 */
	public static function run_command( $command, $option = array() ) {

		//Default Option For Command
		$options = array(
			'return'     => true,
			'parse'      => 'json',
			'launch'     => true,
			'exit_error' => true, //Exit After Error
		);

		//Check Options is exist
		if ( ! empty( $option ) ) {
			$options = PHP::parse_args( $option, $options );
		}

		//Run command
		\WP_CLI::runcommand( $command, $options );
	}

	/**
	 * Get Return array data from run command
	 *
	 * @param $command
	 * @param bool $exist_error
	 * @return mixed
	 */
	public static function return_command( $command, $exist_error = false ) {
		//Setup run command Option
		$options = array( 'return' => true, 'parse' => 'json', 'launch' => false, 'exit_error' => $exist_error );

		//run command
		$run = \WP_CLI::runcommand( $command, $options );

		//Return Data
		return $run;
	}

	/**
	 * eval run WordPress Database (wpdb) query
	 *
	 * @param $sql
	 * @param array $options
	 */
	public static function wpdb_query( $sql, $options = array() ) {
		self::run_command( 'eval "global $wpdb; $wpdb->query(\"' . $sql . '\");"', $options );
	}

	/**
	 * Show Url in Browser
	 *
	 * @param $url
	 * @throws WP_CLI\ExitException
	 */
	public static function Browser( $url ) {

		//Check User Platform
		if ( preg_match( '/^darwin/i', PHP_OS ) ) {
			$cmd = 'open';
		} elseif ( preg_match( '/^win/i', PHP_OS ) ) {
			$cmd = 'start';
		} elseif ( preg_match( '/^linux/i', PHP_OS ) ) {
			$cmd = 'xdg-open';
		}

		/**
		 * escape Url sanitize
		 * @see https://ss64.com/nt/syntax-esc.html
		 */
		$sanitize_web_url = filter_var( $url, FILTER_SANITIZE_URL );
		if ( $sanitize_web_url !== false ) {
			$url = str_replace( "&", "^&", $sanitize_web_url );
		}

		//Run
		if ( isset( $cmd ) ) {
			$command = $cmd . " {$url}";
			self::exec( $command );
		}
	}

	/**
	 * Run composer Command in Workspace Directory
	 *
	 * @param $path
	 * @param $args
	 * @throws WP_CLI\ExitException
	 */
	public static function run_composer( $path, $args ) {

		//Get Composer Path To run
		$composer_path = FileSystem::normalize_path( $path );

		//Create Composer Command
		$command = "composer";
		foreach ( $args as $arg ) {
			$command .= " " . $arg;
		}
		$command .= " -d " . $composer_path;

		self::exec( $command );
	}

	/**
	 * Run System Command
	 *
	 * @param $cmd
	 * @throws WP_CLI\ExitException
	 */
	public static function exec( $cmd ) {
		if ( function_exists( 'system' ) ) {
			system( $cmd );
		} elseif ( function_exists( 'exec' ) ) {
			exec( $cmd );
		} else {
			self::error( "`system` php function does not support in your server." );
		}
	}

	/**
	 * Check Exist Command in Wp-cli
	 *
	 * @param $cmd | command
	 * @return array
	 */
	public static function exist_wp_cli_command( $cmd ) {

		//Remove White Space Between parameter
		$cmd = trim( PHP::remove_whitespace_word( $cmd ) );

		//Remove Default Prefix
		$ext     = explode( " ", $cmd );
		$command = array();
		if ( in_array( strtolower( $ext[0] ), Package::get_config( 'command_prefix' ) ) ) {
			//Remove First Parameter
			unset( $ext[0] );

			//Refresh Keys
			foreach ( $ext as $com ) {
				$command[] = $com;
			}
		} else {
			$command = $ext;
		}

		return ( is_array( \WP_CLI::get_runner()->find_command_to_run( $command ) ) ? array( 'status' => true, 'cmd' => implode( " ", $command ) ) : array( 'status' => false ) );
	}

	/**
	 * Determines if a command exists on the current environment
	 *
	 * @param string|array $command The command to check e.g : composer or npm
	 * @return bool True if the command has been found ; otherwise, false.
	 */
	static public function command_exists( $command ) {

		//Get First Parameter
		$command = explode( " ", $command );
		$command = PHP::remove_whitespace_word( trim( $command[0] ) );

		//Check PHP_OS
		$whereIsCommand = ( PHP_OS == 'WINNT' ) ? 'where' : 'which';

		//Process
		$process = proc_open(
			"$whereIsCommand $command",
			array(
				0 => array( "pipe", "r" ), //STDIN
				1 => array( "pipe", "w" ), //STDOUT
				2 => array( "pipe", "w" ), //STDERR
			),
			$pipes
		);
		if ( $process !== false ) {
			$stdout = stream_get_contents( $pipes[1] );
			$stderr = stream_get_contents( $pipes[2] );
			fclose( $pipes[1] );
			fclose( $pipes[2] );
			proc_close( $process );

			return $stdout != '';
		}

		return false;
	}

	/**
	 * Http Request
	 *
	 * @param $url
	 * @param string $type
	 * @param array $headers
	 * @param int $timeout
	 * @return array|string
	 */
	public static function http_request( $url, $type = 'GET', $timeout = 300, $headers = array() ) {

		# Set Header Request
		if ( empty( $headers ) ) {
			$headers = array( 'Accept' => 'application/json' );
		}

		# Response
		try {
			$response = \WP_CLI\Utils\http_request( $type, $url, null, $headers, array( 'timeout' => $timeout, 'halt_on_error' => false ) );
			if ( 200 === $response->status_code ) {
				return $response->body;
			}
		} catch ( \RuntimeException $error_msg ) {
			return false;
		}

		return false;
	}

	/**
	 * Create Table View
	 *
	 * @param array $array
	 * @param boolean|array $title
	 * @param bool $id
	 */
	public static function create_table( $array = array(), $title = true, $id = false ) {
		$list = self::formatter( $array, $title, $id );
		\WP_CLI\Utils\format_items( 'table', $list['list'], $list['topic'] );
	}

	/**
	 * Calculate Time Process
	 *
	 * @param $datetime
	 * @return string
	 * @throws \Exception
	 */
	public static function process_time( $datetime ) {
		if ( class_exists( '\DateTime' ) ) {
			$now = new \DateTime;
			if ( is_numeric( $datetime ) ) {
				$datetime = '@' . $datetime;
			}
			$ago  = new \DateTime( $datetime );
			$diff = $now->diff( $ago );

			$diff->w = floor( $diff->d / 7 );
			$diff->d -= $diff->w * 7;

			$string = array(
				'y' => 'year',
				'm' => 'month',
				'w' => 'week',
				'd' => 'day',
				'h' => 'h',
				'i' => 'm',
				's' => 's',
			);
			foreach ( $string as $k => &$v ) {
				if ( $diff->$k ) {
					$v = $diff->$k . '' . $v . ( $diff->$k > 1 ? '' : '' );
				} else {
					unset( $string[ $k ] );
				}
			}
			return implode( ', ', $string );
		} else {
			return '';
		}
	}

	/**
	 * Wp-Cli Formatter
	 *
	 * @see https://make.wordpress.org/cli/handbook/internal-api/wp-cli-utils-format-items/
	 * @param array $array
	 * @param boolean|array $title
	 * @param bool $id
	 * @return array
	 */
	public static function formatter( $array = array(), $title = true, $id = false ) {

		//Create Title List
		if ( is_array( $title ) ) {
			$topic = $title;
		} else {
			$topic = array_keys( $array[0] );
		}

		//Check if ID Col is exist
		if ( $id != false ) {
			for ( $i = 0; $i < count( $array ); $i ++ ) {
				$array[ $i ][ $id ] = $i + 1;
			}
			array_unshift( $topic, $id );
		}

		return array(
			"list"  => $array,
			"topic" => $topic,
		);
	}

	/**
	 * Show Formatter item
	 *
	 * @param string $format
	 * @param array $array
	 * @param bool $title
	 * @param bool $id
	 */
	public static function format_items( $format = 'table', $array = array(), $title = true, $id = false ) {
		$list = self::formatter( $array, $title, $id );
		\WP_CLI\Utils\format_items( $format, $list['list'], $list['topic'] );
	}

	/**
	 * Force prompt Command
	 *
	 * @param $command
	 * @param $assoc_args
	 */
	public static function force_prompt( $command, $assoc_args ) {
		if ( ! isset ( $assoc_args['prompt'] ) and count( $assoc_args ) == 0 ) {
			self::run( $command, true );
			exit;
		}
	}

	/**
	 * Get Flag Value
	 *
	 * @param array $assoc_args
	 * @param $key
	 * @param string $default
	 * @return string
	 * @see https://make.wordpress.org/cli/handbook/internal-api/wp-cli-utils-get-flag-value/
	 */
	public static function get_flag_value( $assoc_args = array(), $key, $default = '' ) {
		return \WP_CLI\Utils\get_flag_value( $assoc_args, $key, $default );
	}

	/**
	 * get before command which run in WP-CLI PACKAGIST
	 *
	 * @return array
	 */
	public static function get_command_log() {

		//Command log file name
		$file = Package::get_config( 'command_log' );
		if ( file_exists( $file ) ) {

			//Check time age cache [ 1 minute ]
			if ( time() - filemtime( $file ) >= 120 ) {
				self::remove_command_log();
			} else {
				//get json parse
				$json = FileSystem::read_json_file( $file );
				if ( $json != false ) {
					return $json;
				}
			}
		}

		return array();
	}

	/**
	 * Save last run command
	 *
	 * @param $command
	 * @param $args
	 * @param $assoc_args
	 */
	public static function save_last_command( $command, $args, $assoc_args ) {

		//Command log file name
		$file = Package::get_config( 'command_log' );

		//Get now Command
		$now = array(
			'command'    => $command,
			'args'       => $args,
			'assoc_args' => $assoc_args
		);

		//Add new Command to Log
		FileSystem::create_json_file( $file, $now );
	}

	/**
	 * Complete remove command log
	 */
	public static function remove_command_log() {
		//Command log file name
		$file = Package::get_config( 'command_log' );
		if ( file_exists( $file ) ) {
			FileSystem::remove_file( $file );
		}
	}

	/**
	 * Get WP-CLI home path dir
	 *
	 * @param string $path
	 * @return string
	 */
	public static function get_home_path( $path = '' ) {
		$home_path = rtrim( \WP_CLI\Utils\get_home_dir(), "/" ) . "/.wp-cli/";
		return FileSystem::path_join( $home_path, $path );
	}

	/**
	 * Get WP-CLI Cache dir
	 *
	 * @param string $path
	 * @return string
	 */
	public static function get_cache_dir( $path = '' ) {
		if ( getenv( 'WP_CLI_CACHE_DIR' ) ) {
			$cache = getenv( 'WP_CLI_CACHE_DIR' );
		} else {
			$cache = FileSystem::path_join( self::get_home_path(), "cache/" );
		}

		return FileSystem::path_join( $cache, $path );
	}

	/**
	 * Check exist File Cache
	 *
	 * @param $file_path
	 * @return bool|mixed|string|string[]|null
	 * @example ("/core/wordpress-5.1.1-en_US.tar.gz")
	 */
	public static function exist_cache_file( $file_path ) {
		$path = self::get_cache_dir( $file_path );
		if ( file_exists( $path ) ) {
			return FileSystem::normalize_path( $path );
		}

		return false;
	}

	/**
	 * Search and Replace in WordPress Table.
	 * We can not to use `search-replace` command because this command is Force echo table List in Command Line.
	 *
	 * @param $old
	 * @param $new
	 * @param bool $table | wp_options
	 */
	public static function search_replace_db( $old, $new, $table = false ) {

		# Load Search-Replace Class
		$SRDB = new SRDB();

		# Get List All WordPress Table
		if ( ! $table ) {
			$table = $SRDB::get_tables();
		}

		# Search Replace
		foreach ( $table as $tbl ) {
			$args = array(
				'case_insensitive' => 'off',
				'replace_guids'    => 'off',
				'dry_run'          => 'off',
				'search_for'       => $old,
				'replace_with'     => $new,
				'completed_pages'  => 0,
			);
			$SRDB->srdb( $tbl, $args );
		}
	}

	/**
	 * Check Run in Windows
	 *
	 * @return bool
	 */
	public static function is_windows() {
		if ( function_exists( 'is_windows' ) ) {
			return is_windows();
		} else {
			return strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN';
		}
	}

	/**
	 * Run File Editor
	 *
	 * @param $file_path
	 * @param bool $editor
	 * @throws WP_CLI\ExitException
	 */
	public static function lunch_editor( $file_path, $editor = false ) {

		# Windows Editor List
		$win_editor = array(
			'notepad++' => 'start notepad++',
			'atom'      => 'atom',
			'vscode'    => 'code',
		);

		# Check Custom editor in Windows
		if ( self::is_windows() and $editor != false ) {

			# Check Accept Value
			if ( ! in_array( $editor, array_keys( $win_editor ) ) ) {
				CLI::error( "Editor's name is not valid" );
			} else {

				# Check installed Current Editor
				if ( self::command_exists( $win_editor[ $editor ] ) === false ) {
					self::error( CLI::_e( 'package', 'er_register_cmd', array( "[key]" => $win_editor[ $editor ], "[where]" => "internal or external" ) ) );
				} else {
					self::exec( $win_editor[ $editor ] . ' "' . $file_path . '"' );
				}
			}

		} else {

			# Get File content
			$contents = file_get_contents( $file_path );

			# Lunch with WP-CLI
			$r = \WP_CLI\Utils\launch_editor_for_input( $contents, Package::get_config( 'package', 'file' ), 'json' );
			if ( $r === false ) {
				CLI::warning( 'No changes made to ' . Package::get_config( 'package', 'file' ) );
			} else {
				file_put_contents( $file_path, $r );
			}
		}
	}

}