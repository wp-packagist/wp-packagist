<?php

namespace WP_CLI_PACKAGIST\Package\Utility;

use WP_CLI_PACKAGIST\Package\Package;


class temp
{
    /**
     * Convert Link To file Name
     *
     * @param $path
     * @return mixed|string
     */
    private static function convert_path_to_file($path)
    {
        $path = \WP_CLI_Util::to_lower_string(\WP_CLI_Util::backslash_to_slash($path));
        $path = str_ireplace("/", "--", $path);
        $path = str_ireplace("?", "&&", $path);
        $path = str_ireplace(":", "++", $path);

        return $path;
    }

    /**
     * Get Temp file
     *
     * @param $cwd
     * @return string
     */
    public static function get_temp_file_name($cwd)
    {
        return \WP_CLI_FileSystem::path_join(Package::get_config('package', 'localTemp', 'path'), self::convert_path_to_file($cwd) . Package::get_config('package', 'localTemp', 'type'));
    }

    /**
     * Save Package Temp
     *
     * @param $cwd
     * @param $pkg_array
     * @return bool
     */
    public static function save_temp($cwd, $pkg_array)
    {
        self::clear_temp(); # Remove Expire temp
        $file      = self::get_temp_file_name($cwd);
        $pkg_array = self::do_hook_package($pkg_array);
        if (\WP_CLI_FileSystem::create_json_file($file, $pkg_array)) {
            return true;
        }

        return false;
    }

    /**
     * Remove Custom Temp File
     *
     * @param $cwd
     * @return bool
     */
    public static function remove_temp_file($cwd)
    {
        $file = self::get_temp_file_name($cwd);
        if (file_exists($file)) {
            if (\WP_CLI_FileSystem::remove_file($file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove LocalTemp
     *
     * @param bool $force
     */
    public static function clear_temp($force = false)
    {
        $localPath = Package::get_config('package', 'localTemp', 'path');
        if (realpath($localPath)) {
            $list_file = \WP_CLI_FileSystem::get_dir_contents($localPath);
            foreach ($list_file as $file) {
                $file = $file . Package::get_config('package', 'localTemp', 'name');
                if ($force === true || \WP_CLI_FileSystem::check_file_age($file, Package::get_config('package', 'localTemp', 'age'))) {
                    \WP_CLI_FileSystem::remove_file($file);
                }
            }
        }
    }

    /**
     * Get Last LocalTemp
     *
     * @param $cwd
     * @return array|bool
     */
    public static function get_temp($cwd)
    {
        self::clear_temp(); # Remove Expire Temp
        $base_file = self::get_temp_file_name($cwd);
        $list      = \WP_CLI_FileSystem::get_dir_contents(Package::get_config('package', 'localTemp', 'path'));
        foreach ($list as $file_path) {
            $file_path = $file_path . Package::get_config('package', 'localTemp', 'name');
            if (\WP_CLI_FileSystem::normalize_path($file_path) == \WP_CLI_FileSystem::normalize_path($base_file)) {
                $get_data = \WP_CLI_FileSystem::read_json_file($file_path);
                if ($get_data != false) {
                    return $get_data;
                }
            }
        }

        return false;
    }

    /**
     * Add/Remove Data From WordPress Package
     *
     * @param $pkg_array
     * @return mixed
     */
    public static function do_hook_package($pkg_array)
    {
        return $pkg_array;
    }
}