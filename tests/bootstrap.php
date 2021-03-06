<?php

// Vendor Composer
$vendorDir = realpath(dirname(__FILE__) . '/..') . '/vendor';

// Define Load File From WP-CLI
if ( ! defined('WP_CLI_ROOT')) {
    define('WP_CLI_ROOT', $vendorDir . '/wp-cli/wp-cli');
}
include WP_CLI_ROOT . '/php/utils.php';
include WP_CLI_ROOT . '/php/dispatcher.php';
include WP_CLI_ROOT . '/php/class-wp-cli.php';
include WP_CLI_ROOT . '/php/class-wp-cli-command.php';
\WP_CLI\Utils\load_dependencies();

// Define Basic WP-CLI PACKAGIST Constant
if ( ! defined('WP_CLI_PACKAGIST_PATH')) {
    define("WP_CLI_PACKAGIST_PATH", realpath(dirname(__FILE__) . '/..'));
}
if ( ! defined('WP_CLI_PACKAGIST_TEMPLATE_PATH')) {
    define("WP_CLI_PACKAGIST_TEMPLATE_PATH", WP_CLI_PACKAGIST_PATH . '/templates/');
}
if ( ! defined('WP_CLI_PACKAGIST_HOME_PATH')) {
    define("WP_CLI_PACKAGIST_HOME_PATH", \WP_CLI_Helper::get_home_path('packagist'));
}
if ( ! defined('WP_CLI_PACKAGIST_CACHE_PATH')) {
    define("WP_CLI_PACKAGIST_CACHE_PATH", \WP_CLI_Helper::get_cache_dir('pack'));
}

// Start Logger
require_once 'Logger.php';
\WP_CLI::set_logger(new Logger());
