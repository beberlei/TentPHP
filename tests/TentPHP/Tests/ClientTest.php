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
use TentPHP\UserAuthorization;
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
            "name"          => "Hello World!",
            "redirect_uris" => array("http://example.com/redirect"),
            "scopes"        => array('read_profile' => 'Read profile sections listed in the profile_info parameter')
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
        $state->shouldReceive('pushStateToken')->times(1);

        $userStorage = $this->mock('TentPHP\UserStorage');
        $userStorage->shouldReceive('load')->with(self::ENTITYURL)->andReturn(null);

        $httpClient = new HttpClient();
        $client = new Client($app, $httpClient, $state, null, null, $userStorage);
        $url    = $client->getLoginUrl(self::ENTITYURL);

        $this->assertStringStartsWith("https://beberlei.tent.is/tent/oauth/authorize?client_id=e12345&redirect_uri=http%3A%2F%2Fexample.com%2Fredirect&scope=read_profile&state=", $url);
    }

    public function testGetLoginUrlUnknownServerRegistersApplication()
    {
        $app    = new Application(array(
            "name"         => "Hello World!",
            "redirect_uris" => array("http://example.com/redirect"),
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
        $state->shouldReceive('pushStateToken')->times(1);

        $discovery = $this->mock('TentPHP\Server\EntityDiscovery');
        $discovery->shouldReceive('discoverServers')
                  ->with(self::ENTITYURL)
                  ->andReturn(array(self::SERVERURL));

        $appRegistration = $this->mock('TentPHP\Server\AppRegistration');
        $appRegistration->shouldReceive('register')
                        ->with($app, self::SERVERURL)
                        ->andReturn($config);


        $userStorage = $this->mock('TentPHP\UserStorage');
        $userStorage->shouldReceive('load')->with(self::ENTITYURL)->andReturn(null);

        $httpClient = new HttpClient();
        $client = new Client($app, $httpClient, $state, $discovery, $appRegistration, $userStorage);
        $url    = $client->getLoginUrl(self::ENTITYURL);

        $this->assertStringStartsWith("https://beberlei.tent.is/tent/oauth/authorize?client_id=e12345&redirect_uri=http%3A%2F%2Fexample.com%2Fredirect&scope=read_profile&state=", $url);
    }

    public function testAuthorize()
    {
        $app    = new Application(array(
            "name"         => "Hello World!",
            "redirect_uris" => array("http://example.com/redirect"),
            "scopes"       => array('read_profile' => 'Read profile sections listed in the profile_info parameter')
        ));

        $config = new ApplicationConfig(array(
            'id'            => 'e12345',
            'mac_key_id'    => 'ab1234',
            'mac_key'       => 'abcdefg',
            'mac_algorithm' => 'hmac-sha-256',
        ));

        $state = $this->mock('TentPHP\ApplicationState');
        $state->shouldReceive('popStateToken')->with('abcdefg')->andReturn(array(self::ENTITYURL, self::SERVERURL));
        $state->shouldReceive('getApplicationConfig')->with(self::SERVERURL, $app)->andReturn($config);
        $state->shouldReceive('saveUserAuthorization')->times(1)->with(self::ENTITYURL, $config, \Mockery::type('TentPHP\UserAuthorization'));

        $httpMocks = new MockPlugin();
        $httpMocks->addResponse(new Response(200, null, <<<JSON
{
    "access_token": "u:9a27c9c0",
    "mac_key": "01a72852b917af2f16a782c08fcec23f",
    "mac_algorithm": "hmac-sha-256",
    "token_type": "mac"
}
JSON
        ));

        $httpClient = new HttpClient();
        $httpClient->addSubscriber($httpMocks);

        $client = new Client($app, $httpClient, $state);
        $client->authorize('abcdefg', 'hijklmn');
    }

    public function testGetUserClient()
    {
        $app    = new Application(array(
            "name"         => "Hello World!",
            "redirect_uris" => array("http://example.com/redirect"),
            "scopes"       => array('read_profile' => 'Read profile sections listed in the profile_info parameter')
        ));

        $config = new ApplicationConfig(array(
            'id'            => 'e12345',
            'mac_key_id'    => 'ab1234',
            'mac_key'       => 'abcdefg',
            'mac_algorithm' => 'hmac-sha-256',
        ));

        $state = $this->mock('TentPHP\ApplicationState');
        $state->shouldReceive('getServers')->times(1)->with(self::ENTITYURL)->andReturn(array(self::SERVERURL));
        $state->shouldReceive('getApplicationConfig')->times(1)->andReturn($config);
        $state->shouldReceive('getUserAuthorization')->times(1)->with(self::ENTITYURL, $config)->andReturn(new UserAuthorization(array()));

        $httpClient = new HttpClient();
        $client = new Client($app, $httpClient, $state);

        $userClient = $client->getUserClient(self::ENTITYURL);

        $this->assertInstanceOf('TentPHP\UserClient', $userClient);
    }
}

