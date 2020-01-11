<?php

namespace WP_CLI_PACKAGIST\Package\Utility;

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Params\mysql;

/**
 * install WordPress Package
 */
class install extends Package {
	/**
	 * install WordPress Package
	 *
	 * @param $pkg_array
	 * @throws \Exception
	 */
	public function install( $pkg_array ) {

		//Remove please wait
		if ( defined( 'WP_CLI_PLEASE_WAIT_LOG' ) ) {
			\WP_CLI_Helper::pl_wait_end();
		}

		//Set Timer for Process
		$start_time = time();

		//Get number Step
		$all_step = $this->get_number_step( $pkg_array );

		//Start Step
		$step = 1;

		//Run Params
		foreach ( Package::get_config( 'package', 'params' ) as $class_name ) {

			//Check Exist pkg Key
			if ( array_key_exists( $class_name, $pkg_array ) ) {

				//get Class name
				$class = $this->package_config['params_namespace'] . $class_name;

				//Create new Obj from class
				$obj = new $class();

				//check validation method exist in class
				if ( \WP_CLI_Util::search_method_from_class( $obj, 'install' ) ) {

					//Run install Method
					$run = $obj->install( $pkg_array, array( 'all_step' => $all_step, 'step' => $step ) );

					//Check Run Status
					if ( $run['status'] === true ) {
						$step = $run['step'];
					}
				}
			}
		}

		//Add Package LocalTemp
		temp::save_temp( \WP_CLI_Util::getcwd(), $pkg_array );

		//Success Process
		\WP_CLI_Helper::br();
		\WP_CLI_Helper::success( Package::_e( 'package', 'success_install' ) . ' ' . Package::_e( 'config', 'process_time', array( "[time]" => \WP_CLI_Helper::process_time( $start_time ) ) ) );
	}

	/**
	 * Show install Log
	 *
	 * @param $this_step
	 * @param $all_step
	 * @param $text
	 */
	public static function install_log( $this_step, $all_step, $text ) {
		\WP_CLI_Helper::line( \WP_CLI_Helper::color( "install {$this_step}/{$all_step}:", "Y" ) . " " . $text );
	}

	/**
	 * Added Detail Log
	 *
	 * @param $text
	 * @param int $space
	 */
	public static function add_detail_log( $text, $space = 1 ) {
		# Show Log
		\WP_CLI_Helper::line( str_repeat( " ", $space ) . "- " . $text );

		# Used in Update Package
		if ( ! defined( 'WP_CLI_APP_PACKAGE_UPDATE_LOG' ) ) {
			define( 'WP_CLI_APP_PACKAGE_UPDATE_LOG', true );
		}
	}

	/**
	 * Check Number install Step
	 *
	 * @param $package_data
	 * @return int
	 */
	public function get_number_step( $package_data ) {

		/**
		 * Default Number Step
		 *
		 * 1) WordPress Core
		 * 2) Create wp-config.php file
		 * 3) Install WordPress
		 * 4) wordPress security Removed file
		 */
		$step = 4;

		//Check WordPress Locale
		if ( isset( $package_data['core']['locale'] ) and $package_data['core']['locale'] != $this->package_config['default']['locale'] ) {
			$step ++;
		}

		//Check WordPress timeZone
		if ( isset( $package_data['config']['timezone'] ) and ! empty( $package_data['config']['timezone'] ) ) {
			$step ++;
		}

		//List Of Level
		$level = array(
			'dir'           => array( 'key' => ( isset( $package_data['dir'] ) ? $package_data['dir'] : array() ), 'min' => 0 ),
			'network-sites' => array( 'key' => ( isset( $package_data['core']['network']['sites'] ) ? $package_data['core']['network']['sites'] : array() ), 'min' => 0 ),
			'options'       => array( 'key' => ( isset( $package_data['config']['options'] ) ? $package_data['config']['options'] : array() ), 'min' => 0 ),
			'users'         => array( 'key' => ( isset( $package_data['config']['users'] ) ? $package_data['config']['users'] : array() ), 'min' => 0 ),
			'plugins'       => array( 'key' => ( isset( $package_data['plugins'] ) ? $package_data['plugins'] : array() ), 'min' => 0 ),
			'themes'        => array( 'key' => ( isset( $package_data['themes'] ) ? $package_data['themes'] : array() ), 'min' => 0 ),
			'commands'      => array( 'key' => ( isset( $package_data['commands'] ) ? $package_data['commands'] : array() ), 'min' => 0 ),
		);
		foreach ( $level as $k => $v ) {
			if ( ! empty( $v['key'] ) and count( $v['key'] ) > $v['min'] ) {
				$step ++;
			}
		}

		//Check Rest API
		if ( isset( $package_data['config']['rest-api'] ) ) {
			$step ++;
		}

		//Check Created Database
		if ( isset( $package_data['mysql'] ) ) {
			if ( mysql::exist_db_name( $package_data['mysql'] ) === false ) {
				$step ++;
			}
		}

		//Check Permalink
		if ( isset( $package_data['config']['permalink'] ) and count( $package_data['config']['permalink'] ) > 0 and isset( $package_data['core']['network'] ) and $package_data['core']['network'] === false ) {
			$step ++;
		}

		//Check Active theme
		if ( isset( $package_data['config']['theme'] ) ) {
			$step ++;
		}

		return $step;
	}

}