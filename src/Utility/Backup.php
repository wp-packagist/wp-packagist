<?php

namespace WP_CLI_PACKAGIST\Utility;

use WP_CLI_PACKAGIST\Package\Arguments\Dir;
use WP_CLI_PACKAGIST\Package\Package;

class Backup {
	/**
	 * Get Base Wordpress Path
	 */
	public $base_path;

	/*
	 * Get Default Backup dir name
	 */
	public $backup_dir;

	/*
	 * Get Default Backup dir path
	 */
	public $backup_dir_path;

	/*
	 * Get Last insert ID Backup File
	 */
	public $last_insert_id = 0;

	/*
	 * Basic Backup information
	 */
	public $backup_inf;

	/**
	 * Construct BackUp class
	 */
	public function __construct() {

		//Create Backup folder if not exist
		if ( ! FileSystem::folder_exist( FileSystem::path_join( ABSPATH, Package::get_config('backup_dir') ) ) ) {
			FileSystem::create_dir( Package::get_config('backup_dir'), ABSPATH );
		}

		//Set Default folder
		$this->base_path       = ABSPATH;
		$this->backup_dir      = Package::get_config('backup_dir');
		$this->backup_dir_path = FileSystem::path_join( ABSPATH, Package::get_config('backup_dir') );

		//Set Backup info
		$this->backup_inf = array(
			'plugins'    => array(
				'var'            => 'backup_plugins_file_name',
				'dir_in_archive' => basename( Dir::get_plugins_dir() ),
				'path'           => str_replace( $this->base_path, "./", Dir::get_plugins_dir() ),
			),
			'themes'     => array(
				'var'            => 'backup_themes_file_name',
				'dir_in_archive' => basename( Dir::get_themes_dir() ),
				'path'           => str_replace( $this->base_path, "./", Dir::get_themes_dir() ),
			),
			'uploads'    => array(
				'var'            => 'backup_uploads_file_name',
				'dir_in_archive' => basename( Dir::get_uploads_dir() ),
				'path'           => str_replace( $this->base_path, "./", Dir::get_uploads_dir() ),
			),
			'wp-content' => array(
				'var'            => 'backup_content_file_name',
				'dir_in_archive' => basename( Dir::get_content_dir() ),
				'path'           => str_replace( $this->base_path, "./", Dir::get_content_dir() ),
			)
		);

		//Get Last insert ID
		$this->last_insert_id = $this->get_last_insert_id();
	}

	/**
	 * Generate Backup filename
	 *
	 * @param string $file_name
	 * @param $template
	 * @param $extension
	 * @param string $custom_folder
	 * @return string
	 */
	public function generate_filename( $file_name = '', $template, $extension, $custom_folder = '' ) {

		//Check Filename
		if ( ! empty( $file_name ) ) {

			if ( strstr( $file_name, "." ) != false ) {
				$array     = explode( ".", $file_name );
				$file_name = FileSystem::sanitize_folder_name( $array[0] );
			}
			return $file_name . '.' . $extension;
		} else {
			//Get Template Setting
			$template = Package::get_config( $template );

			//Add Template File Name
			$list_template = array(
				'[datetime]' => ( function_exists( 'current_time' ) ? current_time( "Y-m-d_H-i-s" ) : date( "Y-m-d_H-i-s" ) )
			);
			$new_name      = str_replace( array_keys( $list_template ), array_values( $list_template ), $template );

			//Add New ID
			$this->last_insert_id = $this->last_insert_id + 1;
			return '#' . $this->last_insert_id . '_' . $new_name . ( $custom_folder != "" ? '_' . $custom_folder : '' ) . '.' . $extension;
		}
	}

	/**
	 * Generate Backup Database
	 *
	 * @param $file_name
	 * @return string
	 */
	public function database( $file_name = '' ) {

		//Check Filename
		$new_name = $this->generate_filename( $file_name, 'backup_sql_file_name', "sql" );

		//Create Database backup
		$path = FileSystem::path_join( $this->backup_dir_path, $new_name );
		CLI::run( "db export " . $path, false );

		return $new_name;
	}

	/**
	 * Generate Full Backup
	 *
	 * @param $file_name
	 * @return string
	 * @throws \WP_CLI\ExitException
	 */
	public function full_backup( $file_name = '' ) {

		//Check Filename
		$new_name = $this->generate_filename( $file_name, 'backup_full_file_name', "zip" );

		//Create Full BackUp file
		FileSystem::create_zip( $this->base_path, FileSystem::path_join( $this->backup_dir_path, $new_name ), "wordpress", array( $this->backup_dir . "/", ".idea/" ) );

		return $new_name;
	}

	/**
	 * Remove Backup Folder
	 */
	public function remove() {
		$count_file = count( $this->get_array_backup_files() );
		FileSystem::remove_dir( $this->backup_dir_path, true );
		return $count_file;
	}

	/**
	 * Get file ID
	 *
	 * @param $file_name
	 * @return string
	 */
	public function get_file_id( $file_name ) {
		if ( substr( $file_name, 0, 1 ) == "#" ) {
			$file_name = explode( "_", substr( $file_name, 1 ) );
			if ( is_numeric( $file_name[0] ) ) {
				return $file_name[0];
			}
		}

		return '';
	}

	/**
	 * Get Last insert ID
	 */
	public function get_last_insert_id() {
		$list_id = array( 0 );

		//Get List files ID
		$list_backups = $this->get_array_backup_files();
		foreach ( $list_backups as $file ) {
			if ( $this->get_file_id( $file ) != "" ) {
				$list_id[] = $this->get_file_id( $file );
			}
		}

		//Sort array by ID
		rsort( $list_id );
		return $list_id[0];
	}

	/**
	 * Search Backup File
	 *
	 * @param $value | File ID or Filename
	 * @return string
	 */
	public function search_backup_file( $value ) {

		$file_name    = false;
		$list_backups = $this->get_array_backup_files();
		foreach ( $list_backups as $file ) {
			if ( is_numeric( $value ) ) {
				if ( $this->get_file_id( $file ) == $value ) {
					$file_name = $file;
				}
			} else {
				if ( strtolower( $value ) == strtolower( $file ) ) {
					$file_name = $file;
				}
			}
		}

		return $file_name;
	}

	/**
	 * Remove backup File
	 *
	 * @param $file_name
	 * @return bool
	 */
	public function remove_backup_file( $file_name ) {
		$path = FileSystem::path_join( $this->backup_dir_path, $file_name );
		if ( file_exists( $path ) ) {
			return FileSystem::remove_file( $path );
		}

		return false;
	}

	/**
	 * Get list All Backups
	 *
	 * @return array
	 */
	public function get_array_backup_files() {

		$list = array();
		foreach ( scandir( $this->backup_dir_path ) as $file ) {
			if ( in_array( $file, array( '.', '..', '.php', '.htaccess' ) ) ) {
				continue;
			}
			$info      = pathinfo( $file );
			$extension = $info['extension'];
			if ( in_array( $extension, array( 'sql', 'zip' ) ) ) {
				$list[] = $file;
			}
		}

		return $list;
	}


	/**
	 * Get List Of backup folder
	 *
	 * @param string $search
	 * @return array
	 */
	public function get_list_backup( $search = "" ) {

		//Get List Of Backups
		$list = array();
		foreach ( scandir( $this->backup_dir_path ) as $file ) {
			if ( in_array( $file, array( '.', '..', '.php', '.htaccess' ) ) ) {
				continue;
			}
			$info      = pathinfo( $file );
			$extension = $info['extension'];
			if ( in_array( $extension, array( 'sql', 'zip' ) ) ) {
				$in_array = true;
				//Check Search
				if ( $search != "" ) {
					if ( stristr( $file, $search ) === false ) {
						$in_array = false;
					}
				}
				if ( $in_array === true ) {
					$list[] = $this->get_file_info( $file, array( 'DownloadLink' ) );
				}
			}
		}

		return $list;
	}

	/**
	 * Get file info
	 *
	 * @param $file_name
	 * @param array $except
	 * @return array
	 */
	public function get_file_info( $file_name, $except = array() ) {
		$info = array(
			'File'         => basename( $file_name ),
			'DownloadLink' => FileSystem::path_join( Wordpress::get_site_url(), FileSystem::path_join( $this->backup_dir, $file_name ) ),
			'Size'         => FileSystem::size_format( FileSystem::path_join( $this->backup_dir_path, $file_name ) ),
			'CreateDate'   => date( "F d Y H:i:s", filectime( FileSystem::path_join( $this->backup_dir_path, $file_name ) ) ),
			'ID'           => ( $this->get_file_id( $file_name ) != "" ? $this->get_file_id( $file_name ) : '-' )
		);
		if ( count( $except ) > 0 ) {
			foreach ( $except as $key ) {
				if ( array_key_exists( $key, $info ) ) {
					unset( $info[ $key ] );
				}
			}
		}
		return $info;
	}

	/**
	 * Show download backup table
	 *
	 * @param $file_name
	 * @return array
	 */
	public function show_inf_file_tbl( $file_name ) {
		$file_info = array();
		$except    = array( 'CreateDate', 'File' );
		if ( $this->get_file_id( $file_name ) == "" ) {
			array_push( $except, "ID" );
		}
		$file_info[] = $this->get_file_info( $file_name, $except );

		return $file_info;
	}

	/**
	 * Create BackUp From Folder
	 *
	 * @param $type
	 * @param string $file_name
	 * @param string $custom_folder
	 * @return string
	 */
	public function backup_dir( $type, $file_name = '', $custom_folder = '' ) {

		//Define List
		$list = $this->backup_inf;

		//Check if workspace
		if ( $type == "workspace" ) {
			$workspace         = WorkSpace::get_workspace();
			$list['workspace'] = array(
				'var'            => 'backup_workspace_file_name',
				'dir_in_archive' => basename( $workspace['path'] ),
				'path'           => $workspace['path'],
			);
		}

		//Create Zip From Main folder
		if ( trim( $custom_folder ) == "" ) {

			//Check Filename
			$new_name = $this->generate_filename( $file_name, $list[ $type ]['var'], "zip" );

			//Create Backup
			FileSystem::create_zip( $list[ $type ]['path'], FileSystem::path_join( $this->backup_dir_path, $new_name ), $list[ $type ]['dir_in_archive'] );

			return $new_name;
		} else {

			//Check folder exist
			$dir_path = FileSystem::path_join( $list[ $type ]['path'], FileSystem::sanitize_folder_name( $custom_folder ) );
			if ( FileSystem::folder_exist( $dir_path ) ) {

				//Check Filename
				$new_name = $this->generate_filename( $file_name, $list[ $type ]['var'], "zip", FileSystem::sanitize_folder_name( $custom_folder ) );

				//Create Backup
				FileSystem::create_zip( $dir_path, FileSystem::path_join( $this->backup_dir_path, $new_name ), basename( $dir_path ) );

				return $new_name;
			} else {
				return false;
			}
		}
	}

	/**
	 * Run Cli Backup Command
	 *
	 * @param $type
	 * @param array $args
	 * @param array $assoc_args
	 * @throws \WP_CLI\ExitException
	 */
	public function run_backup( $type, $args = array(), $assoc_args = array() ) {

		//Create New Backup
		$start = time();

		//Check file Name
		$file_name = '';
		if ( isset( $args[0] ) ) {
			$file_name = $args[0];
		}

		//Check Custom dir
		$custom_dir = '';
		if ( isset( $assoc_args['dir'] ) ) {
			$custom_dir = $assoc_args['dir'];

			//Check folder Exist
			if ( ! FileSystem::folder_exist( FileSystem::path_join( Wordpress::{'get_' . $type . '_dir'}(), $custom_dir ) ) ) {
				CLI::error( "`$custom_dir` folder is not found." );
				exit;
			}
		}

		//Create Backup
		$this_file = $this->backup_dir( $type, $file_name, $custom_dir );
		sleep( 1 );
		if ( file_exists( FileSystem::path_join( $this->backup_dir_path, $this_file ) ) ) {
			$process_time = CLI::process_time( $start );
			$time         = "(Process time: " . $process_time . ")";
			CLI::br();
			CLI::success( "Backup File is Created." . $time );
			$file_info = $this->show_inf_file_tbl( $this_file );
			CLI::create_table( $file_info, true );
		} else {
			CLI::error( "Error Taking backup file, Please try again." );
		}

	}


}