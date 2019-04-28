<?php

namespace WP_CLI_PACKAGIST\Utility;

use WP_CLI_PACKAGIST\Package\Package;

class FTP {
	/**
	 * Ftp Client
	 */
	public $ftp;

	/**
	 * FTP Host
	 */
	public $host;

	/**
	 * Ftp Login Username
	 */
	public $login;

	/**
	 * FTP Password
	 */
	public $password;

	/**
	 * Ftp is_ssl connect
	 */
	public $is_ssl = 0;

	/**
	 * Ftp Post
	 */
	public $port = 21;

	/**
	 * Ftp is_passive
	 */
	public $passive = 1;

	/**
	 * Ftp TimeOut
	 */
	public $timeout = 300;

	/**
	 * Ftp Domain Connect
	 */
	public $domain;

	/**
	 * Ftp Wordpress Directory
	 */
	public $wp_directory;

	/**
	 * Ftp Wordpress wp-content Directory
	 */
	public $wp_content;

	/**
	 * Ftp Wordpress mu-plugins Directory
	 */
	public $mu_plugins;

	/**
	 * Ftp Wordpress Uploads Directory
	 */
	public $wp_uploads;

	/**
	 * Ftp Wordpress Theme Directory
	 */
	public $wp_themes;

	/**
	 * Ftp Wordpress Plugins Directory
	 */
	public $wp_plugins;

	/**
	 * Ftp Config file
	 */
	public $config_file;

	/**
	 * Template Basic Path
	 */
	public $template_path;

	/**
	 * FTP constructor.
	 * @throws \Exception
	 */
	public function __construct() {

		//Create Object Ftp
		$this->ftp = new FtpClient();

		//disable Error Reporting
		error_reporting( 0 );

		//Set Config File
		$this->config_file   = Package::get_config( 'ftp_opt_file_name' );
		$this->template_path = FileSystem::path_join( WP_CLI_PACKAGIST_PATH, 'templates/' . Package::get_config( 'plugin_file_name' ) );
		@FileSystem::remove_file( $this->template_path );

		//Check Exist Remote FTP Or Set Variable
		$this->check_ftp_opt();
	}

	/**
	 * Connect With FTP
	 *
	 * @param array $args
	 * @return bool|FtpClient
	 */
	public function connect( $args = array() ) {

		//Set Default Data
		$defaults = array(
			'host'     => $this->host,
			'login'    => $this->login,
			'password' => $this->password,
			'is_ssl'   => $this->is_ssl,
			'passive'  => $this->passive,
			'port'     => $this->port
		);
		$arg      = PHP::parse_args( $args, $defaults );

		//Connect To FTP
		try {
			$this->ftp->connect( $arg['host'], $arg['is_ssl'], $arg['port'] );
			$this->ftp->login( $arg['login'], $arg['password'] );
			$this->ftp->passive( $arg['passive'] );

			return $this->ftp;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Require Option Arg
	 */
	public static function require_ftp_item() {
		return array( 'host', 'login', 'password', 'is_ssl', 'passive', 'port', 'domain', 'wp_directory', 'wp_content', 'mu_plugins', 'wp_uploads', 'wp_themes', 'wp_plugins' );
	}

	/**
	 * Check Ftp Option
	 */
	public function check_ftp_opt() {

		//Check FTP Remote Option
		if ( ! file_exists( $this->config_file ) ) {
			return false;
		}
		$string = file_get_contents( $this->config_file );
		$opt    = @json_decode( $string, true );
		if ( $opt === null && json_last_error() !== JSON_ERROR_NONE ) {
			return false;
		}

		//Check Require Arg
		foreach ( self::require_ftp_item() as $key ) {

			//If Not exist
			if ( ! array_key_exists( $key, $opt ) ) {
				return false;
			}

			//Set Variable
			$this->{$key} = $opt[ $key ];
		}

		return $opt;
	}

	/**
	 * Save Remote Config File
	 *
	 * @param array $args
	 * @return bool
	 */
	public function save_config( $args = array() ) {

		//Prepare Data
		$data = array();
		foreach ( self::require_ftp_item() as $key ) {
			$data[ $key ] = ( array_key_exists( $key, $args ) ? $args[ $key ] : $this->{$key} );
		}

		//File put content
		if ( FileSystem::create_json_file( $this->config_file, $data ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Is Wordpress Directory
	 * This function Check exist wp-config.php in Same Dir
	 *
	 * @param bool $ftp_path
	 * @return bool
	 */
	public function is_wp_directory( $ftp_path = false ) {
		$path = $this->wp_directory;
		if ( $ftp_path != false ) {
			$path = $ftp_path;
		}

		//Get All List File in This Path
		try {
			$list = $this->ftp->listDirectory( $path );
			if ( in_array( "wp-config.php", $list ) ) {
				return $path;
			}
		} catch ( \Exception $e ) {
			return false;
		}

		return false;
	}

	/**
	 * Check Domain Url
	 *
	 * @param string $domain
	 * @param string $base_path
	 * @return bool|mixed
	 */
	public function check_ftp_domain( $domain = '', $base_path = '' ) {

		//Upload Check connect To Ftp
		$url     = ( empty( $domain ) === true ? $this->domain : $domain );
		$wp_path = ( empty( $base_path ) === true ? $this->wp_directory : $base_path );

		//Prepare php file
		$mustache = FileSystem::load_mustache( WP_CLI_PACKAGIST_TEMPLATE_PATH );
		$get_key  = strtolower( WP_CLI_Util::random_key( 80, false ) );
		$data     = array(
			'GET_KEY'   => $get_key,
			'file_name' => Package::get_config( 'plugin_file_name' ),
		);
		$text     = $mustache->render( 'remote/connect', $data );
		FileSystem::file_put_content( $this->template_path, $text );

		//Upload To Server
		try {
			$remote_file_path = FileSystem::path_join( $wp_path, Package::get_config( 'plugin_file_name' ) );
			$this->ftp->put( $remote_file_path, $this->template_path );
			@FileSystem::remove_file( $this->template_path );

			//Connect To Host
			$request = CLI::http_request( rtrim( $url, "/" ) . "/" . Package::get_config( 'plugin_file_name' ) . '?wp_cli_ftp_token=' . $get_key );
			if ( $request === false ) {
				@$this->ftp->delete( $remote_file_path );
				return false;
			} else {
				$json = json_decode( $request, true );
				if ( $json['status'] == 1 ) {
					return $json;
				} else {
					return false;
				}
			}
		} catch ( \Exception $e ) {
			@FileSystem::remove_file( $this->template_path );
			return false;
		}

	}

	/**
	 * Json FTP Request
	 *
	 * @param $key
	 * @return bool|mixed
	 * @throws \Exception
	 */
	public function json_request( $key ) {
		$request = CLI::http_request( rtrim( $this->domain, "/" ) . "/" . Package::get_config( 'plugin_file_name' ) . '?wp_cli_ftp_token=' . $key );
		if ( $request === false ) {
			@$this->ftp->delete( FileSystem::path_join( $this->wp_directory, Package::get_config( 'plugin_file_name' ) ) );
			return false;
		} else {
			$json = json_decode( $request, true );
			return $json;
		}
	}

	/**
	 * Prepare Mu Plugin and Upload To Ftp Host
	 *
	 * @param $template
	 * @param string $remote_path
	 * @param array $args
	 * @return bool|string
	 * @throws \Exception
	 */
	public function prepare_plugin( $template, $remote_path = '/', $args = array() ) {

		//Load Mustache
		$mustache = FileSystem::load_mustache( WP_CLI_PACKAGIST_TEMPLATE_PATH );

		//Create GET Request Key
		$get_key = strtolower( WP_CLI_Util::random_key( 80, false ) );

		//Get Render Data
		$data = array(
			'GET_KEY'   => $get_key,
			'file_name' => Package::get_config( 'plugin_file_name' )
		);

		//Add New code in Files
		$data['code'] = $mustache->render( 'remote/' . $template, array_merge( $args, $data ) );

		//Check do_action function
		if ( array_key_exists( 'do_action', $args ) ) {
			if ( is_string( $args['do_action'] ) ) {
				$data['do_action'] = $mustache->render( 'remote/' . $args['do_action'], $args );
			} else {
				$do_action = '';
				foreach ( $args['do_action'] as $actions ) {
					$do_action .= $mustache->render( 'remote/' . $actions, $args );
				}
				$data['do_action'] = $do_action;
			}
		}

		//Render Text
		$text = $mustache->render( 'remote/base', $data );

		//Create File
		FileSystem::file_put_content( $this->template_path, $text );

		//Upload File
		try {
			if ( $remote_path != $this->wp_directory ) {
				$this->ftp->changeDirectory( $remote_path );
			}
			$this->ftp->put( FileSystem::path_join( $remote_path, Package::get_config( 'plugin_file_name' ) ), $this->template_path );
			@FileSystem::remove_file( $this->template_path );
			return $get_key;
		} catch ( \Exception $e ) {
			@FileSystem::remove_file( $this->template_path );
			return false;
		}
	}

	/**
	 * FTP Close
	 */
	public function close() {
		try {
			$this->ftp->close();
		} catch ( \Exception $e ) {
			return false;
		}

		return false;
	}


}