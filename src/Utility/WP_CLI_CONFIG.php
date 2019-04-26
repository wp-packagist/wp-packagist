<?php

namespace WP_CLI_PACKAGIST\Utility;

/**
 * WP-CLI Global config.
 *
 * @see https://make.wordpress.org/cli/handbook/config/
 */
class WP_CLI_CONFIG {
	/**
	 * WP-CLI config file name
	 *
	 * @var string
	 */
	public $config_filename = 'config.yml';

	/**
	 * WP-CLI config file name in working directory
	 *
	 * @var array
	 */
	public $config_working_file = array( 'wp-cli.local.yml', 'wp-cli.yml' );

	/**
	 * WP-CLI config file Path
	 *
	 * @defalt ~/.wp-cli/config.yml
	 * @var string
	 */
	public $config_path;

	/**
	 * Config constructor.
	 *
	 * @param string $type
	 * -- Type --
	 * global : ~/.wp-cli/config.yml
	 * local : Current working directory
	 *
	 */
	public function __construct( $type = 'global' ) {
		/**
		 * Set Config File path
		 */
		if ( $type == "global" ) {
			$this->config_path = $this->get_global_config_path();
		} else {
			$this->config_path = $this->get_current_directory_config_file();
		}
	}

	/**
	 * Convert array to Yaml
	 *
	 * @param $array
	 * @return string
	 */
	public static function array_to_yaml( $array ) {
		$YAML = new \Spyc();
		return $YAML->YAMLDump( $array );
	}

	/**
	 * Get Global Config Path file
	 */
	public function get_global_config_path() {
		$path = \WP_CLI::get_runner()->get_global_config_path();
		if ( empty( $path ) ) {
			$path = FileSystem::path_join( CLI::get_home_path(), $this->config_filename );
		}

		return $path;
	}

	/**
	 * Get Current directory Config File
	 * @return string
	 */
	public function get_current_directory_config_file() {
		$cwd = PHP::getcwd();
		foreach ( $this->config_working_file as $file ) {
			$path = FileSystem::path_join( $cwd, $file );
			if ( file_exists( $path ) ) {
				$file_path = $path;
			}
		}

		if ( isset( $file_path ) ) {
			return $file_path;
		} else {
			return FileSystem::path_join( $cwd, $this->config_working_file[1] );
		}
	}

	/**
	 * Get WP-CLI Global Config
	 *
	 * @param bool $arg
	 * @param bool $where
	 * @return bool
	 * @example get_config('path') | get_config(array('app', 'username'));
	 * @throws \Exception
	 */
	public static function get( $arg = false, $where = false ) {

		# Get List Config & extra-config
		$global_config = \WP_CLI::get_runner()->__get( 'config' );
		$local_config  = \WP_CLI::get_runner()->__get( 'extra_config' );
		if ( $where == "global" ) {
			$list = $global_config;
		} elseif ( $where == "local" ) {
			$list = $local_config;
		} else {
			$list = array_merge( $global_config, $local_config );
		}

		if ( ! $arg ) {
			return $list;
		} else {
			if ( is_string( $arg ) ) {
				if ( array_key_exists( $arg, $list ) ) {
					return $list[ $arg ];
				}
			} elseif ( is_array( $arg ) ) {
				$exist_key = PHP::check_exist_key( $arg, $list );
				if ( $exist_key != false ) {
					$exp = $list;
					foreach ( $arg as $key ) {
						$exp = $exp[ $key ];
					}
					return $exp;
				}
			}
		}

		throw new \Exception( "config not found." );
	}

	/**
	 * Check exist Config file
	 *
	 * @return bool
	 */
	public function exist_config_file() {
		if ( file_exists( $this->config_path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Load Config file
	 */
	public function load_config_file() {
		$config_list = array();
		if ( $this->exist_config_file() ) {
			$YAML        = new \Spyc();
			$config_list = $YAML->YAMLLoad( $this->config_path );
		}

		return $config_list;
	}

	/**
	 * Save Config File
	 *
	 * @param $array
	 * @return bool
	 */
	public function save_config_file( $array ) {
		$yaml = self::array_to_yaml( $array );
		if ( FileSystem::file_put_content( $this->config_path, $yaml ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Remove Config File
	 */
	public function remove_config_file() {
		FileSystem::remove_file( $this->config_path );
	}

}