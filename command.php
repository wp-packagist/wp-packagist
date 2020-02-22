<?php

namespace WP_CLI_PACKAGIST;

# Check exist WordPress command line
if ( ! class_exists('WP_CLI')) {
    return;
}

# Define Basic WP-CLI PACKAGIST Constant
define("WP_CLI_PACKAGIST_PATH", dirname(__FILE__));
define("WP_CLI_PACKAGIST_TEMPLATE_PATH", WP_CLI_PACKAGIST_PATH . '/templates/');
define("WP_CLI_PACKAGIST_HOME_PATH", \WP_CLI_Helper::get_home_path('packagist'));
define("WP_CLI_PACKAGIST_CACHE_PATH", \WP_CLI_Helper::get_cache_dir('pack'));

# Register 'init' Command
require_once(WP_CLI_PACKAGIST_PATH . '/src/init.php');

# Register 'install' Command
require_once(WP_CLI_PACKAGIST_PATH . '/src/install.php');

# Register 'update' Command
require_once(WP_CLI_PACKAGIST_PATH . '/src/update.php');

# Register 'uninstall' Command
require_once(WP_CLI_PACKAGIST_PATH . '/src/uninstall.php');

# Register 'run' Command
require_once(WP_CLI_PACKAGIST_PATH . '/src/run.php');

# Register 'Pack' Command
\WP_CLI::add_command('pack', Pack::class);