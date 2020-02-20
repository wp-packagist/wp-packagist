<?php

namespace WP_CLI_PACKAGIST\Package\Params;

use WP_CLI_PACKAGIST\Package\Package;

class Description
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
         * Set Config Global
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

        //Get description parameter
        $parameter = $pkg_array['description'];

        //Check Validation parameter
        $check = $this->sanitize_description($parameter, true);
        if ($check['status'] === false) {
            foreach ($check['data'] as $error) {
                $valid->add_error($error);
            }
        } else {
            $valid->add_success(array_shift($check['data']));
        }

        return $valid->result();
    }

    /**
     * Sanitize Package Description parameter
     *
     * @param $var
     * @param bool $validate
     * @return string|boolean|array
     * @since 1.0.0
     */
    public function sanitize_description($var, $validate = false)
    {
        //Create new validation
        $valid = new \WP_CLI_ERROR();

        //Check is Empty
        if (empty($var)) {
            $valid->add_error(Package::_e('package', 'empty_val', array("[key]" => "description")));
            //Check is array
        } elseif (is_array($var)) {
            $valid->add_error(Package::_e('package', 'is_not_string', array("[key]" => "description")));
        } else {
            //save original description
            $raw_desc = $var;

            //Strip all tags
            $var = strip_tags($var);

            //Check Contain Html
            if (\WP_CLI_Util::to_lower_string($raw_desc) != \WP_CLI_Util::to_lower_string($var)) {
                $valid->add_error(Package::_e('package', 'er_contain_html', array("[key]" => "description")));
            } else {
                //Check Max character
                if (\WP_CLI_Util::strlen($var) > $this->package_config['max_desc_ch']) {
                    $valid->add_error(Package::_e('package', 'er_max_num_ch', array("[key]" => "description", "[number]" => number_format($this->package_config['max_desc_ch']))));
                } else {
                    //Remove White Space in description
                    $var = \WP_CLI_Util::remove_whitespace_word($var);
                    $valid->add_success($var);
                }
            }
        }

        return ($validate === true ? $valid->result() : $var);
    }

}