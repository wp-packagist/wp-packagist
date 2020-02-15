<?php

namespace WP_CLI_PACKAGIST\Package\Params;

use WP_CLI_PACKAGIST\API\WP_Themes_Api;
use WP_CLI_PACKAGIST\Package\Arguments\Version;
use WP_CLI_PACKAGIST\Package\Package;

class themes
{
    /**
     * Get Wordpress Package options
     *
     * @var string
     */
    public $package_config;

    /**
     * Core constructor.
     */
    public function __construct()
    {
        /*
         * Set config Global
         */
        $this->package_config = Package::get_config('package');
    }

    /**
     * Validation Package
     *
     * @param $pkg_array
     * @return array
     */
    public function validation($pkg_array)
    {
        //Create new validation
        $valid = new \WP_CLI_ERROR();

        //Get themes parameter
        $parameter = $pkg_array['themes'];

        //Sanitize Custom Key
        $check = $this->sanitize_themes($parameter, true);
        if ($check['status'] === false) {
            foreach ($check['data'] as $error) {
                $valid->add_error($error);
                break;
            }
        } else {
            //Get Sanitize Data
            $return['themes'] = array_shift($check['data']);

            //Push To sanitize return data
            $valid->add_success($return['themes']);
        }

        return $valid->result();
    }

    /**
     * Sanitize Themes Params
     *
     * @param $array
     * @param bool $validate
     * @return string|boolean|array
     * @since 1.0.0
     */
    public function sanitize_themes($array, $validate = false)
    {
        //Create new validation
        $valid = new \WP_CLI_ERROR();

        //Load Plugins API
        $themes_api = new WP_Themes_Api();

        //Check is String
        if (is_string($array)) {
            $valid->add_error(Package::_e('package', 'is_string', array("[key]" => "themes: { ..")));
        } elseif (empty($array)) {
            //Check Empty Array
            $valid->add_error(Package::_e('package', 'empty_val', array("[key]" => "themes: { ..")));
        } else {
            //Check is Assoc array
            if (\WP_CLI_Util::is_assoc_array($array) === false) {
                $valid->add_error(Package::_e('package', 'er_valid', array("[key]" => "themes: { ..")));
            } else {
                //Check Empty Value in array
                foreach ($array as $themes_n => $themes_v) {
                    //Check if array version
                    if (is_array($themes_v)) {
                        $valid->add_error(Package::_e('wordpress_api', 'er_string', array("[name]" => $themes_n, "[type]" => "theme")));
                        break;
                    } elseif (empty(\WP_CLI_Util::to_lower_string($themes_v))) {
                        //Check is empty version
                        $valid->add_error(Package::_e('package', 'er_empty_source', array("[key]" => $themes_n, "[type]" => "theme")));
                        break;
                    } else {
                        //Check Preg Theme slug
                        $slug = \WP_CLI_Util::to_lower_string(preg_replace(Package::get_config('wordpress_api', 'slug'), '', $themes_n));
                        if ($slug != \WP_CLI_Util::to_lower_string($themes_n)) {
                            $valid->add_error(Package::_e('wordpress_api', 'er_slug', array("[name]" => $themes_n, "[type]" => "theme")));
                            break;
                        } else {
                            //to lowercase all data
                            unset($array[$themes_n]);
                            $array[$slug] = \WP_CLI_Util::to_lower_string($themes_v);
                        }
                    }
                }

                //Save Product Type Source
                $source_list = array();

                //Check text version validation
                if ( ! $valid->is_cli_error()) {
                    foreach ($array as $themes_n => $themes_v) {
                        //Check is Url
                        $url = filter_var($themes_v, FILTER_VALIDATE_URL);
                        if ($url === false) {
                            //Check is static value
                            if ( ! in_array($themes_v, Package::get_config('package', 'latest'))) {
                                //Check is Version number
                                if (\WP_CLI_Util::is_semver_version($themes_v) === false) {
                                    $valid->add_error(Package::_e('package', 'er_wrong_version', array("[key]" => $themes_n, "[type]" => "theme")));
                                    break;
                                } else {
                                    $source_list[$themes_n] = 'version';
                                }
                            } else {
                                $array[$themes_n] = $source_list[$themes_n] = 'latest';
                            }
                        } else {
                            $source_list[$themes_n] = 'url';
                        }
                    }
                }

                //Check themes slug exist in Wordpress APi or exist Custom url zip file
                if ( ! $valid->is_cli_error()) {
                    foreach ($source_list as $key => $value) {
                        //Check is Custom Url zip file
                        if ($value == "url") {
                            //Check Themes Zip file Url
                            if (defined('WP_CLI_PACKAGIST_RUN_EXIST_CUSTOM_URL')) {
                                $exist_url = Version::exist_url($array[$key], $key . " theme", true);
                                if ($exist_url['status'] === false) {
                                    $valid->add_error($exist_url['data']);
                                    break;
                                } else {
                                    //Set Correct Url to Download Themes zip file (if redirect)
                                    $array[$key] = $exist_url['data'];
                                }
                            }
                        } else {
                            //Check Themes slug is exist in Wordpress API
                            $get_plugin_data = $themes_api->get_list_theme_versions($key);
                            if ($get_plugin_data['status'] === false) {
                                $valid->add_error($get_plugin_data['data']);
                                break;
                            } else {
                                //Check exist Version number
                                if ($value == "version" and ! in_array($array[$key], $get_plugin_data['data'])) {
                                    $valid->add_error(Package::_e('wordpress_api', 'not_available_ver', array("[name]" => $key, "[type]" => "theme", "[ver]" => $array[$key])));
                                    break;
                                }
                            }
                        }
                    }
                }

                //Push To sanitize return data
                if ( ! $valid->is_cli_error()) {
                    $valid->add_success($array);
                }
            }
        }

        return ($validate === true ? $valid->result() : $array);
    }

}