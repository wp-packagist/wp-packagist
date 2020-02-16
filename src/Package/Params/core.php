<?php

namespace WP_CLI_PACKAGIST\Package\Params;

use WP_CLI_PACKAGIST\Package\Arguments\Locale;
use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Arguments\Version;
use WP_CLI_PACKAGIST\Package\Utility\install;

class core
{
    /**
     * Default Parameter
     *
     * @var array
     */
    public $params_keys = array('version', 'locale', 'network');

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
         * Set Global Config
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

        //Get core parameter
        $parameter = $pkg_array['core'];

        //Require keys
        $require_params = array('version');

        //Default value
        $default_config = Package::get_config('package', 'default');
        $default_values = array(
            'locale'  => $default_config['locale'],
            'network' => $default_config['network']
        );

        //Check is empty
        if (empty($parameter)) {
            $valid->add_error(Package::_e('package', 'empty_val', array("[key]" => "core")));
        } else {
            //check is string
            if (is_string($parameter)) {
                $valid->add_error(Package::_e('package', 'is_string', array("[key]" => "core")));
            } else {
                //Check is not Assoc array
                if (\WP_CLI_Util::is_assoc_array($parameter) === false) {
                    $valid->add_error(Package::_e('package', 'er_valid', array("[key]" => "core")));
                } else {
                    //Convert to lowercase key
                    $parameter = array_change_key_case($parameter, CASE_LOWER);

                    //Check require key
                    $check_require_key = \WP_CLI_Util::check_require_array($parameter, $require_params, false);
                    if ($check_require_key['status'] === false) {
                        foreach ($check_require_key['data'] as $key) {
                            $valid->add_error(Package::_e('package', 'not_exist_key', array("[require]" => $key, "[key]" => "core: { .. ")));
                            break;
                        }
                    }

                    //Check Anonymous Parameter
                    foreach ($parameter as $k => $val) {
                        if ( ! in_array(strtolower($k), $this->params_keys)) {
                            $valid->add_error(Package::_e('package', 'er_unknown_param', array("[key]" => 'core: { "' . $k . '" ..')));
                        }
                    }

                    //Push default value if not exist
                    foreach ($default_values as $k => $v) {
                        if ( ! array_key_exists($k, $parameter)) {
                            $parameter[$k] = $v;
                        }
                    }

                    //Validation Separate Parameter
                    if ( ! $valid->is_cli_error()) {
                        //Create return object
                        $return = array();

                        //Check validation every parameter
                        foreach ($this->params_keys as $keys) {
                            //Sanitize params
                            $check = $this->{'sanitize_' . $keys}($parameter[$keys], true);
                            if ($check['status'] === false) {
                                foreach ($check['data'] as $error) {
                                    $valid->add_error($error);
                                    break;
                                }
                            } else {
                                //Get Sanitize Data
                                $return[$keys] = array_shift($check['data']);
                            }
                        }

                        // Check Release in custom locale and version ( this item disable in Update pack )
                        if ( ! $valid->is_cli_error() and \WP_CLI_PACKAGIST\Package\Arguments\Core::is_installed_wordpress() === false) {
                            $check_dl_link = Version::check_download_url($return['version'], $return['locale']);
                            if ($check_dl_link['status'] === false) {
                                $valid->add_error(array_shift($check_dl_link['data']));
                            }
                        }

                        //Push To sanitize return data
                        $valid->add_success($return);
                    }
                }
            }
        }

        return $valid->result();
    }

    /**
     * Sanitize Package network parameter
     *
     * @param $var
     * @param bool $validate
     * @return string|boolean|array
     * @since 1.0.0
     */
    public function sanitize_network($var, $validate = false)
    {
        //Create new validation
        $valid = new \WP_CLI_ERROR();

        //Check if Not Network WordPress
        if ((is_bool($var) and $var === false) || (is_array($var) and ! empty($var))) {
            //Check if is Network
            if (is_array($var)) {
                //Check is assoc array
                if (\WP_CLI_Util::is_assoc_array($var)) {
                    //Lower case array key
                    $var = array_change_key_case($var, CASE_LOWER);

                    //All accept Parameter
                    $accept_params = array('subdomain', 'sites');

                    //Require Key
                    $require_key = array('subdomain');

                    //Not empty if exist
                    $not_empty = array('sites');

                    //Check Require Key
                    $check_require_key = \WP_CLI_Util::check_require_array($var, $require_key, true);
                    if ($check_require_key['status'] === false) {
                        foreach ($check_require_key['data'] as $key) {
                            $valid->add_error(Package::_e('package', 'not_exist_key', array("[require]" => $key, "[key]" => "core: { network: { ..")));
                        }
                    }

                    //Check Anonymous Parameter
                    foreach ($var as $k => $val) {
                        if ( ! in_array(strtolower($k), $accept_params)) {
                            $valid->add_error(Package::_e('package', 'er_unknown_param', array("[key]" => 'core: { network: { "' . $k . '" ..')));
                        }
                    }

                    //Check Not Empty key
                    foreach ($not_empty as $k) {
                        if (array_key_exists($k, $var) and empty($var[$k])) {
                            $valid->add_error(Package::_e('package', 'empty_val', array("[key]" => 'core: { network: { "' . $k . '" ..')));
                        }
                    }

                    // Validate Every item
                    if ( ! $valid->is_cli_error()) {
                        //Validate Sub-domain key
                        if ( ! is_bool($var['subdomain'])) {
                            $valid->add_error(Package::_e('package', 'is_boolean', array("[key]" => "core: { network: { subdomain: ..")));
                        }

                        //Validate Sites
                        if ( ! $valid->is_cli_error()) {
                            //Check Exist Sites List
                            if (array_key_exists('sites', $var)) {
                                //Check Site Key is array without assoc
                                if (is_array($var['sites']) and \WP_CLI_Util::is_assoc_array($var['sites']) === false) {
                                    //Require Keys in every sites
                                    $require_keys = array('slug', 'public');

                                    //All Accept Key
                                    $accept_key = array('slug', 'public', 'email', 'title');

                                    //Save All Slug list for checking duplicate
                                    $slug_list = array();

                                    //Check Require in every sites
                                    for ($x = 0; $x < count($var['sites']); $x++) {
                                        //Sanitize lower case Keys
                                        $var['sites'][$x] = array_change_key_case($var['sites'][$x], CASE_LOWER);

                                        //Check Require key
                                        $check_require_key = \WP_CLI_Util::check_require_array($var['sites'][$x], $require_keys, true);
                                        if ($check_require_key['status'] === false) {
                                            foreach ($check_require_key['data'] as $key) {
                                                $valid->add_error(Package::_e('package', 'not_exist_key', array("[require]" => $key, "[key]" => "core: { network: { sites: { [" . ($x + 1) . "]")));
                                                break;
                                            }
                                        }

                                        //Check Anonymous Parameter
                                        foreach ($var['sites'][$x] as $k => $v) {
                                            if ( ! in_array(strtolower($k), $accept_key)) {
                                                $valid->add_error(Package::_e('package', 'er_unknown_param', array("[key]" => "core: { network: { sites: { [" . ($x + 1) . "]['" . $k . "']")));
                                                break;
                                            }
                                        }

                                        //Check Not Empty data
                                        foreach (array('slug', 'title', 'email') as $k) {
                                            if (is_array($var['sites'][$x]) and array_key_exists($k, $var['sites'][$x]) === true) {
                                                if ($var['sites'][$x][$k] == "") {
                                                    $valid->add_error(Package::_e('package', 'empty_val', array("[key]" => "core: { network: { sites: { [" . ($x + 1) . "]['" . $k . "']")));
                                                    break;
                                                }
                                            }
                                        }

                                        // Check forbidden name for Slug
                                        $forbidden_slug_name = Package::get_config('package', 'forbidden_blog_slug');
                                        $blog_slug           = trim($var['sites'][$x]['slug']);
                                        if (in_array($blog_slug, $forbidden_slug_name)) {
                                            $valid->add_error(Package::_e('package', 'mu_er_slug', array("[slug]" => $blog_slug)));
                                            break;
                                        }

                                        //Check Validation Data
                                        if ( ! $valid->is_cli_error()) {
                                            //Get user original data
                                            $user_raw = $var['sites'][$x];

                                            //Check Valid Slug
                                            $site_slug = preg_replace('/[^a-zA-Z0-9-]/', '', $user_raw['slug']);
                                            if (\WP_CLI_Util::to_lower_string($site_slug) == \WP_CLI_Util::to_lower_string(($user_raw['slug'])) and ! empty($site_slug)) {
                                                //Sanitize slug Data
                                                $var['sites'][$x]['slug'] = \WP_CLI_Util::to_lower_string($site_slug);

                                                //Check Duplicate Slug
                                                if (in_array($var['sites'][$x]['slug'], $slug_list)) {
                                                    $valid->add_error(Package::_e('package', 'nv_duplicate', array("[key]" => "slug", "[array]" => "core: { network: { sites: { ..")));
                                                    break;
                                                } else {
                                                    $slug_list[] = $var['sites'][$x]['slug'];
                                                }

                                                //Check Valid Public or private key
                                                if (is_bool($var['sites'][$x]['public'])) {
                                                    //Check Validation email
                                                    if (isset($var['sites'][$x]['email'])) {
                                                        $var['sites'][$x]['email'] = filter_var(\WP_CLI_Util::to_lower_string($var['sites'][$x]['email']), FILTER_VALIDATE_EMAIL);
                                                        if ( ! $var['sites'][$x]['email']) {
                                                            $valid->add_error(Package::_e('package', 'er_valid', array("[key]" => "core: { network: { sites: { [" . ($x + 1) . "]['email']")));
                                                            break;
                                                        }
                                                    }

                                                    //sanitize title
                                                    if (isset($var['sites'][$x]['title'])) {
                                                        $var['sites'][$x]['title'] = strip_tags($var['sites'][$x]['title']);
                                                    }
                                                } else {
                                                    $valid->add_error(Package::_e('package', 'er_valid', array("[key]" => "core: { network: { sites: { [" . ($x + 1) . "]['public']")));
                                                }
                                            } else {
                                                $valid->add_error(Package::_e('package', 'er_valid', array("[key]" => "core: { network: { sites: { [" . ($x + 1) . "]['slug']")));
                                            }
                                        }
                                    }
                                } else {
                                    $valid->add_error(Package::_e('package', 'er_valid', array("[key]" => "core: { network: { sites: ..")));
                                }
                            }
                        }

                        //Return sanitize output
                        if ( ! $valid->is_cli_error()) {
                            $valid->add_success($var);
                        }
                    } //Cli Error
                } else {
                    $valid->add_error(Package::_e('package', 'er_valid', array("[key]" => "network")));
                }
            } else {
                //Push false To Success return
                $valid->add_success($var);
            }
        } else {
            $valid->add_error(Package::_e('package', 'er_valid', array("[key]" => "network")));
        }

        return ($validate === true ? $valid->result() : $var);
    }

    /**
     * Sanitize Version
     *
     * @param $text
     * @param bool $validate
     * @return string|boolean|array
     */
    public function sanitize_version($text, $validate = false)
    {
        //To Lowercase
        $text = \WP_CLI_Util::to_lower_string($text);

        //Default value
        $return = $this->package_config['default']['version'];

        //Create Object Validation
        $valid = new \WP_CLI_ERROR();

        //Check Version
        switch ($text) {
            case "master":
            case "latest":
            case "last":
            case "*":
                $return = "latest";
                break;
            case "nightly":
                $return = "nightly";
                break;
            default :
                if (\WP_CLI_Util::is_semver_version($text)) {
                    //Check Exist Version
                    $exist_version = Version::get_wordpress_version($text);
                    if ($exist_version['status'] === false) {
                        $valid->add_error($exist_version['data']);
                    } else {
                        $return = $text;
                    }
                } else {
                    $valid->add_error(Package::_e('package', 'version_standard'));
                }
        }

        //push to success if not error
        if ( ! $valid->is_cli_error()) {
            $valid->add_success($return);
        }

        return ($validate === true ? $valid->result() : $return);
    }

    /**
     * Sanitize Locale
     *
     * @param $text
     * @param bool $validate
     * @return string|array
     */
    public function sanitize_locale($text, $validate = false)
    {
        //Default value
        $return = $this->package_config['default']['locale'];

        //Create Object Validation
        $valid = new \WP_CLI_ERROR();

        //Check Not Valid
        if ( ! empty(trim($text))) {
            //Check Locale name
            $check_locale = Locale::get_wordpress_locale($text);
            if ($check_locale['status'] === false) {
                $valid->add_error($check_locale['data']);
            } else {
                //if locale is valid set to return value
                $return = $text;
            }
        }

        //push to success if not error
        if ( ! $valid->is_cli_error()) {
            $valid->add_success($return);
        }

        return ($validate === true ? $valid->result() : $return);
    }

    /**
     * Create Default Value for init command
     *
     * @param $args
     * @param bool $validate
     * @return mixed
     */
    public function init($args, $validate = false)
    {
        //Create Empty object
        $array = array();
        $error = array();

        //Get Params Key
        foreach ($this->params_keys as $key) {
            //Check Default Value
            switch ($key) {
                case "version":
                    $default = $this->sanitize_version($this->package_config['default']['version'], $validate);
                    break;
                case "locale":
                    $default = $this->sanitize_locale(\WP_CLI_Helper::get_flag_value($args, 'locale', $this->package_config['default']['locale']), $validate);
                    break;
                case "network":
                    if ( ! isset($args['multisite'])) {
                        $default = $this->package_config['default']['network'];
                    } else {
                        $default = $this->sanitize_network(array('subdomain' => false), $validate);
                    }
                    break;
            }

            if (isset($default)) {
                $array[$key] = $default;

                //Check if validate
                if ($validate and is_array($default) and $default['status'] === false) {
                    foreach ($default['data'] as $text_error) {
                        $error[] = $text_error;
                    }
                }
            }
        }

        return ($validate === true ? $error : $array);
    }

    /**
     * install Command
     *
     * @param $pkg_array
     * @param array $args
     * @return array
     */
    public function install($pkg_array, $args = array())
    {
        //Prepare Step
        $step     = $args['step'];
        $all_step = $args['all_step'];

        //Download WordPress
        $version = $pkg_array['core']['version'];
        install::install_log($step, $all_step, Version::get_log_download_wordpress($version));
        \WP_CLI_Helper::pl_wait_start();
        Version::download_wordpress($version);
        \WP_CLI_Helper::pl_wait_end();
        $step++;

        return array('status' => true, 'step' => $step);
    }

    /**
     * WordPress Package Update
     *
     * @param $pkg_array
     */
    public function update($pkg_array)
    {
        # Update WordPress Version
        Version::update_version($pkg_array);

        # Update WordPress Locale
        Locale::update_language($pkg_array);

        # Update WordPress Multi-Site
        \WP_CLI_PACKAGIST\Package\Arguments\Core::update_network($pkg_array);
    }

}