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
        return md5(\WP_CLI_Util::to_lower_string(\WP_CLI_Util::backslash_to_slash($path)));
    }

    /**
     * Get WordPress Lock File Path
     *
     * @param $cwd
     * @return string
     */
    public static function getLockPath($cwd = false)
    {
        if ( ! $cwd) {
            $cwd = \WP_CLI_Util::getcwd();
        }
        return \WP_CLI_FileSystem::path_join($cwd, Package::get_config('package', 'lock'));
    }

    /**
     * Get Temporary Json File Path
     *
     * @param $cwd
     * @return string
     */
    public static function getTemporaryJson($cwd = false)
    {
        if ( ! $cwd) {
            $cwd = \WP_CLI_Util::getcwd();
        }
        return \WP_CLI_FileSystem::path_join(Package::get_config('package', 'localTemp', 'path'), self::convertPathToFileName($cwd) . Package::get_config('package', 'localTemp', 'type'));
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

        // First Check Temporary
        $Temporary = self::getTemporaryJson($cwd);

        // Second Check WordPress.lock
        if ( ! file_exists($Temporary)) {
            $Temporary = self::getLockPath($cwd);
        }

        return $Temporary;
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
        $Json_file  = self::getTemporaryJson($cwd);
        $Lock_file  = self::getLockPath($cwd);
        $_save_Json = \WP_CLI_FileSystem::create_json_file($Json_file, (array)$pkg_array);
        $_save_Lock =\WP_CLI_FileSystem::create_json_file($Lock_file, (array)$pkg_array);

        return ($_save_Json && $_save_Lock);
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