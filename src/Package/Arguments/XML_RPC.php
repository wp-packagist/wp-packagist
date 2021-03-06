<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Package\Utility\Package_Install;
use WP_CLI_PACKAGIST\Package\Utility\Package_Temporary;
use WP_CLI_PACKAGIST\Package\Utility\Package_Update;

class XML_RPC
{
    public static $default_active_xml_rpc = true;

    /**
     * Check Enable XML RPC
     * @after_wp_load
     * @return bool
     */
    public static function isEnableXML_RPC()
    {
        return has_filter('xmlrpc_enabled') === false;
    }

    /**
     * Update WordPress XML-RPC
     *
     * @param $mu_plugin_path
     * @param bool $activate
     */
    public static function update($mu_plugin_path, $activate = true)
    {
        //Get MU PLUGIN Path
        $_plugin_path = \WP_CLI_FileSystem::path_join($mu_plugin_path, 'disable-xmlrpc.php');

        // Show Loading only in Update Process
        if (Package_Update::isUpdateProcess()) {
            // get Temp Package
            $tmp = Package_Temporary::getTemporaryFile();

            // Get Current xm_rpc status
            $tmp_xml_rpc = (isset($tmp['config']['xml-rpc']) ? $tmp['config']['xml-rpc'] : self::isEnableXML_RPC());

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
        if (Package_Update::isUpdateProcess()) {
            //Flush ReWrite
            Permalink::runFlushRewriteCLI();

            // Add Update Log
            Package_Install::add_detail_log(($activate === true ? "Enable" : "Disable") . " WordPress " . \WP_CLI_Helper::color("XML-RPC", "Y") . "");
        }
    }

}