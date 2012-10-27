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

        $appRegistration = $this->mock('TentPHP\Server\AppRegistration');
        $appRegistration->shouldReceive('register')
                        ->with($app, \Mockery::type('TentPHP\User'));

        $state = $this->mock('TentPHP\ApplicationState');
        $state->shouldReceive('pushStateToken')->times(1);

        $user = new \TentPHP\User(self::ENTITYURL);
        $user->appId     = 'e12345';
        $user->serverUrl = 'https://beberlei.tent.is/tent';

        $userStorage = $this->mock('TentPHP\UserStorage');
        $userStorage->shouldReceive('load')->with(self::ENTITYURL)->andReturn($user);
        $userStorage->shouldReceive('save')->with(\Mockery::type('TentPHP\User'));

        $httpMocks = new MockPlugin();
        $httpMocks->addResponse(new Response(200, null,'{}'));

        $httpClient = new HttpClient();
        $httpClient->addSubscriber($httpMocks);

        $client = new Client($app, $httpClient, $userStorage, $state, null, $appRegistration);
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

        $state = $this->mock('TentPHP\ApplicationState');
        $state->shouldReceive('pushStateToken')->times(1);

        $discovery = $this->mock('TentPHP\Server\EntityDiscovery');
        $discovery->shouldReceive('discoverServers')
                  ->with(self::ENTITYURL)
                  ->andReturn(array(self::SERVERURL));

        $appRegistration = $this->mock('TentPHP\Server\AppRegistration');
        $appRegistration->shouldReceive('register')
                        ->times(1)
                        ->with($app, \Mockery::type('TentPHP\User'));

        $userStorage = $this->mock('TentPHP\UserStorage');
        $userStorage->shouldReceive('load')->with(self::ENTITYURL)->andReturn(null);
        $userStorage->shouldReceive('save')->with(\Mockery::type('TentPHP\User'));

        $httpClient = new HttpClient();
        $client = new Client($app, $httpClient, $userStorage, $state, $discovery, $appRegistration);
        $url    = $client->getLoginUrl(self::ENTITYURL);

        $this->assertStringStartsWith("https://beberlei.tent.is/tent/oauth/authorize?redirect_uri=http%3A%2F%2Fexample.com%2Fredirect&scope=read_profile&state=", $url);
    }

    public function testAuthorize()
    {
        $app    = new Application(array(
            "name"         => "Hello World!",
            "redirect_uris" => array("http://example.com/redirect"),
            "scopes"       => array('read_profile' => 'Read profile sections listed in the profile_info parameter')
        ));

        $user = new \TentPHP\User(self::ENTITYURL);

        $userStorage = $this->mock('TentPHP\UserStorage');
        $userStorage->shouldReceive('load')->with(self::ENTITYURL)->andReturn($user);
        $userStorage->shouldReceive('save')->with(\Mockery::type('TentPHP\User'));

        $state = $this->mock('TentPHP\ApplicationState');
        $state->shouldReceive('popStateToken')->with('abcdefg')->andReturn(array(self::ENTITYURL, self::SERVERURL));

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

        $client = new Client($app, $httpClient, $userStorage, $state, null, null);
        $client->authorize('abcdefg', 'hijklmn');
    }

    public function testGetUserClient()
    {
        $app    = new Application(array(
            "name"         => "Hello World!",
            "redirect_uris" => array("http://example.com/redirect"),
            "scopes"       => array('read_profile' => 'Read profile sections listed in the profile_info parameter')
        ));

        $state = $this->mock('TentPHP\ApplicationState');

        $user = new \TentPHP\User(self::ENTITYURL);
        $userStorage = $this->mock('TentPHP\UserStorage');
        $userStorage->shouldReceive('load')->with(self::ENTITYURL)->andReturn($user);

        $httpClient = new HttpClient();
        $client = new Client($app, $httpClient, $userStorage, $state, null, null);

        $userClient = $client->getUserClient(self::ENTITYURL);

        $this->assertInstanceOf('TentPHP\UserClient', $userClient);
    }
}

