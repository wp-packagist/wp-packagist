<?php

namespace test\unit;

use PHPUnit\Framework\TestCase;
use WP_CLI_PACKAGIST\Package\Package;

class HelperClassTest extends TestCase
{

    /** @test */
    public function existHelperClass()
    {
        $this->assertTrue(class_exists('WP_CLI_Helper'));
        $this->assertTrue(class_exists('WP_CLI_Util'));
        $this->assertTrue(class_exists('WP_CLI_FileSystem'));
    }

    /** @test */
    public function getConfig()
    {
        $get_config = Package::get_config('package', 'default_clone_role');
        $this->assertEquals($get_config, 'subscriber');
    }

    /** @test */
    public function checkBoolean()
    {
        $var = 'disable';
        $this->assertEquals(null,  filter_var($var, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
    }

}
