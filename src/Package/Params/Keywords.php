<?php

namespace WP_CLI_PACKAGIST\Package\Params;

use WP_CLI_PACKAGIST\Package\Package;

class Keywords
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

        //Get keywords parameter
        $parameter = $pkg_array['keywords'];

        //Check Validation parameter
        $check = $this->sanitize_keywords($parameter, true);
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
     * Sanitize Package Keywords parameter
     *
     * @param $var
     * @param bool $validate
     * @return string|boolean|array
     * @since 1.0.0
     */
    public function sanitize_keywords($var, $validate = false)
    {
        //Create new validation
        $valid = new \WP_CLI_ERROR();

        //Check is Empty
        if (empty(array_filter($var, 'mb_strlen'))) {
            $valid->add_error(Package::_e('package', 'empty_val', array("[key]" => "keywords")));
            //Check is string
        } elseif (is_string($var)) {
            $valid->add_error(Package::_e('package', 'is_string', array("[key]" => "keywords")));
        } else {
            //Check is not multi array
            if (\WP_CLI_Util::is_assoc_array($var)) {
                $valid->add_error(Package::_e('package', 'er_valid', array("[key]" => "keywords")));
            } else {
                //Check Count Keywords
                if (count($var) > $this->package_config['max_num_keywords']) {
                    $valid->add_error(Package::_e('package', 'er_max_item', array("[key]" => "keywords", "[number]" => $this->package_config['max_num_keywords'])));
                } else {
                    $val_list = array();
                    foreach ($var as $item) {
                        $string = strtolower($item);

                        //Check Number Character
                        if (\WP_CLI_Util::strlen($string) > $this->package_config['max_keywords_ch']) {
                            $valid->add_error(Package::_e('package', 'er_max_num_ch', array("[key]" => "keywords: [ '" . \WP_CLI_Util::substr($item, 15) . "' ..", "[number]" => $this->package_config['max_keywords_ch'])));
                            break;
                        }

                        //Check Special character
                        if (preg_match('/[\'^£$%&*()}{@#~?><>,|=+¬]/', $string)) {
                            $valid->add_error(Package::_e('package', 'er_special_ch', array("[key]" => "keywords: [ '" . $item . "' ..")));
                            break;
                        }

                        //Check White Space
                        if (preg_match('/\s/', $item)) {
                            $valid->add_error(Package::_e('package', 'er_contain_space', array("[key]" => "keywords: [ '" . $item . "' ..")));
                            break;
                        }

                        //if duplicate value
                        if (in_array(\WP_CLI_Util::remove_whitespace_word($string), $val_list)) {
                            $valid->add_error(Package::_e('package', 'nv_duplicate', array("[key]" => $string, "[array]" => "keywords")));
                            break;
                        } else {
                            $val_list[] = \WP_CLI_Util::remove_whitespace_word($string);
                        }
                    }

                    //Sanitize Keywords List
                    if ( ! $valid->is_cli_error()) {
                        $var = array_map(function ($value) {
                            //Trim Word
                            $return = \WP_CLI_Util::to_lower_string($value);
                            //Convert _ to -
                            $return = str_ireplace("_", "-", $return);
                            //Sanitize Keywords
                            $return = self::_prepare_keywords_slug($return);
                            return $return;
                        }, $var);

                        //push to success return
                        $valid->add_success($var);
                    }
                }
            }
        }

        return ($validate === true ? $valid->result() : $var);
    }

    /**
     * Prepare Keywords for Convert White Space to Dash
     *
     * @param $text
     * @return bool|null|string|string[]
     */
    public static function _prepare_keywords_slug($text)
    {
        // replace non letter or digits by -
        $text = str_ireplace(' ', '-', $text);
        // trim
        $text = trim($text, '-');
        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);
        if (empty($text)) {
            return false;
        }
        return $text;
    }

}