<?php
/**
 * PHP Tent Client (c) Benjamin Eberlei
 *
 * LICENSE
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace TentPHP\Tests;

use TentPHP\Client;
use TentPHP\Application;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    public function testApplicationRegistration()
    {
        $client = new Client();
        $application = new Application(array(
            "name" => "Test Application",
        ));

        $config = $client->registerApplication($application);

        $this->assertInstanceOf('TentPHP\ApplicationConfig', $config);
    }
}

