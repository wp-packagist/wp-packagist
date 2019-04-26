<?php

namespace WP_CLI_PACKAGIST\Command;

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Utility\CLI;
use WP_CLI_PACKAGIST\Utility\FileSystem;
use WP_CLI_PACKAGIST\Utility\PHP;

class Curl {
	/**
	 * Download Url
	 *
	 * @var string
	 */
	public $url;

	/**
	 * Save to
	 *
	 * @var string
	 */
	public $where;

	/**
	 * New file name
	 *
	 * @var string
	 */
	public $filename;

	/**
	 * PHP Current Working directory
	 *
	 * @var string
	 */
	public $cwd;

	/**
	 * Downloads dir
	 *
	 * @var string
	 */
	public $downloads_dir = null;

	/**
	 * Start time Download
	 *
	 * @var int
	 */
	public $start_time, $time;

	/**
	 * Download constructor.
	 *
	 * @param $url
	 * @param $where
	 * @param $new_name
	 * @throws \WP_CLI\ExitException
	 */
	public function __construct( $url, $where = '', $new_name = '' ) {

		# Check Active Curl
		if ( ! function_exists( 'curl_init' ) ) {
			CLI::error( CLI::_e( 'curl', 'er_enabled' ) );
		}

		# Set Variable
		$this->url      = trim( $url );
		$location_path  = pathinfo( $where );
		$this->where    = FileSystem::normalize_path( isset( $location_path['extension'] ) ? str_replace( $location_path['filename'] . "." . $location_path['extension'], "", $where ) : $where );
		$this->filename = $new_name;
		$this->cwd      = PHP::getcwd();
		$downloads_dir  = FileSystem::path_join( $this->cwd, Package::get_config( 'downloads_dir' ) );
		if ( FileSystem::folder_exist( $downloads_dir ) ) {
			$this->downloads_dir = $downloads_dir;
		}
		$this->time       = new \DateTime();
		$this->start_time = $this->time->getTimestamp();

		//prepare File Name
		if ( empty( $this->filename ) ) {
			$this->filename = basename( $this->url );
		}

		//Check Path
		if ( ! empty( $this->where ) and $this->where != "/" ) {
			if ( FileSystem::folder_exist( FileSystem::path_join( $this->cwd, $this->where ) ) === false ) {
				mkdir( FileSystem::path_join( $this->cwd, $this->where ), 0777, true );
			}
		}
	}

	/**
	 * Download File
	 *
	 * @param array $options
	 * @return bool|string
	 * @throws \WP_CLI\ExitException
	 */
	public function download( $options = array() ) {

		//Get target Path
		$target_path = FileSystem::path_join( FileSystem::path_join( $this->cwd, $this->where ), $this->filename );

		//Check Exist file in Same Path
		if ( file_exists( $target_path ) and ! isset( $options['force'] ) ) {
			CLI::error( "the `" . rtrim( $this->where, "/" ) . "/" . $this->filename . "` file is exist now." );
		}

		//Start tmp file
		$targetFile = fopen( $target_path, 'w' );

		//Curl Start
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $this->url ); //Add Url to Start Download
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, false ); //For Save To file
		curl_setopt( $curl, CURLOPT_FAILONERROR, true ); //For Echo http Error in curl
		curl_setopt( $curl, CURLOPT_TIMEOUT, 0 ); //Disable Limit Download File
		curl_setopt( $curl, CURLOPT_BINARYTRANSFER, true );
		curl_setopt( $curl, CURLOPT_HEADER, 0 );
		curl_setopt( $curl, CURLOPT_FILE, $targetFile );
		curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 0 );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 0 );
		//Show Progress
		if ( ! isset( $options['no-progress'] ) ) {
			curl_setopt( $curl, CURLOPT_NOPROGRESS, false );
			curl_setopt( $curl, CURLOPT_PROGRESSFUNCTION, array( $this, 'download_progress' ) );
		}
		//Add User Agent
		if ( isset( $options['user-agent'] ) ) {
			curl_setopt( $curl, CURLOPT_USERAGENT, Package::get_config( 'curl', 'user_agent' ) );
		}
		curl_exec( $curl );
		if ( curl_error( $curl ) ) {
			FileSystem::remove_file( $target_path );
			$error_msg = curl_error( $curl );
		}
		curl_close( $curl );

		//Check is Empty Directory Removed
		if ( $this->downloads_dir != null ) {
			$get_list = FileSystem::get_dir_contents( $this->downloads_dir );
			if ( count( $get_list ) < 1 ) {
				FileSystem::remove_dir( $this->downloads_dir, true );
			}
		}

		//Check If log for cli
		if ( ! isset( $options['return'] ) ) {
			CLI::br();
			if ( isset( $error_msg ) ) {
				CLI::error( "downloading file is filed. `$error_msg`" );
			} else {
				CLI::success( "Completed download file." );
			}
		} else {
			if ( isset( $error_msg ) ) {
				return false;
			} else {
				return $target_path;
			}
		}

	}

	/**
	 * Curl Progress Show
	 *
	 * @param $resource
	 * @param $download_size
	 * @param $downloaded
	 * @param $upload_size
	 * @param $uploaded
	 * @throws \Exception
	 */
	public function download_progress( $resource, $download_size, $downloaded, $upload_size, $uploaded ) {
		if ( $download_size > 1000000 && ( $this->time < new \DateTime() ) ) {
			$p             = round( ( $downloaded / $download_size ) * 100, 2 );
			$download_time = CLI::process_time( $this->start_time );
			echo "File Size: " . FileSystem::size_format( $download_size ) . ",   Downloaded: " . FileSystem::size_format( $downloaded, 2 ) . ",   Progress: $p%,   Time: " . $download_time . "\r";
			$this->time->modify( "+1 second" );
		}
	}

}