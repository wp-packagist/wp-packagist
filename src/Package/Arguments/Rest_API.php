<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Package\Package;

class Rest_API
{
    /**
     * Get List Of WordPress Rest API namespace/route
     * @ after_wp_load
     */
    public static function get_rest_list()
    {
        //Creat Empty Object
        $rest = array('namespace' => array(), 'route' => array());

        //Get List Of Detail WP API
        if (function_exists('rest_get_server')) {
            //Get List
            $wp_rest_server = rest_get_server();

            //Get List OF namespace
            $all_namespaces = $wp_rest_server->get_namespaces();

            //Get List Of Route
            $all_routes = array_keys($wp_rest_server->get_routes());

            //Push to array
            $rest['namespace'] = $all_namespaces;
            $rest['route']     = $all_routes;
        }

        return $rest;
    }

    /**
     * Remove All Default WordPress Route
     * Option name : rewrite_rules (wp-json)
     */
    public static function remove_all_default_route()
    {
        $t = "# Remove All WordPress Default Rest API Route\n";
        $t .= "remove_action('rest_api_init', 'create_initial_rest_routes', 99);" . "\n";
        return $t;
    }

    /**
     * Change WordPress Rest API Prefix
     * NOTICE : After Push must Flush Rewrite
     *
     * @param $prefix
     * @return string
     */
    public static function change_rest_api_prefix($prefix)
    {
        $t = "# Change WordPress Rest API Prefix\n";
        $t .= 'add_filter( \'rest_url_prefix\', function ($slug) { return \'' . $prefix . '\'; });' . "\n";
        return $t;
    }

    /**
     * Complete Disable WordPress Rest API
     */
    public static function complete_disable_rest_api()
    {
        /**
         * We Removed All Register Route in REST API
         * Other way is show error authentication.
         *
         * add_filter( 'rest_authentication_errors', function( $access ){ return new WP_Error( 'rest_cannot_access', 'Bye', array( 'status' => 403 ) ); } );
         */
        $t = "# Removed All WordPress Rest API Route\n";
        $t .= 'add_filter( \'rest_endpoints\', function ( $endpoints ) { return $endpoints = array(); });' . "\n";
        $t .= "# Remove Action in WordPress Theme" . "\n";
        $t .= 'remove_action( \'wp_head\', \'rest_output_link_wp_head\', \'10\' );' . "\n";
        $t .= 'remove_action( \'wp_head\', \'wp_oembed_add_discovery_links\' );' . "\n";
        $t .= 'remove_action( \'template_redirect\', \'rest_output_link_header\', \'11\' );' . "\n";

        return $t;
    }

    /**
     * Disable Custom Route From WordPress Rest API
     *
     * @param $array
     * @return string
     */
    public static function disable_custom_route($array)
    {
        $t = "# Disable Custom Route From WordPress Rest API\n";
        $t .= 'add_filter(\'rest_endpoints\', function($endpoints) {' . "\n";
        foreach ($array as $route) {
            //Sanitize Route
            $rote_name = "/" . ltrim(str_replace(array("\"", "'"), "", $route), "/");

            //Push
            $t .= '  if ( isset( $endpoints[\'' . $rote_name . '\'] ) ) { unset( $endpoints[\'' . $rote_name . '\'] ); }' . "\n";
        }
        $t .= ' return $endpoints;' . "\n";
        $t .= '});' . "\n";

        return $t;
    }

    /**
     * Reset WordPress REST API Url Prefix
     */
    public static function reset_rest_url_prefix()
    {
        //Get Default Prefix
        $default_prefix = Package::get_config('package', 'default_rest_prefix');

        //Run
        $command = 'eval "add_filter( \'rest_url_prefix\', function ($slug) { return \'' . $default_prefix . '\'; }); flush_rewrite_rules(true);"';
        \WP_CLI::runcommand($command);
    }

    /**
     * Update WordPress REST API
     *
     * @param $mu_plugin_path
     * @param array|boolean $args
     */
    public static function update_rest_api($mu_plugin_path, $args)
    {
        //Get MU PLUGIN Path
        $plugin = \WP_CLI_FileSystem::path_join($mu_plugin_path, 'rest-api.php');

        //Remove Plugin if Exist
        if (file_exists($plugin)) {
            \WP_CLI_FileSystem::remove_file($plugin);
        }

        //Reset REST API url prefix
        self::reset_rest_url_prefix();

        //Create File Content
        if ( ! is_null($args)) {
            //Create Empty Content
            $content = array(
                'rest_prefix'              => '',
                'complete_disable_rest'    => '',
                'remove_all_default_route' => '',
                'disable_custom_route'     => '',
            );

            //Check Disable REST API
            if (is_bool($args) and $args === false) {
                $content['complete_disable_rest'] = self::complete_disable_rest_api();
            } else {
                //Change Prefix
                if (isset($args['prefix']) and is_string($args['prefix']) and ! empty($args['prefix']) and strtolower($args['prefix']) != Package::get_config('package', 'default_rest_prefix')) {
                    $content['rest_prefix'] = self::change_rest_api_prefix(strtolower($args['prefix']));
                }

                //Check Disable Route
                if (isset($args['disable'])) {
                    //Check disable All default
                    if (is_string($args['disable']) and $args['disable'] == "default") {
                        $content['complete_disable_rest'] = self::remove_all_default_route();
                    }

                    //Check Disable custom Route
                    if (is_array($args['disable'])) {
                        $content['complete_disable_rest'] = self::disable_custom_route($args['disable']);
                    }
                }
            }

            //Push new Plugin
            $mustache = \WP_CLI_FileSystem::load_mustache(WP_CLI_PACKAGIST_TEMPLATE_PATH);
            \WP_CLI_FileSystem::file_put_content(
                $plugin,
                $mustache->render('mu-plugins/rest-api', $content)
            );

            //Flush ReWrite
            Permalink::flush_rewrite(true);
        }
    }

}