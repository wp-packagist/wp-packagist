<?php

namespace WP_CLI_PACKAGIST\API;

use WP_CLI_PACKAGIST\Package\Package;

class WP_Themes_Api
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
        $this->config    = Package::get_config('wordpress_api', 'themes');
        $this->cache_dir = rtrim(WP_CLI_PACKAGIST_CACHE_PATH, "/") . '/' . $this->config["cache_dir"];
        /*
         * Check Cache Dir
         */
        if (\WP_CLI_FileSystem::folder_exist($this->cache_dir) === false) {
            \WP_CLI_FileSystem::create_dir($this->config["cache_dir"], WP_CLI_PACKAGIST_CACHE_PATH);
        }
    }

    /**
     * Generate Cache File Path
     *
     * @param $slug
     * @return string
     */
    public function generateCacheFilePath($slug)
    {
        return \WP_CLI_FileSystem::path_join($this->cache_dir, str_ireplace("[slug]", $slug, $this->config['file_name']));
    }

    /**
     * Get Theme Data By Slug
     *
     * @param $slug
     * @param bool $force_update
     * @return array
     */
    public function get_theme_data($slug, $force_update = false)
    {
        //Generate File Path
        $file_path = $this->generateCacheFilePath($slug);

        //Check Cache File exist
        if (file_exists($file_path)) {
            //if cache file exist we used same file
            $json_data = \WP_CLI_FileSystem::read_json_file($file_path);

            // if Force Update
            if ($force_update === false) {
                //if require update by calculate cache time
                $cacheFileAge = (time() - filemtime($file_path) >= 60 * $this->config['age']);
                if (isset($json_data) and $cacheFileAge === false) {
                    return array('status' => true, 'data' => $json_data);
                }
            }
        }

        //Fetch Theme data
        $theme_data = $this->fetch_theme_data($slug);
        if ($theme_data['status'] === false) {
            if (isset($json_data) and ! empty($json_data)) {
                return array('status' => true, 'data' => $json_data);
            } else {
                return $theme_data;
            }
        } else {
            return $theme_data;
        }
    }

    /**
     * Fetch Theme Data from WordPress.org API
     *
     * @param $slug
     * @return array
     */
    public function fetch_theme_data($slug)
    {
        //Generate File Path
        $file_path = $this->generateCacheFilePath($slug);

        //Connect To Wordpress API
        $data = \WP_CLI_Helper::http_request(str_ireplace("[slug]", $slug, $this->config['themes_data']));
        if ($data != false) {
            //convert list to json file
            $data = json_decode($data, true);

            //Check found Plugin information
            if (array_key_exists('themes', $data) and empty($data['themes'])) {
                return array('status' => false, 'data' => Package::_e('wordpress_api', 'not_found', array("[name]" => $slug, "[type]" => "theme")));
            } else {
                //Connect To Wordpress SVN To Get Versions
                $html = \WP_CLI_Helper::http_request(str_ireplace("[slug]", $slug, $this->config['themes_version_list']), 'GET', 300, array('Accept' => 'text/javascript, text/html, application/xml, */*'));
                if ($html != false) {
                    //Get List Of Versions
                    $DOM = new \DOMDocument();
                    @$DOM->loadHTML($html);
                    $versions = array();
                    $items    = $DOM->getElementsByTagName('a');
                    foreach ($items as $item) {
                        $href = str_replace('/', '', $item->getAttribute('href')); // Remove trailing slash
                        if (strpos($href, 'http') === false && '..' !== $href) {
                            $versions[] = $href;
                        }
                    }

                    //Create Data For push to json files
                    $data                  = array_shift($data['themes']);
                    $data['list_versions'] = $versions;

                    //Create Cache file
                    \WP_CLI_FileSystem::create_json_file($file_path, $data, false);
                } else {
                    //Show Error connect to WP API
                    return array('status' => false, 'data' => Package::_e('wordpress_api', 'connect'));
                }
            }
        } else {
            //Show Error connect to WP API
            return array('status' => false, 'data' => Package::_e('wordpress_api', 'connect'));
        }

        return array('status' => true, 'data' => $data);
    }

    /**
     * Get List of Theme Versions
     *
     * @param $slug
     * @return array
     */
    public function get_list_theme_versions($slug)
    {
        //Get Plugin Data
        $data = $this->get_theme_data($slug);
        if ($data['status'] === false) {
            return $data;
        } else {
            //Get List Versions
            $versions = $data['data']['list_versions'];

            //Sort Version Number
            usort($versions, 'version_compare');

            //return
            return array('status' => true, 'data' => $versions);
        }
    }

    /**
     * Get Last Version of theme
     *
     * @param $slug
     * @param bool $force_update
     * @return array
     */
    public function get_last_version_theme($slug, $force_update = false)
    {
        //Get Theme Data
        $data = $this->get_theme_data($slug, $force_update);
        if ($data['status'] === false) {
            return $data;
        } else {
            return $data['data']['version'];
        }
    }
}