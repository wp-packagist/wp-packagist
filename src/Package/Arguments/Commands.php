<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Utility\CLI;
use WP_CLI_PACKAGIST\Utility\FileSystem;
use WP_CLI_PACKAGIST\Utility\PHP;
use WP_CLI_PACKAGIST\Package\Utility\install;

class Commands {
	/**
	 * Run Commands Parameter in WordPress Package
	 *
	 * @param $pkg_commands
	 */
	public static function run_commands( $pkg_commands ) {

		# Get Base Dir
		$cwd = PHP::getcwd();

		# Start Loop commands
		foreach ( $pkg_commands as $command ) {

			//Check is WP-CLI or Global Command
			if ( isset( $command['where'] ) and $command['where'] == "wp-cli" ) {
				# Show Log
				install::add_detail_log( CLI::_e( 'package', 'run_cmd', array( "[cmd]" => self::show_command_log( $command['command'] ), "[more]" => "" ) ) );

				# Run WP-CLI
				CLI::run_command( $command['command'], array( 'exit_error' => false ) );
			} else {
				# Run Global

				//Check Exist Dir
				$sanitize_dir  = PHP::backslash_to_slash( "/" . ltrim( $command['where'], "/" ) );
				$complete_path = FileSystem::path_join( $cwd, $sanitize_dir );
				if ( is_dir( $complete_path ) and is_dir( $complete_path ) ) {
					# Show log
					install::add_detail_log( CLI::_e( 'package', 'run_cmd', array( "[cmd]" => self::show_command_log( $command['command'] ), "[more]" => " in '" . $command['where'] . "' path" ) ) );

					# Run global command
					chdir( $complete_path );
					CLI::exec( $command['command'] );
					chdir( $cwd );
				} else {

					# Show Log Error directory
					install::add_detail_log( CLI::_e( 'package', 'er_find_dir_cmd', array( "[dir]" => $command['where'], "[cmd]" => self::show_command_log( $command['command'] ) ) ) );
				}
			}
		}

	}

	/**
	 * Sanitize Command name in show log
	 *
	 * @param $cmd
	 * @return mixed
	 */
	public static function show_command_log( $cmd ) {
		$exp = explode( " ", $cmd );
		if ( count( $exp ) <= 6 ) {
			return $cmd;
		} else {
			$t = "";
			for ( $i = 0; $i <= 6; $i ++ ) {
				$t = $exp[ $i ] . " ";
			}
			return $t . " ..";
		}
	}

}