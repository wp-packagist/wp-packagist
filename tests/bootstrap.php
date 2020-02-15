<?php

$vendorDir = realpath(dirname(__FILE__) . '/..') . '/vendor';
if (!defined('WP_CLI_ROOT')) {
    define('WP_CLI_ROOT', $vendorDir . '/wp-cli/wp-cli');
}

include WP_CLI_ROOT . '/php/utils.php';
include WP_CLI_ROOT . '/php/dispatcher.php';
include WP_CLI_ROOT . '/php/class-wp-cli.php';
include WP_CLI_ROOT . '/php/class-wp-cli-command.php';

\WP_CLI\Utils\load_dependencies();

require_once 'Logger.php';
\WP_CLI::set_logger(new Logger());
