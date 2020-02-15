<?php

namespace WP_CLI_PACKAGIST\Package\Utility;

use WP_CLI_PACKAGIST\Package\Package;

class update extends Package
{
    /**
     * Update WordPress Package
     *
     * @param $pkg_array
     * @throws \Exception
     */
    public function run($pkg_array)
    {
        # Remove please wait
        if (defined('WP_CLI_PLEASE_WAIT_LOG')) {
            \WP_CLI_Helper::pl_wait_end();
        }

        # Set Timer for Process
        $start_time = time();

        # Run Params
        foreach (Package::get_config('package', 'params') as $class_name) {
            # Check Exist pkg Key
            if (array_key_exists($class_name, $pkg_array)) {
                # get Class name
                $class = $this->package_config['params_namespace'] . $class_name;

                # Create new Obj from class
                $obj = new $class();

                # check validation method exist in class
                if (\WP_CLI_Util::search_method_from_class($obj, 'update')) {
                    # Run install Method
                    $obj->update($pkg_array);
                }
            }
        }

        # Save Package LocalTemp
        temp::save_temp(\WP_CLI_Util::getcwd(), $pkg_array);

        # Success Process
        if (defined('WP_CLI_APP_PACKAGE_UPDATE_LOG')) {
            \WP_CLI_Helper::success(Package::_e('package', 'success_update') . ' ' . Package::_e('config', 'process_time', array("[time]" => \WP_CLI_Helper::process_time($start_time))));
        } else {
            \WP_CLI_Helper::log(Package::_e('package', 'not_change_pkg'));
        }
    }

}