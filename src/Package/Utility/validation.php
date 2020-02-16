<?php

namespace WP_CLI_PACKAGIST\Package\Utility;

use WP_CLI_PACKAGIST\Package\Package;

/**
 * WordPress Package Validation
 */
class validation extends Package
{
    /**
     * Check WordPress Package Validation
     *
     * @param bool $log
     * @return array
     */
    public function validation($log = true)
    {
        //Create Object Validation
        $valid = array('status' => true, 'data' => array());

        //Create Empty array for Sanitize data
        $sanitize_data = array();

        //Get Wordpress Package Data
        $exist_pkg = $this->get_package_data();
        if ($exist_pkg['status'] === false) {
            //Push Error to List
            $valid['data'][] = $exist_pkg['data'];
        } else {
            //Get Package Data
            $pkg_array = $exist_pkg['data'];

            //Convert All keys to lowercase
            $pkg_array = array_change_key_case($pkg_array, CASE_LOWER);

            //Check Primary WordPress Package Key
            $primary_keys = $this->check_primary_keys($pkg_array);
            if ($primary_keys['status'] === false) {
                foreach ($primary_keys['data'] as $error) {
                    $valid['data'][] = $error;
                }
            } else {
                //validation every key params
                foreach ($this->package_config['params'] as $class_name) {
                    //Check Exist pkg Key
                    if (array_key_exists($class_name, $pkg_array)) {
                        //get Class name
                        $class = $this->package_config['params_namespace'] . $class_name;

                        //Create new Obj from class
                        $obj = new $class();

                        //check validation method exist in class
                        if (\WP_CLI_Util::search_method_from_class($obj, 'validation')) {
                            //Check list of error
                            $error = $obj->validation($pkg_array);
                            if ($error['status'] === false) {
                                foreach ($error['data'] as $text) {
                                    $valid['data'][] = $text;
                                }
                            } else {
                                //Push Sanitize data
                                if (is_string($error['data'])) {
                                    $sanitize_data[$class_name] = $error['data'];
                                } else {
                                    $sanitize_data[$class_name] = array_shift($error['data']);
                                }
                            }
                        }
                    }

                    //Check Exist Error
                    if (count($valid['data']) > 0) {
                        break;
                    }
                }
            }
        }

        //Check number validation error
        if (count($valid['data']) > 0) {
            $valid['status'] = false;
        }

        //Show Log in Cli
        if ($log and $valid['status'] === false) {
            //Remove please wait
            if (defined('WP_CLI_PLEASE_WAIT_LOG')) {
                \WP_CLI_Helper::pl_wait_end();
            }

            //Check has error in validation
            \WP_CLI_Helper::line(\WP_CLI_Helper::color("Error: ", "R"));
            foreach ($valid['data'] as $text_error) {
                \WP_CLI_Helper::line("  - " . $text_error);
            }
            \WP_CLI_Helper::br();

            return $valid;
        } else {
            //Get Package Data if not exist error
            if ($valid['status'] === true and isset($pkg_array)) {
                $valid['data'] = $sanitize_data;
            }

            return $valid;
        }
    }

    /**
     * Check Primary WordPress Package Key
     *
     * @param $pkg_data
     * @return array
     */
    public function check_primary_keys($pkg_data)
    {
        //Create new validation
        $valid = new \WP_CLI_ERROR();

        //Check Primary keys exist
        $check_require_key = \WP_CLI_Util::check_require_array($pkg_data, $this->primary_keys, true);
        if ($check_require_key['status'] === false) {
            foreach ($check_require_key['data'] as $key) {
                $valid->add_error(Package::_e('package', 'not_exist_key', array("[require]" => $key, "[key]" => "WordPress Package file", "`" => "")));
            }
        }

        //Check Anonymous Parameter
        $pkg_params_list = $this->package_config['params'];
        foreach ($pkg_data as $k => $v) {
            if ( ! in_array($k, $pkg_params_list)) {
                $valid->add_error(Package::_e('package', 'er_unknown_param', array("[key]" => $k)));
            }
        }

        return $valid->result();
    }

}