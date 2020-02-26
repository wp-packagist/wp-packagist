<?php

namespace WP_CLI_PACKAGIST\Package\Arguments;

use WP_CLI_PACKAGIST\Package\Package;
use WP_CLI_PACKAGIST\Package\Utility\Package_Install;

class Commands
{
    /**
     * Run Commands Parameter in WordPress Package
     *
     * @param $pkg_commands
     * @throws \WP_CLI\ExitException
     */
    public static function run_commands($pkg_commands)
    {
        # Get Base Dir
        $cwd = \WP_CLI_Util::getcwd();

        # Start Loop commands
        foreach ($pkg_commands as $command) {
            //Check is WP-CLI or Global Command
            if (isset($command['where']) and $command['where'] == "wp-cli") {
                # Show Log
                Package_Install::add_detail_log(Package::_e('package', 'run_cmd', array("[cmd]" => self::show_command_log($command['command']), "[more]" => "")));

                # Run WP-CLI
                \WP_CLI_Helper::run_command($command['command'], array('exit_error' => false));
            } else {
                # Run Global

                //Check Exist Dir
                $sanitize_dir  = \WP_CLI_Util::backslash_to_slash("/" . ltrim($command['where'], "/"));
                $complete_path = \WP_CLI_FileSystem::path_join($cwd, $sanitize_dir);
                if (is_dir($complete_path) and is_dir($complete_path)) {
                    # Show log
                    Package_Install::add_detail_log(Package::_e('package', 'run_cmd', array("[cmd]" => self::show_command_log($command['command']), "[more]" => " in '" . $command['where'] . "' path")));

                    # Run global command
                    chdir($complete_path);
                    \WP_CLI_Helper::exec($command['command']);
                    chdir($cwd);
                } else {
                    # Show Log Error directory
                    Package_Install::add_detail_log(Package::_e('package', 'er_find_dir_cmd', array("[dir]" => $command['where'], "[cmd]" => self::show_command_log($command['command']))));
                }
            }
        }
    }

    /**
     * Sanitize Command name in show log
     *
     * @param $cmd
     * @return mixed
     */
    public static function show_command_log($cmd)
    {
        $exp = explode(" ", $cmd);
        if (count($exp) <= 4) {
            return $cmd;
        } else {
            $t = "";
            for ($i = 0; $i <= 4; $i++) {
                $t .= $exp[$i] . " ";
            }
            return $t . " ..";
        }
    }

}