<?php

/**
 * Explorer Current working directory.
 *
 * ## DOCUMENT
 *
 *      https://wp-packagist.com/docs/explorer/
 *
 * ## EXAMPLES
 *
 *      # Explorer Current working directory
 *      $ wp explorer
 *
 * @when before_wp_load
 */

use WP_CLI_PACKAGIST\Utility\CLI;
use WP_CLI_PACKAGIST\Utility\PHP;

\WP_CLI::add_command( 'explorer', function ( $args, $assoc_args ) {
	CLI::Browser( PHP::getcwd() );
} );