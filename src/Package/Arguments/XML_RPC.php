<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\install;
use WP_CLI_PACKAGIST\Package\Utility\temp;
use WP_CLI_PACKAGIST\Package\Utility\update;

class XML_RPC
{
    public static $default_active_xml_rpc = true;

    /**
     * Update WordPress XML-RPC
     *
     * @param $mu_plugin_path
     * @param bool $activate
     */
    public static function update_xml_rpc($mu_plugin_path, $activate = true)
    {
        //Get MU PLUGIN Path
        $_plugin_path = \WP_CLI_FileSystem::path_join($mu_plugin_path, 'disable-xmlrpc.php');

        // Show Loading only in Update Process
        if (update::isUpdateProcess()) {
            // get Temp Package
            $localTemp = temp::get_temp(\WP_CLI_Util::getcwd());
            $tmp       = ($localTemp === false ? array() : $localTemp);

            // Get Current xm_rpc status
            $tmp_xml_rpc = (isset($tmp['config']['xml-rpc']) ? $tmp['config']['xml-rpc'] : ! file_exists($_plugin_path));

            // If Not any change
            if ($tmp_xml_rpc == $activate) {
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
                $mustache->render('mu-plugins/disable-xmlrpc')
            );
        }

        // Only in Update Process
        if (update::isUpdateProcess()) {
            //Flush ReWrite
            Permalink::runFlushRewriteCLI();

            // Add Update Log
            install::add_detail_log(($activate === true ? "Enable" : "Disable") . " WordPress XML-RPC");
        }
    }

}