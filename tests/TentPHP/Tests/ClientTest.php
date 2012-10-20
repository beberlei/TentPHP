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
    private $httpMocks;
    private $client;
    private $discovery;
    private $appRegistration;

    public function setUp()
    {
        $this->httpMocks = new MockPlugin();
        $this->discovery = $this->mock('TentPHP\Server\EntityDiscovery');
        $this->appRegistration = $this->mock('TentPHP\Server\AppRegistration');

        $httpclient = new HttpClient();
        $httpclient->addSubscriber($this->httpMocks);

        $this->client  = new Client($httpclient, $this->discovery, $this->appRegistration);
    }

    public function testApplicationRegistration()
    {
        $application = new Application(array(
            "name" => "Test Application",
        ));
        $config = new ApplicationConfig(array(
            'id' => '326ee3',
        ));

        $this->discovery->shouldReceive('discoverServers')
                        ->with('https://beberlei.tent.is')
                        ->andReturn(array('https://tent.is/tent'));

        $this->appRegistration->shouldReceive('register')
                              ->with($application, 'https://tent.is/tent')
                              ->andReturn($config);

        $this->client->registerApplication($application, "https://beberlei.tent.is");
    }
}

