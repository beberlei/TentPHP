<?php

namespace TentPHP\Tests\Server;

use TentPHP\Tests\TestCase;
use TentPHP\Application;
use TentPHP\Server\AppRegistration;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Client as HttpClient;

class AppRegistrationTest extends TestCase
{
    private $httpMocks;

    public function setUp()
    {
        $this->httpMocks = new MockPlugin();

        $httpclient = new HttpClient();
        $httpclient->addSubscriber($this->httpMocks);

        $this->appRegistration  = new AppRegistration($httpclient);
    }

    public function testRegister()
    {
        $this->httpMocks->addResponse(new Response(200, array(), <<<JSON
{
    "id": "326ee3",
    "mac_key_id": "a:02ddb3b8",
    "mac_key": "1bdaa909e7e1254d41c102775b20c605",
    "mac_algorithm": "hmac-sha-256"
}
JSON
        ));

        $application = new Application(array(
            "name" => "Test Application",
        ));

        $config = $this->appRegistration->register($application, 'https://tent.is/tent');

        $this->assertInstanceOf('TentPHP\ApplicationConfig', $config);
        $this->assertEquals('326ee3', $config->getApplicationId());
        $this->assertEquals('a:02ddb3b8', $config->getMacKeyId());
        $this->assertEquals('1bdaa909e7e1254d41c102775b20c605', $config->getMacKey());
        $this->assertEquals('hmac-sha-256', $config->getMacAlgorithm());
    }
}

