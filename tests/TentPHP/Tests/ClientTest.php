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
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Client as HttpClient;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    public function testApplicationRegistration()
    {
        $client = new Client(new HttpClient);
        $application = new Application(array(
            "name" => "Test Application",
        ));

        $config = $client->registerApplication($application, "https://beberlei.tent.is");

        $this->assertInstanceOf('TentPHP\ApplicationConfig', $config);
    }


    public function testDiscoverEntityServers()
    {
        $plugin = new MockPlugin();
        $plugin->addResponse(new Response(200, array(
            'Link' => '<https://beberlei.tent.is/tent/profile>; rel="https://tent.io/rels/profile"'
        ), ''));
        $plugin->addResponse(new Response(200, array(), <<<JSON
{
    "https://tent.io/types/info/basic/v0.1.0":{
        "name":"",
        "avatar_url":"",
        "birthdate":"",
        "location":"",
        "gender":"male",
        "bio":"",
        "permissions":{"public":true}
    },
    "https://tent.io/types/info/core/v0.1.0":{
        "entity":"https://beberlei.tent.is",
        "licenses":[],
        "servers":["https://beberlei.tent.is/tent", "https://tent.beberlei.de/tent"],
        "permissions":{"public":true}
    }
}
JSON
        ));

        $httpclient = new HttpClient();
        $httpclient->addSubscriber($plugin);

        $client  = new Client($httpclient);
        $servers = $client->discoverServers("https://beberlei.tent.is");

        $this->assertInternalType('array', $servers);
        $this->assertEquals(array("https://beberlei.tent.is/tent", "https://tent.beberlei.de/tent"), $servers);
    }
}

