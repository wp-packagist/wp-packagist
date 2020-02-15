<?php

namespace WP_CLI_PACKAGIST\API;

use WP_CLI_PACKAGIST\Package\Package;

class WP_Plugins_Api
{
    /**
     * WP Plugins API Config
     *
     * @var string
     */
    public $config;

    /**
     * Cache Dir Path
     *
     * @var string
     */
    public $cache_dir;

    /**
     * WP_Plugins_Api constructor.
     */
    public function __construct()
    {
        /*
         * Set Default config
         */
        $this->config    = Package::get_config('wordpress_api', 'plugins');
        $this->cache_dir = rtrim(WP_CLI_PACKAGIST_CACHE_PATH, "/") . '/' . $this->config['cache_dir'];
        /*
         * Check Cache Dir
         */
        if (\WP_CLI_FileSystem::folder_exist($this->cache_dir) === false) {
            \WP_CLI_FileSystem::create_dir($this->config['cache_dir'], WP_CLI_PACKAGIST_CACHE_PATH);
        }
    }

    /**
     * Get Plugin Data By Slug
     *
     * @param $slug
     * @param bool $force_update
     * @return array
     */
    public function get_plugin_data($slug, $force_update = false)
    {
        //Generate File Path
        $file_path = \WP_CLI_FileSystem::path_join($this->cache_dir, str_ireplace("[slug]", $slug, $this->config['file_name']));

        //Check Cache File exist
        if (file_exists($file_path)) {
            //if cache file exist we used same file
            $json_data = \WP_CLI_FileSystem::read_json_file($file_path);
        }

        // if Force Update
        if ($force_update === false) {
            //if require update by calculate cache time
            if (isset($json_data) and \WP_CLI_FileSystem::check_file_age($file_path, $this->config['age']) === false) {
                return array('status' => true, 'data' => $json_data);
            }
        }

        //Fetch Plugin data
        $plugin_data = $this->_fetch_plugin_data($slug);
        if ($plugin_data['status'] === false) {
            if (isset($json_data) and ! empty($json_data)) {
                return array('status' => true, 'data' => $json_data);
            } else {
                return $plugin_data;
            }
        } else {
            return $plugin_data;
        }
    }

    /**
     * Fetch Plugin Data from WordPress.org API
     *
     * @param $slug
     * @return array
     */
    public function _fetch_plugin_data($slug)
    {
        //Generate File Path
        $file_path = \WP_CLI_FileSystem::path_join($this->cache_dir, str_ireplace("[slug]", $slug, $this->config['file_name']));

        //Connect To Wordpress API
        $data = \WP_CLI_Helper::http_request(str_ireplace("[slug]", $slug, $this->config['plugin_data']));
        if ($data != false) {
            //convert list to json file
            $json_data = json_decode($data, true);

            //Check found Plugin information
            if (array_key_exists('error', $json_data)) {
                return array('status' => false, 'data' => Package::_e('wordpress_api', 'not_found', array("[name]" => $slug, "[type]" => "plugin")));
            } else {
                //Create Cache file
                \WP_CLI_FileSystem::create_json_file($file_path, $json_data, false);
            }
        } else {
            //Show Error connect to WP API
            return array('status' => false, 'data' => Package::_e('wordpress_api', 'connect'));
        }

        return array('status' => true, 'data' => $json_data);
    }

    /**
     * Get List of Plugin Versions
     *
     * @param $slug
     * @return array
     */
    public function get_list_plugin_versions($slug)
    {
        //Get Plugin Data
        $data = $this->get_plugin_data($slug);
        if ($data['status'] === false) {
            return $data;
        } else {
            //Push All Version to List
            foreach ($data['data']['versions'] as $ver => $link) {
                if (\WP_CLI_Util::is_semver_version($ver)) {
                    $version[] = $ver;
                }
            }

            //Push Last Version
            if (isset($version)) {
                if ( ! in_array($data['data']['version'], $version)) {
                    $version[] = $data['data']['version'];
                }
            } else {
                $version[] = $data['data']['version'];
            }

            //Sort Version Number
            usort($version, 'version_compare');

            //return
            return array('status' => true, 'data' => $version);
        }
    }

    /**
     * Get Last Version of plugin
     *
     * @param $slug
     * @return array
     */
    public function get_last_version_plugin($slug)
    {
        //Get Plugin Data
        $data = $this->get_plugin_data($slug);
        if ($data['status'] === false) {
            return $data;
        } else {
            return $data['data']['version'];
        }
    }

}