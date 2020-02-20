<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Package\Utility\Package_Install;
use WP_CLI_PACKAGIST\Package\Utility\Package_Temporary;
use WP_CLI_PACKAGIST\Package\Utility\Package_Update;

class Emoji
{
    public static $default_active_emoji = true;

    /**
     * Check Is Enable Emoji From WordPress
     * @after_wp_load
     */
    public static function isEnableEmoji()
    {
        return has_action('wp_head', 'print_emoji_detection_script') != false;
    }

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

        // Only in Update Process
        if (Package_Update::isUpdateProcess()) {
            // get Temp Package
            $tmp = Package_Temporary::getTemporaryFile();

            // Get Current emoji status
            $tmp_emoji = (isset($tmp['config']['emoji']) ? $tmp['config']['emoji'] : self::isEnableEmoji());

            // If Not any change
            if ($tmp_emoji == $activate) {
                return;
            }

            //Remove Plugin if Exist in Update Process
            if (file_exists($_plugin_path)) {
                \WP_CLI_FileSystem::remove_file($_plugin_path);
            }
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
        if (Package_Update::isUpdateProcess()) {
            // Add Update Log
            Package_Install::add_detail_log(($activate === true ? "Enable" : "Disable") . " WordPress Emoji");
        }
    }

}