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

class ClientTest extends TestCase
{
    private $httpMocks;
    private $client;
    private $discovery;

    public function setUp()
    {
        $this->httpMocks = new MockPlugin();

        $httpclient = new HttpClient();
        $httpclient->addSubscriber($this->httpMocks);

        $this->client  = new Client($httpclient);
    }

    public function testApplicationRegistration()
    {
        $application = new Application(array(
            "name" => "Test Application",
        ));

        $this->httpMocks->addResponse(new Response(200, array(), <<<JSON
{
    "id": "326ee3",
    "mac_key_id": "a:02ddb3b8",
    "mac_key": "1bdaa909e7e1254d41c102775b20c605",
    "mac_algorithm": "hmac-sha-256"
}
JSON
        ));

        $this->client->registerApplication($application, "https://beberlei.tent.is");
    }
}

