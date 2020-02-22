<?php

namespace WP_CLI_PACKAGIST\Package\Utility;

use WP_CLI_PACKAGIST\Package\Package;


class Package_Temporary
{
    /**
     * Convert Link To file Name
     *
     * @param $path
     * @return mixed|string
     */
    private static function convertPathToFileName($path)
    {
        $path = \WP_CLI_Util::to_lower_string(\WP_CLI_Util::backslash_to_slash($path));
        return md5($path) . '--' . \WP_CLI_Util::remove_all_space(basename($path));
    }

    /**
     * Get Temporary file path
     *
     * @param $cwd
     * @return string
     */
    public static function getTemporaryFilePath($cwd = false)
    {
        if ( ! $cwd) {
            $cwd = \WP_CLI_Util::getcwd();
        }
        return \WP_CLI_FileSystem::path_join(Package::get_config('package', 'localTemp', 'path'), self::convertPathToFileName($cwd) . Package::get_config('package', 'localTemp', 'type'));
    }

    /**
     * Save Package Temporary
     *
     * @param $cwd
     * @param $pkg_array
     * @return bool
     */
    public static function saveTemporary($pkg_array, $cwd = false)
    {
        $file = self::getTemporaryFilePath($cwd);
        if (\WP_CLI_FileSystem::create_json_file($file, (array)$pkg_array)) {
            return true;
        }

        return false;
    }

    /**
     * Remove Custom Temporary File
     *
     * @param $cwd
     * @return bool
     */
    public static function removeTemporaryFile($cwd = false)
    {
        $file = self::getTemporaryFilePath($cwd);
        if (file_exists($file)) {
            if (\WP_CLI_FileSystem::remove_file($file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get Last Local Temporary
     *
     * @param $cwd
     * @return array|bool
     */
    public static function getTemporaryFile($cwd = false)
    {
        $file = self::getTemporaryFilePath($cwd);
        if (file_exists($file)) {
            $get_data = \WP_CLI_FileSystem::read_json_file($file);
            if ($get_data != false) {
                return $get_data;
            }
        }

        return array();
    }
}