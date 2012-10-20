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
use TentPHP\ApplicationConfig;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Client as HttpClient;

class ClientTest extends TestCase
{
    public function testGetLoginUrlUnknownServerRegistersApplication()
    {
        $app    = new Application(array("name" => "Hello World!"));
        $client = new Client($app);
        $url    = $client->getLoginUrl('https://beberlei.tent.is');
    }
}

