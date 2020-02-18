<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\install;
use WP_CLI_PACKAGIST\Package\Utility\temp;
use WP_CLI_PACKAGIST\Package\Utility\update;

class Emoji
{
    public static $default_active_emoji = true;

    /**
     * Update WordPress Emoji
     *
     * @param $mu_plugin_path
     * @param bool $activate
     */
    public static function update_emoji($mu_plugin_path, $activate = true)
    {
        //Get MU PLUGIN Path
        $_plugin_path = \WP_CLI_FileSystem::path_join($mu_plugin_path, 'disable-emoji.php');

        // Show Loading only in Update Process
        if (update::isUpdateProcess()) {
            // get Temp Package
            $localTemp = temp::get_temp(\WP_CLI_Util::getcwd());
            $tmp       = ($localTemp === false ? array() : $localTemp);

            // Get Current emoji status
            $tmp_emoji = (isset($tmp['config']['emoji']) ? $tmp['config']['emoji'] : ! file_exists($_plugin_path));

            // If Not any change
            if ($tmp_emoji == $activate) {
                return;
            }

            // Show please wait ...
            \WP_CLI_Helper::pl_wait_start();
        }

        //Remove Plugin if Exist
        if (file_exists($_plugin_path)) {
            \WP_CLI_FileSystem::remove_file($_plugin_path);
        }

        //Create File Content
        if ($activate === false) {
            $mustache = \WP_CLI_FileSystem::load_mustache(WP_CLI_PACKAGIST_TEMPLATE_PATH);
            \WP_CLI_FileSystem::file_put_content(
                $_plugin_path,
                $mustache->render('mu-plugins/disable-emoji')
            );
        }

        // Show Log only in Update Process
        if (update::isUpdateProcess()) {
            // Remove please wait ...
            \WP_CLI_Helper::pl_wait_end();

            // Add Update Log
            install::add_detail_log(rtrim(Package::_e('package', 'manage_item_blue', array("[work]" => ($activate === true ? "Enable" : "Disable"), "[key]" => "WordPress Emoji", "[type]" => "")), "."));
        }
    }

}