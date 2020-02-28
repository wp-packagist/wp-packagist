<?php

namespace WP_CLI_PACKAGIST\Package\Params;

use WP_CLI_PACKAGIST\API\WP_Plugins_Api;
use WP_CLI_PACKAGIST\Package\Arguments\Version;
use WP_CLI_PACKAGIST\Package\Package;

class Plugins
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

        //Get plugins parameter
        $parameter = $pkg_array['plugins'];

        //Sanitize Custom Key
        $check = $this->sanitize_plugins($parameter, true);
        if ($check['status'] === false) {
            foreach ($check['data'] as $error) {
                $valid->add_error($error);
                break;
            }
        } else {
            //Get Sanitize Data
            $return['plugins'] = array_shift($check['data']);

            //Push To sanitize return data
            $valid->add_success($return['plugins']);
        }

        return $valid->result();
    }

    /**
     * Sanitize Plugins Params
     *
     * @param $array
     * @param bool $validate
     * @return string|boolean|array
     * @since 1.0.0
     */
    public function sanitize_plugins($array, $validate = false)
    {
        //Create new validation
        $valid = new \WP_CLI_ERROR();

        //Load Plugins API
        $plugin_api = new WP_Plugins_Api();

        //Plugin List
        $plugin_list = array();

        //Check is String
        if (is_string($array)) {
            $valid->add_error(Package::_e('package', 'is_string', array("[key]" => "plugins: { ..")));
        } elseif (empty($array)) {
            //Check Empty Array
            $valid->add_error(Package::_e('package', 'empty_val', array("[key]" => "plugins: { ..")));
        } else {
            //Check is Assoc array
            if (\WP_CLI_Util::is_assoc_array($array) === false) {
                $valid->add_error(Package::_e('package', 'er_valid', array("[key]" => "plugins: { ..")));
            } else {
                //Lower Case
                $array = array_change_key_case($array, CASE_LOWER);

                //Check Empty Value in array
                foreach ($array as $plugin_slug => $plugin_v) {
                    //Activate
                    $activate = true;

                    //Url
                    $url = '';

                    //Version
                    $version = '';

                    //Check Preg Plugin slug
                    $slug = \WP_CLI_Util::to_lower_string(preg_replace(Package::get_config('wordpress_api', 'slug'), '', $plugin_slug));
                    if ($slug != \WP_CLI_Util::to_lower_string($plugin_slug)) {
                        $valid->add_error(Package::_e('wordpress_api', 'er_slug', array("[name]" => $plugin_slug, "[type]" => "plugin")));
                        break;
                    }

                    //Check String
                    if (is_string($plugin_v)) {
                        //Check Empty
                        if (empty(\WP_CLI_Util::to_lower_string($plugin_v))) {
                            $valid->add_error(Package::_e('package', 'er_empty_source', array("[key]" => $plugin_slug, "[type]" => "plugin")));
                            break;
                        } else {
                            //Check valid url or version
                            $plugin_url = filter_var($plugin_v, FILTER_VALIDATE_URL);
                            if ($plugin_url === false) {
                                //Check is static value
                                if ( ! in_array($plugin_v, Package::get_config('package', 'latest'))) {
                                    //Check is Version number
                                    if (\WP_CLI_Util::is_semver_version($plugin_v) === false) {
                                        $valid->add_error(Package::_e('package', 'er_wrong_version', array("[key]" => $plugin_slug, "[type]" => "plugin")));
                                        break;
                                    } else {
                                        $version = $plugin_v;
                                    }
                                } else {
                                    $version = 'latest';
                                }
                            } else {
                                $url = $plugin_v;
                            }
                        }
                    } elseif (is_array($plugin_v)) {
                        //To lowercase
                        $plugin_v = array_change_key_case($plugin_v, CASE_LOWER);

                        //Check Accept Key
                        $accept_key = array('version', 'url', 'activate');

                        //Check is assoc array
                        if (\WP_CLI_Util::is_assoc_array($plugin_v) === false) {
                            $valid->add_error(Package::_e('package', 'er_valid', array("[key]" => "plugins: { " . $plugin_slug . " { ..")));
                            break;
                        }

                        //check require Key
                        if ( ! array_key_exists('url', $plugin_v) and ! array_key_exists('version', $plugin_v)) {
                            $valid->add_error(Package::_e('package', 'require_param_plugin', array("[slug]" => $plugin_slug)));
                            break;
                        }

                        //Check Anonymous Parameter
                        foreach ($plugin_v as $k => $val) {
                            if ( ! in_array(strtolower($k), $accept_key)) {
                                $valid->add_error(Package::_e('package', 'er_unknown_param', array("[key]" => 'plugins: { ' . $plugin_slug . ': { "' . $k . '" ..')));
                                break;
                            }
                        }

                        //Check url together
                        if (array_key_exists('url', $plugin_v)) {
                            $plugin_url = filter_var($plugin_v['url'], FILTER_VALIDATE_URL);
                            if ($plugin_url === false) {
                                $valid->add_error(Package::_e('package', 'er_wrong_plugin_url', array("[key]" => $plugin_slug, "[type]" => "plugin")));
                                break;
                            } else {
                                $url = $plugin_v['url'];
                            }
                        }

                        //Check Version
                        if (array_key_exists('version', $plugin_v) and empty($url)) {
                            //Check Latest
                            if ( ! in_array($plugin_v['version'], Package::get_config('package', 'latest'))) {
                                //Check is Version number
                                if (\WP_CLI_Util::is_semver_version($plugin_v['version']) === false) {
                                    $valid->add_error(Package::_e('package', 'er_wrong_plugin_v', array("[key]" => $plugin_slug, "[type]" => "plugin")));
                                    break;
                                } else {
                                    $version = $plugin_v['version'];
                                }
                            } else {
                                $version = 'latest';
                            }
                        }

                        //Check Activate
                        if (isset($plugin_v['activate'])) {
                            $plugin_v['activate'] = \WP_CLI_Util::is_boolean($plugin_v['activate']);
                            if (is_null($plugin_v['activate'])) {
                                $valid->add_error(Package::_e('package', 'er_plugin_activate', array("[slug]" => $plugin_slug)));
                                break;
                            }
                        }
                        $activate = $plugin_v['activate'];
                    } else {
                        $valid->add_error(Package::_e('package', 'er_valid', array("[key]" => "plugins: { '" . $plugin_slug . "' ..")));
                        break;
                    }

                    //Push To List
                    $this_plugin = array(
                        'slug'     => $slug,
                        'activate' => $activate
                    );

                    //Check Url
                    if ( ! empty($url)) {
                        $this_plugin['url'] = $url;
                    } else {
                        $this_plugin['version'] = $version;
                    }

                    //Push To list
                    $plugin_list[] = $this_plugin;
                }

                //Check Plugins slug exist in Wordpress APi or exist Custom url zip file
                if ( ! $valid->is_cli_error()) {
                    $k = 0;
                    foreach ($plugin_list as $plugin) {
                        //Check is Custom Url zip file
                        if (isset($plugin['url'])) {
                            //Check Plugins Zip file Url
                            if (defined('WP_CLI_PACKAGIST_RUN_EXIST_CUSTOM_URL')) {
                                $exist_url = Version::exist_url($plugin['url'], $plugin['slug'] . " plugin", true);
                                if ($exist_url['status'] === false) {
                                    $valid->add_error($exist_url['data']);
                                    break;
                                } else {
                                    //Set Correct Url to Download Plugins zip file (if redirect)
                                    $plugin_list[$k]['url'] = $exist_url['data'];
                                }
                            }
                        } else {
                            //Check Plugins slug is exist in Wordpress API
                            $get_plugin_data = $plugin_api->get_list_plugin_versions($plugin['slug']);
                            if ($get_plugin_data['status'] === false) {
                                $valid->add_error($get_plugin_data['data']);
                                break;
                            } else {
                                //Check exist Version number
                                if (isset($plugin['version'])) {
                                    if ($plugin['version'] != "latest" and ! in_array($plugin['version'], $get_plugin_data['data'])) {
                                        $valid->add_error(Package::_e('wordpress_api', 'not_available_ver', array("[name]" => $plugin['slug'], "[type]" => "plugin", "[ver]" => $plugin['version'])));
                                        break;
                                    }
                                }
                            }
                        }
                        $k++;
                    }
                }

                //Push To sanitize return data
                if ( ! $valid->is_cli_error()) {
                    $valid->add_success($plugin_list);
                }
            }
        }

        return ($validate === true ? $valid->result() : $plugin_list);
    }

}