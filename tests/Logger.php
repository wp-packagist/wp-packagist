<?php

/**
 * PHPUnit logger
 */
class Logger
{

    /**
     * @param string $message Message to write.
     *
     * @return string
     */
    public function info($message)
    {
        print $message;
    }

    /**
     * @param $message
     *
     * @return mixed
     */
    public function success($message)
    {
        print $message;
    }

    /**
     * @param $message
     *
     * @return mixed
     */
    public function warning($message)
    {
        print $message;
    }

    /**
     * @param $message
     *
     * @return mixed
     */
    public function error($message)
    {
        print $message;
    }

    /**
     * @param $message_lines
     *
     * @return string
     */
    public function error_multi_line($message_lines)
    {
        $message = implode("\n", $message_lines);

        print $message;
    }
}
