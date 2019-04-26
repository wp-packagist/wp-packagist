<?php

namespace WP_CLI_PACKAGIST\Utility;

class Git {

	/**
	 * Clone git Help User
	 */
	public static function help_clone_command() {
		$items = array(
			array(
				'type'        => 'Plugin',
				'command'     => 'wp app clone:plugin https://github../repo.git',
				'description' => 'Clone Repository in Plugin dir',
			),
			array(
				'type'        => 'Theme',
				'command'     => 'wp app clone:theme https://github../repo.git',
				'description' => 'Clone Repository in Themes dir',
			),
		);
		\WP_CLI\Utils\format_items( 'table', $items, array( 'type', 'command', 'description' ) );
	}

	/**
	 * Run Git Command in Workspace Directory
	 *
	 * @param $path
	 * @param $args
	 */
	public static function run_git( $path, $args ) {

		//Get Composer Path To run
		$git_path = FileSystem::normalize_path( $path );

		//Create Git Command
		$command = "git -C " . $git_path;
		foreach ( $args as $arg ) {
			$command .= " " . $arg;
		}

		CLI::exec( $command );
	}

	/**
	 * Check Git command is exist
	 */
	public static function is_exist_git() {
		if ( CLI::command_exists( "git" ) === false ) {
			CLI::error( "Git Control Version is not active in your system, read more : https://git-scm.com/downloads" );
			return;
		}
	}

}