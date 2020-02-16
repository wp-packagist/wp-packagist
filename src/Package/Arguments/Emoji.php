<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

class Emoji
{
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

        //Remove Plugin if Exist
        if (file_exists($_plugin_path)) {
            \WP_CLI_FileSystem::remove_file($_plugin_path);
        }

        //Create File Content
        if ($activate === false) {
            //Push new Plugin
            $mustache = \WP_CLI_FileSystem::load_mustache(WP_CLI_PACKAGIST_TEMPLATE_PATH);
            \WP_CLI_FileSystem::file_put_content(
                $_plugin_path,
                $mustache->render('mu-plugins/disable-emoji')
            );
        }
    }

}