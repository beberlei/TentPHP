<?php

namespace TentPHP\Tests\Server;

use TentPHP\Server\EntityDiscovery;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Client as HttpClient;

class EntityDiscoveryTest extends \PHPUnit_Framework_TestCase
{
    private $httpMocks;
    private $client;

    public function setUp()
    {
        $this->httpMocks = new MockPlugin();

        $httpclient = new HttpClient();
        $httpclient->addSubscriber($this->httpMocks);

        $this->discovery  = new EntityDiscovery($httpclient);
    }

    public function testDiscoverEntityServers()
    {
        $this->httpMocks->addResponse(new Response(200, array(
            'Link' => '<https://beberlei.tent.is/tent/profile>; rel="https://tent.io/rels/profile"'
        ), ''));
        $this->httpMocks->addResponse(new Response(200, array(), <<<JSON
{
    "https://tent.io/types/info/core/v0.1.0":{
        "servers":["https://beberlei.tent.is/tent", "https://tent.beberlei.de/tent"]
    }
}
JSON
        ));

        $servers = $this->discovery->discoverServers("https://beberlei.tent.is");

        $this->assertInternalType('array', $servers);
        $this->assertEquals(array("https://beberlei.tent.is/tent", "https://tent.beberlei.de/tent"), $servers);
    }

    public function testDiscoverServersMultipleProfiles()
    {
        $this->httpMocks->addResponse(new Response(200, array(
            'Link' => array(
                '<https://beberlei.tent.is/tent/profile>; rel="https://tent.io/rels/profile"',
                '<https://beberlei.tent.is/tent/profile>; rel="https://tent.io/rels/profile"'
            )
        ), ''));
        $this->httpMocks->addResponse(new Response(200, array(), <<<JSON
{
    "https://tent.io/types/info/core/v0.1.0":{
        "servers":["https://beberlei.tent.is/tent"]
    }
}
JSON
        ));
        $this->httpMocks->addResponse(new Response(200, array(), <<<JSON
{
    "https://tent.io/types/info/core/v0.1.0":{
        "servers":["https://tent.beberlei.de/tent"]
    }
}
JSON
        ));

        $servers = $this->discovery->discoverServers("https://beberlei.tent.is");

        $this->assertInternalType('array', $servers);
        $this->assertEquals(array("https://beberlei.tent.is/tent", "https://tent.beberlei.de/tent"), $servers);
    }

    public function testDiscoverServersNoLink()
    {
        $this->httpMocks->addResponse(new Response(200));

        $this->setExpectedException('TentPHP\Exception\EntityNotFoundException', 'No links found when querying the entity url.');
        $servers = $this->discovery->discoverServers("https://beberlei.tent.is");
    }

    public function testDiscoverServersNoProfileLink()
    {
        $this->httpMocks->addResponse(new Response(200, array(
            'Link' => 'foo',
        )));

        $this->setExpectedException('TentPHP\Exception\EntityNotFoundException', 'No profile links found when querying the entity url.');
        $servers = $this->discovery->discoverServers("https://beberlei.tent.is");
    }

    public function testDiscoverServerInvalidResponse()
    {
        $this->httpMocks->addResponse(new Response(404, array()));

        $this->setExpectedException('TentPHP\Exception\EntityNotFoundException', 'Unsuccessful response querying the entity url for a profile link.');
        $servers = $this->discovery->discoverServers("https://beberlei.tent.is");
    }

    public function testDiscoverServersProfileInvalidResponse()
    {
        $this->httpMocks->addResponse(new Response(200, array(
            'Link' => '<https://beberlei.tent.is/tent/profile>; rel="https://tent.io/rels/profile"'
        ), ''));
        $this->httpMocks->addResponse(new Response(404, array()));

        $this->setExpectedException('TentPHP\Exception\EntityNotFoundException', 'Unsuccessful response querying for profile https://beberlei.tent.is/tent/profile');
        $servers = $this->discovery->discoverServers("https://beberlei.tent.is");
    }

    public function testDiscoverServersNoServersKeyFound()
    {
        $this->httpMocks->addResponse(new Response(200, array(
            'Link' => '<https://beberlei.tent.is/tent/profile>; rel="https://tent.io/rels/profile"'
        ), ''));
        $this->httpMocks->addResponse(new Response(200, array(), <<<JSON
{
    "https://tent.io/types/info/core/v0.1.0":{}
}
JSON
        ));

        $this->setExpectedException('TentPHP\Exception\EntityNotFoundException', 'Incomplete response querying for profile https://beberlei.tent.is/tent/profile. No servers key found in tent core info type.');
        $servers = $this->discovery->discoverServers("https://beberlei.tent.is");
    }
}

