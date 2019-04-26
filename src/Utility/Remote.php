<?php

namespace WP_CLI_PACKAGIST\Utility;

use WP_CLI_PACKAGIST\Package\Package;

/**
 * Class Remote
 */
class Remote {
	/**
	 * Wordpress FTP Class
	 */
	public $wp_ftp;

	/**
	 * FTP Client Object
	 */
	public $ftp;

	/**
	 * Get Remote config
	 */
	public $config;

	/**
	 * Custom Notice text
	 */
	public $notice;

	/**
	 * Remote constructor.
	 * @throws \Exception
	 */
	public function __construct() {

		//Load FTP Wordpress Class
		$this->wp_ftp = new FTP();

		//Check Exist FTP remote
		$config = $this->wp_ftp->check_ftp_opt();
		if ( $config === false ) {
			CLI::error( "please set a new FTP remote Server with the command `wp app remote`." );
			exit;
		}

		//Set Config
		$this->config = $config;

		//Check Connect To Server
		$connect = $this->wp_ftp->connect();
		if ( $connect === false ) {
			CLI::error( "Connected with a remote server has encountered an error.Please try again" );
			exit;
		}

		//Set Notice Text
		$this->notice = array(
			'upload_error'        => "The remote system can not upload any file.please tray again.",
			'connect_to_wp_error' => "The remote system unable to connect to your WordPress Domain.Please Check your Domain and tray again."
		);

		//Set FTP Client
		$this->ftp = $this->wp_ftp->ftp;
	}

	/**
	 * Run Command
	 *
	 * @param $command
	 * @param array $args
	 */
	public function run( $command, $args = array() ) {
		if ( method_exists( $this, $command ) ) {
			$this->{$command}( $args );
		} else {
			CLI::error( "`$command` function not found for remote" );
		}
	}


	/**
	 * Create BackUp
	 *
	 * @param array $args
	 * @throws \Exception
	 */
	public function backup( $args = array() ) {

		//Start timer
		$start = time();

		//Check if `Remove` Command
		if ( $args['where'] == "remove" ) {

			//Check confirm
			CLI::confirm( "Are you sure you want to drop the all backup Files ?" );

			//Add Please Wait
			CLI::PleaseWait();

			//Prepare Plugin and Upload to server
			$key = $this->wp_ftp->prepare_plugin( 'backup/backup-remove', $this->config['mu_plugins'], array( 'do_action' => array( 'helper/set-time-limit', 'helper/remove-folder' ), 'backup_dir' => Package::get_config('backup_dir') ) );
			if ( $key === false ) {
				CLI::error( $this->notice['upload_error'] );
				exit;
			}

			//Connect to Server
			sleep( 2 );
			if ( isset( $key ) and ! empty( $key ) ) {
				$request = $this->wp_ftp->json_request( $key );
				if ( $request === false ) {
					CLI::error( $this->notice['connect_to_wp_error'] );
					exit;
				} else {
					$process_time = CLI::process_time( $start );
					$time         = "(Process time: " . $process_time . ")";
					CLI::success( $request['text'] . ' ' . $time );
				}
			}

			exit;
		}

		//Check if `list` Command
		if ( $args['where'] == "list" ) {

			//Add Please Wait
			CLI::PleaseWait();

			//Prepare Plugin and Upload to server
			$key = $this->wp_ftp->prepare_plugin( 'backup/backup-list', $this->config['mu_plugins'], array( 'do_action' => array( 'helper/set-time-limit' ), 'backup_dir' => $this->wp_cli_packagist['backup_dir'] ) );
			if ( $key === false ) {
				CLI::error( $this->notice['upload_error'] );
				exit;
			}

			//Connect to Server
			sleep( 2 );
			if ( isset( $key ) and ! empty( $key ) ) {
				$request = $this->wp_ftp->json_request( $key );
				if ( $request === false ) {
					CLI::error( $this->notice['connect_to_wp_error'] );
					exit;
				} else {
					$process_time = CLI::process_time( $start );
					$time         = "(Process time: " . $process_time . ")";
					if ( $request['number_backup_file'] == 0 ) {
						CLI::success( 'no backup file found. ' . $time );
					} else {
						CLI::success( number_format( $request['number_backup_file'] ) . ' backup file' . ( $request['number_backup_file'] > 1 ? 's' : '' ) . ' found. ' . $time );
						CLI::create_table( $request['list'], true, false );
					}
				}
			}

			exit;
		}

		//Create Backup Folder in FTP server
		$backup_dir = FileSystem::path_join( $this->config['wp_directory'], $this->wp_cli_packagist['backup_dir'] );
		try {
			if ( $this->ftp->folder_exist( $backup_dir ) === false ) {
				$this->ftp->createDirectory( $backup_dir );
			}
		} catch ( \Exception $e ) {
			CLI::error( "The remote system can not create a backup folder.please tray again." );
		}

		//Start Log
		CLI::log( "Backup operation started. please wait ..." );

		//List Of where Define
		$where_arg = array(
			'db'   => array(
				'base-file' => 'backup/backup-db',
				'args'      => array(
					'do_action'  => array( 'helper/set-time-limit', 'helper/mysqldump' ),
					'backup_dir' => $this->wp_cli_packagist['backup_dir']
				)
			),
			'file' => array(
				'base-file' => 'backup/backup-file',
				'args'      => array(
					'do_action'  => array( 'helper/set-time-limit', 'helper/create-zip' ),
					'area'       => $args['where'],
					'backup_dir' => $this->wp_cli_packagist['backup_dir']
				)
			)
		);

		//Check Where
		if ( $args['where'] == "db" ) {
			$where = $where_arg['db'];
		} else {
			$where = $where_arg['file'];
		}

		//Prepare and Upload File For wordpress files Backup
		$key = $this->wp_ftp->prepare_plugin( $where['base-file'], $this->config['mu_plugins'], $where['args'] );
		if ( $key === false ) {
			CLI::error( $this->notice['upload_error'] );
			exit;
		}

		//Start Request To Wordpress Site
		sleep( 2 );
		if ( isset( $key ) and ! empty( $key ) ) {
			$request = $this->wp_ftp->json_request( $key );
			if ( $request === false ) {
				CLI::error( $this->notice['connect_to_wp_error'] );
				exit;
			} else {
				//Check If exist Error in php run Server
				if ( $request['status'] == 0 ) {
					if ( array_key_exists( 'error', $request ) ) {
						CLI::error( $request['error'] );
					}
					exit;
				} else {
					$process_time = CLI::process_time( $start );
					$time         = "(Process time: " . $process_time . ")";
					CLI::br();
					CLI::success( "Backup File is Created." . $time );
					CLI::create_table( array( array( 'link' => $request['link'], 'size' => $request['size'] ) ), true );
				}
			}
		}

		//Check Download File After Complete Backup
		if ( array_key_exists( 'download_after', $args ) and isset( $request ) ) {
			CLI::run( 'tools curl ' . $request['link'] . ' ' . $this->wp_cli_packagist['downloads_dir'], true );
		}

	}


	/**
	 * Login As User in Wordpress
	 *
	 * @param array $args
	 * @throws \Exception
	 */
	public function login( $args = array() ) {

		//Add Please Wait
		CLI::PleaseWait();

		//Prepare Plugin and Upload to server
		$key = $this->wp_ftp->prepare_plugin( 'user-login', $this->config['mu_plugins'],
			array(
				'do_action'      => array( 'helper/set-time-limit', 'helper/get-user-info' ),
				'backup_dir'     => $this->wp_cli_packagist['backup_dir'],
				'user_search'    => $args['user_search'],
				'wp_acl'         => $this->wp_cli_packagist['acl_opt'],
				'go_after_login' => $args['go_after_login'],
			) );
		if ( $key === false ) {
			CLI::error( $this->notice['upload_error'] );
			exit;
		}

		//Request Check Exist User in Wordpress Site
		sleep( 2 );
		if ( isset( $key ) and ! empty( $key ) ) {
			$request = $this->wp_ftp->json_request( $key );
			if ( $request === false ) {
				CLI::error( $this->notice['connect_to_wp_error'] );
				exit;
			} else {
				//Check If exist Error in php run Server
				if ( $request['status'] == 0 ) {
					if ( array_key_exists( 'error', $request ) ) {
						CLI::error( $request['error'] );
					}
					exit;
				} else {
					CLI::Browser( $request['redirect'] );
				}
			}
		}

	}
}