<?php

namespace WP_CLI_PACKAGIST\Utility;

class WP_CLI_ERROR {
	/**
	 * Status after process
	 *
	 * @var boolean
	 */
	public $status = true;

	/**
	 * List of Error log
	 *
	 * @var array
	 */
	public $error = array();

	/**
	 * List of Success log
	 *
	 * @var array
	 */
	public $success = array();

	/**
	 * WP_CLI_ERROR constructor.
	 */
	public function __construct() {
	}

	/**
	 * Push new error message
	 *
	 * @param $text
	 */
	public function add_error( $text ) {
		$this->error[] = $text;
	}

	/**
	 * Push new success message
	 *
	 * @param $text
	 */
	public function add_success( $text ) {
		$this->success[] = $text;
	}

	/**
	 * Check Exist Error
	 */
	public function is_cli_error() {
		if ( count( $this->error ) > 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * Show result
	 */
	public function result() {

		//Save msg
		$data = $this->success;

		//check if has error set status to false
		if ( $this->is_cli_error() ) {
			$this->status = false;
			$data         = $this->error;
		}

		return array( 'status' => $this->status, 'data' => $data );
	}

}