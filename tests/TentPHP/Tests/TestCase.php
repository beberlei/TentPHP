<?php

namespace TentPHP\Tests;

use Mockery;
use PHPUnit_Framework_TestCase;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    public function mock()
    {
        $args = func_get_args();
        return call_user_func_array(array('Mockery', 'mock'), $args);
    }

    public function tearDown()
    {
        Mockery::close();
    }
}

