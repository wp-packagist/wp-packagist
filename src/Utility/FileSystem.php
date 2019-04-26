<?php

namespace WP_CLI_PACKAGIST\Utility;

/**
 * Filesystem tools for WP-CLI
 */
class FileSystem {
	/**
	 * Remove Complete Folder
	 *
	 * @param $dir
	 * @param bool $remove_folder
	 * @return bool
	 */
	public static function remove_dir( $dir, $remove_folder = false ) {
		$di = new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS );
		$ri = new \RecursiveIteratorIterator( $di, \RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $ri as $file ) {
			$file->isDir() ? rmdir( $file ) : unlink( $file );
		}
		if ( $remove_folder ) {
			@rmdir( $dir );
		}

		return true;
	}

	/**
	 * Complete Move File Or Folders
	 *
	 * @param $old_name
	 * @param $new_name
	 * @return bool
	 */
	public static function move( $old_name, $new_name ) {

		// Returns a parent directory's path (operates naively on the input string, and is not aware of the actual filesystem)
		$targetDir = dirname( $new_name );

		// here $targetDir is "/wp-content/xx/xx/x"
		if ( ! file_exists( $targetDir ) ) {
			mkdir( $targetDir, 0777, true ); // third parameter "true" allows the creation of nested directories
		}

		return rename( rtrim( $old_name, "/" ), rtrim( $new_name, "/" ) );
	}

	/**
	 * Create Folder
	 *
	 * @param $name
	 * @param $path
	 * @param int $permission
	 */
	public static function create_dir( $name, $path, $permission = 0755 ) {
		mkdir( rtrim( $path, "/" ) . "/" . $name, $permission, true );
	}

	/**
	 * Rename File or Folder
	 *
	 * @param $from_path
	 * @param $to_path
	 * @return bool
	 */
	public static function rename( $from_path, $to_path ) {
		$rename = rename( self::normalize_path( $from_path ), self::normalize_path( $to_path ) );
		if ( $rename === true ) {
			return true;
		}
		return false;
	}

	/**
	 * Get list of file From Directory
	 *
	 * @param $dir
	 * @param bool $sub_folder
	 * @param array $results
	 * @return array
	 */
	public static function get_dir_contents( $dir, $sub_folder = false, &$results = array() ) {
		$files = scandir( $dir );
		foreach ( $files as $key => $value ) {
			$path = realpath( $dir . DIRECTORY_SEPARATOR . $value );
			if ( ! is_dir( $path ) ) {
				$results[] = $path;
			} else if ( $value != "." && $value != ".." ) {
				if ( $sub_folder == true ) {
					self::get_dir_contents( $path, $results );
				}
				$results[] = $path;
			}
		}

		return $results;
	}

	/**
	 * Remove File
	 *
	 * @param $path
	 * @return bool
	 */
	public static function remove_file( $path ) {
		if ( @unlink( self::normalize_path( $path ) ) === true ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get Size Format File
	 *
	 * @see https://developer.wordpress.org/reference/functions/size_format/
	 * @param $value
	 * @param int $decimals
	 * @return string
	 */
	public static function size_format( $value, $decimals = 0 ) {
		if ( is_numeric( $value ) ) {
			$file_size = $value;
		} else {
			$file_size = filesize( self::normalize_path( $value ) );
		}

		$KB_IN_BYTES = 1024;
		$MB_IN_BYTES = 1024 * $KB_IN_BYTES;
		$GB_IN_BYTES = 1024 * $MB_IN_BYTES;
		$TB_IN_BYTES = 1024 * $GB_IN_BYTES;
		$Quantity    = array(
			'TB' => $TB_IN_BYTES,
			'GB' => $GB_IN_BYTES,
			'MB' => $MB_IN_BYTES,
			'KB' => $KB_IN_BYTES,
			'B'  => 1,
		);
		if ( 0 === $file_size ) {
			return number_format( 0, $decimals ) . ' B';
		}
		foreach ( $Quantity as $unit => $mag ) {
			if ( doubleval( $file_size ) >= $mag ) {
				return number_format( $file_size / $mag, $decimals ) . ' ' . $unit;
			}
		}

		return false;
	}

	/**
	 * Join two filesystem paths together
	 *
	 * @see https://developer.wordpress.org/reference/functions/path_join/
	 * @param $base
	 * @param $path
	 * @return string
	 */
	public static function path_join( $base, $path ) {
		return rtrim( PHP::backslash_to_slash( $base ), '/' ) . '/' . ltrim( PHP::backslash_to_slash( $path ), '/' );
	}

	/**
	 * Normalize Path
	 *
	 * @see https://developer.wordpress.org/reference/functions/wp_normalize_path/
	 * @param $path
	 * @return mixed|null|string|string[]
	 */
	public static function normalize_path( $path ) {
		$path = PHP::backslash_to_slash( $path );
		$path = preg_replace( '|(?<=.)/+|', '/', $path );
		if ( ':' === substr( $path, 1, 1 ) ) {
			$path = ucfirst( $path );
		}
		return $path;
	}

	/**
	 * Create Zip Archive
	 *
	 * @param $source
	 * @param string $zip_name
	 * @param bool $base_folder
	 * @param array $except
	 * @return bool
	 * @throws \WP_CLI\ExitException
	 * @example Filesystem::zipData(ABSPATH.'/wp-admin', "wp-admin.zip", "wp-admin", array("about.php", "css/about.css", "images/"));
	 */
	public static function create_zip( $source, $zip_name = 'archive.zip', $base_folder = false, $except = array() ) {
		if ( extension_loaded( 'zip' ) ) {

			// Get real path for our folder
			$rootPath = realpath( $source );

			// Initialize archive object
			$zip = new \ZipArchive();
			$zip->open( $zip_name, \ZipArchive::CREATE | \ZipArchive::OVERWRITE );

			// Create recursive directory iterator
			$files = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $rootPath ),
				\RecursiveIteratorIterator::LEAVES_ONLY
			);

			foreach ( $files as $name => $file ) {
				// Skip directories (they would be added automatically)
				if ( ! $file->isDir() ) {
					// Get real and relative path for current file
					$filePath     = $file->getRealPath();
					$relativePath = substr( $filePath, strlen( $rootPath ) + 1 );

					//Check except Files Or dir
					$in_zip = true;
					if ( count( $except ) > 0 ) {
						foreach ( $except as $path ) {
							//Check is file or dir
							if ( is_file( $path ) ) {
								if ( in_array( self::normalize_path( $relativePath ), $except ) ) {
									$in_zip = false;
								}
							} else {
								//Check is dir
								$strlen = strlen( $path );
								if ( substr( self::normalize_path( $relativePath ), 0, $strlen ) == $path ) {
									$in_zip = false;
								}
							}
						}
					}

					if ( $in_zip === true ) {
						//Check if base Folder
						if ( $base_folder != false ) {
							$relativePath = $base_folder . "/" . $relativePath;
						}

						// Add current file to archive
						$zip->addFile( $filePath, $relativePath );
					}
				}
			}

			// Zip archive will be created only after closing object
			$zip->close();

		} else {
			CLI::error( "Zip extension Not loaded in your php." );
			return false;
		}

		return false;
	}


	/**
	 * Unzip File
	 *
	 * @param $file_path
	 * @param bool $path_to_unzip | without zip file name
	 *
	 * @example unzip("/wp-content/3.zip", "/wp-content/test/");
	 * We Don`t Use https://developer.wordpress.org/reference/functions/unzip_file/
	 * @return bool
	 */
	public static function unzip( $file_path, $path_to_unzip = false ) {
		if ( ! extension_loaded( 'zip' ) ) {
			CLI::error( "Zip extension Not loaded in your php." );
			return false;
		}
		$file = self::normalize_path( $file_path );

		// get the absolute path to $file
		if ( $path_to_unzip === false ) {
			$path = pathinfo( realpath( $file ), PATHINFO_DIRNAME );
		} else {
			$path = self::normalize_path( $path_to_unzip );
		}

		$zip = new \ZipArchive;
		$res = $zip->open( $file );
		if ( $res === true ) {
			// extract it to the path we determined above
			$zip->extractTo( $path );
			$zip->close();
		} else {
			CLI::error( "Zip File is Not Found." );
			return false;
		}

		return true;
	}

	/**
	 * Clean Folder name
	 *
	 * @param $name
	 * @param string $allowed_character
	 * @return string
	 */
	public static function sanitize_folder_name( $name, $allowed_character = '' ) {
		return strtolower( preg_replace( '/[^a-zA-Z0-9-_.' . $allowed_character . ']/', '', $name ) );
	}

	/**
	 * Load Mustache Machine
	 *
	 * @param string $ext
	 * @return \Mustache_Engine
	 */
	public static function load_mustache( $ext = '.mustache' ) {
		return new \Mustache_Engine( array(
			'loader' => new \Mustache_Loader_FilesystemLoader( WP_CLI_PACKAGIST_PATH . '/templates/', array( 'extension' => $ext ) ),
		) );
	}

	/**
	 * create file with content, and create folder structure if doesn't exist
	 *
	 * @param $file_path
	 * @param $data
	 * @return bool
	 */
	public static function file_put_content( $file_path, $data ) {
		//TODO check is writable folder
		try {
			$isInFolder = preg_match( "/^(.*)\/([^\/]+)$/", $file_path, $file_path_match );
			if ( $isInFolder ) {
				$folderName = $file_path_match[1];
				//$fileName   = $file_path_match[2];
				if ( ! is_dir( $folderName ) ) {
					mkdir( $folderName, 0777, true );
				}
			}
			file_put_contents( $file_path, $data, LOCK_EX );
			return true;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Checks if a folder exist
	 *
	 * @param $folder
	 *
	 * @return bool
	 */
	public static function folder_exist( $folder ) {
		$path = $folder;
		if ( function_exists( "realpath" ) ) {
			$path = realpath( $folder );
		}

		// If it exist, check if it's a directory
		return ( $path !== false AND is_dir( $path ) ) ? true : false;
	}

	/**
	 * Download file
	 *
	 * @param $link
	 * @param $path
	 * @return bool|string
	 */
	public static function download_file( $link, $path ) {
		if ( ! function_exists( 'download_url' ) ) {
			require_once( Wordpress::get_base_path() . 'wp-admin/includes/file.php' );
		}

		$tmp = download_url( $link, $timeout = 480 );
		if ( is_wp_error( $tmp ) ) {
			return false;
		} else {
			$new_path = self::path_join( $path, 'zip_' . time() . '.zip' );
			copy( $tmp, $new_path );
			@unlink( $tmp );
			return $new_path;
		}
	}

	/*
	 * Check For Reset all folder name in wordpress
	 */
	public static function Reset_Basic_Folder_Wordpress() {

		//check exist wp-content
		if ( self::folder_exist( self::path_join( Wordpress::get_base_path(), "wp-content" ) ) ) {
			//check uploads or plugins
			if ( self::folder_exist( self::path_join( Wordpress::get_base_path(), "wp-content/plugins" ) ) and self::folder_exist( self::path_join( Wordpress::get_base_path(), "wp-content/uploads" ) ) ) {
				$constant           = array(
					'WP_HOME',
					'WP_SITEURL',
					'WP_CONTENT_FOLDER',
					'WP_CONTENT_DIR',
					'WP_CONTENT_URL',
					'WP_PLUGIN_DIR',
					'PLUGINDIR',
					'WP_PLUGIN_URL',
					'UPLOADS'
				);
				$config_transformer = new \WPConfigTransformer( getcwd() . '/wp-config.php' );
				foreach ( $constant as $cons ) {
					$config_transformer->remove( 'constant', $cons );
				}
			}
		}
	}

	/**
	 * Sort Dir By Date
	 *
	 * @param $dir
	 * @param string $sort
	 * @param array $disagree_type
	 *
	 * @return array
	 */
	public static function sort_dir_by_date( $dir, $sort = "DESC", $disagree_type = array( ".php" ) ) {

		$ignored = array( '.', '..', '.svn', '.htaccess' );
		$files   = array();
		foreach ( scandir( $dir ) as $file ) {
			if ( in_array( $file, $ignored ) ) {
				continue;
			}
			if ( count( $disagree_type ) > 0 ) {
				foreach ( $disagree_type as $type ) {
					if ( strlen( stristr( $file, $type ) ) > 0 ) {
						continue 2;
					}
				}
			}
			$files[ $file ] = filemtime( $dir . '/' . $file );
		}

		if ( $sort == "DESC" ) {
			arsort( $files, SORT_NUMERIC );
		} else {
			asort( $files, SORT_NUMERIC );
		}

		$files = array_keys( $files );
		return $files;
	}

	/**
	 * Generates a Random Key.
	 *
	 * @param int $number
	 * @param bool $special
	 * @return string
	 */
	public static function random_key( $number = 64, $special = true ) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$spec  = '!@#$%^&*()-_[]{}<>~`+=,.;:/?|';
		if ( $special === true ) {
			$chars = $chars . $spec;
		}
		$charsLength = strlen( $chars );
		$key         = '';
		for ( $i = 0; $i < $number; $i ++ ) {
			$key .= $chars[ rand( 0, $charsLength - 1 ) ];
		}
		return $key;
	}

	/**
	 * Search , Replace in File
	 *
	 * @param $file_path
	 * @param array $search
	 * @param array $replace
	 * @param bool $is_save
	 * @param string $which_line
	 * @return array|string
	 */
	public static function search_replace_file( $file_path, $search = array(), $replace = array(), $is_save = true, $which_line = '' ) {

		//Check Exist file
		if ( file_exists( $file_path ) and is_dir( $file_path ) === false ) {
			$lines    = file( $file_path );
			$new_line = array();
			$z        = 1;
			foreach ( $lines as $l ) {
				if ( $which_line != "" ) {
					if ( $z == $which_line ) {
						$new = str_replace( $search, $replace, $l );
					} else {
						$new = $l;
					}
				} else {
					$new = str_replace( $search, $replace, $l );
				}

				$new_line[] = $new;
				$z ++;
			}

			//Save file
			if ( $is_save ) {
				self::file_put_content( $file_path, $new_line );
			}

			return $new_line;
		}

		return false;
	}

	/**
	 * Read Json File
	 *
	 * @param $file_path
	 * @return bool|array
	 */
	public static function read_json_file( $file_path ) {

		//Check Exist File
		if ( ! file_exists( self::normalize_path( $file_path ) ) ) {
			return false;
		}

		//Check readable
		if ( ! is_readable( self::normalize_path( $file_path ) ) ) {
			return false;
		}

		//Read File
		$strJson = file_get_contents( $file_path );
		$array   = json_decode( $strJson, true );
		if ( $array === null ) {
			return false;
		}

		return $array;
	}

	/**
	 * Create Json File
	 *
	 * @param $file_path
	 * @param $array
	 * @param bool $JSON_PRETTY
	 * @return bool
	 */
	public static function create_json_file( $file_path, $array, $JSON_PRETTY = true ) {

		//Prepare Data
		if ( $JSON_PRETTY ) {
			$data = json_encode( $array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK );
		} else {
			$data = json_encode( $array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK );
		}

		//Save To File
		if ( self::file_put_content( $file_path, $data ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check File Age
	 *
	 * @param $path
	 * @param $hour
	 * @return bool
	 */
	public static function check_file_age( $path, $hour ) {

		//Get Now Time
		$now    = time();
		$second = 60 * 60 * $hour;

		//Check File Age
		if ( $now - filemtime( $path ) >= $second ) {
			return true;
		}

		return false;
	}

}