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
    const ENTITYURL = 'https://beberlei.tent.is';
    const SERVERURL = 'https://beberlei.tent.is/tent';
    private $httpMocks;

    public function testGetLoginUrlFromPersistence()
    {
        $app    = new Application(array(
            "name"         => "Hello World!",
            "redirect_uri" => array("http://example.com/redirect"),
            "scopes"       => array('read_profile' => 'Read profile sections listed in the profile_info parameter')
        ));
        $config = new ApplicationConfig(array(
            'id'            => 'e12345',
            'mac_key_id'    => 'ab1234',
            'mac_key'       => 'abcdefg',
            'mac_algorithm' => 'hmac-sha-256',
        ));

        $state = $this->mock('TentPHP\ApplicationState');
        $state->shouldReceive('getServers')->with(self::ENTITYURL)->andReturn(array(self::SERVERURL));
        $state->shouldReceive('saveServers')->times(0);
        $state->shouldReceive('getApplicationConfig')->with(self::SERVERURL, $app)->andReturn($config);
        $state->shouldReceive('saveApplicationConfig')->times(0);

        $httpClient = new HttpClient();
        $client = new Client($app, $httpClient, $state);
        $url    = $client->getLoginUrl(self::ENTITYURL);

        $this->assertEquals("https://beberlei.tent.is/tent/oauth/authorize?client_id=e12345&redirect_uri=http%3A%2F%2Fexample.com%2Fredirect&scope=read_profile&state=&tent_profile_info_types=all&tent_post_types=all", $url);
    }

    public function testGetLoginUrlUnknownServerRegistersApplication()
    {
        $app    = new Application(array(
            "name"         => "Hello World!",
            "redirect_uri" => array("http://example.com/redirect"),
            "scopes"       => array('read_profile' => 'Read profile sections listed in the profile_info parameter')
        ));
        $config = new ApplicationConfig(array(
            'id'            => 'e12345',
            'mac_key_id'    => 'ab1234',
            'mac_key'       => 'abcdefg',
            'mac_algorithm' => 'hmac-sha-256',
        ));

        $state = $this->mock('TentPHP\ApplicationState');
        $state->shouldReceive('getServers')->with(self::ENTITYURL)->andReturn(false);
        $state->shouldReceive('saveServers')->with(self::ENTITYURL, array(self::SERVERURL));
        $state->shouldReceive('getApplicationConfig')->with(self::SERVERURL, $app);
        $state->shouldReceive('saveApplicationConfig')->with(self::SERVERURL, $app, $config);

        $discovery = $this->mock('TentPHP\Server\EntityDiscovery');
        $discovery->shouldReceive('discoverServers')
                  ->with(self::ENTITYURL)
                  ->andReturn(array(self::SERVERURL));

        $appRegistration = $this->mock('TentPHP\Server\AppRegistration');
        $appRegistration->shouldReceive('register')
                        ->with($app, self::SERVERURL)
                        ->andReturn($config);

        $httpClient = new HttpClient();
        $client = new Client($app, $httpClient, $state, $discovery, $appRegistration);
        $url    = $client->getLoginUrl(self::ENTITYURL);

        $this->assertEquals("https://beberlei.tent.is/tent/oauth/authorize?client_id=e12345&redirect_uri=http%3A%2F%2Fexample.com%2Fredirect&scope=read_profile&state=&tent_profile_info_types=all&tent_post_types=all", $url);
    }
}

