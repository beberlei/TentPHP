<?php

namespace TentPHP\Tests;

use TentPHP\UserClient;
use TentPHP\UserAuthorization;
use Guzzle\Http\Client as HttpClient;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\Message\Response;

class UserClientTest extends TestCase
{
    public function testGetProfile()
    {
        $userAuthorization = new UserAuthorization(array(
            'mac_key'      => 'abcdefg',
            'access_token' => 'hijklmn',
        ));
        $mockPlugin = new MockPlugin();
        $mockPlugin->addResponse(new Response(200, null, <<<JSON
{
  "https://tent.io/types/info/basic/v0.1.0": {
    "name": "The Tentity",
    "avatar_url": "http://example.org/avatar.jpg",
    "birthdate": "2012-08-23",
    "location": "The Internet",
    "gender": "Unknown",
    "bio": "A qui cum ratione consequatur pariatur.",
    "permissions": {
      "public": true
    }
  },
  "https://tent.io/types/info/core/v0.1.0": {
    "licenses": [
      "http://creativecommons.org/licenses/by/3.0/"
    ],
    "entity": "https://example.org",
    "servers": [
      "https://tent.example.com",
      "http://eqt5g4fuenphqinx.onion/"
    ],
    "permissions": {
      "public": true
    }
  }
}
JSON
        ));
        $httpClient = new HttpClient();
        $httpClient->addSubscriber($mockPlugin);

        $userClient = new UserClient($httpClient, "https://beberlei.tent.is/tent", $userAuthorization);

        $data = $userClient->getProfile();

        $this->assertEquals(array (
              'https://tent.io/types/info/basic/v0.1.0' =>
              array (
                'name' => 'The Tentity',
                'avatar_url' => 'http://example.org/avatar.jpg',
                'birthdate' => '2012-08-23',
                'location' => 'The Internet',
                'gender' => 'Unknown',
                'bio' => 'A qui cum ratione consequatur pariatur.',
                'permissions' =>
                array (
                  'public' => true,
                ),
              ),
              'https://tent.io/types/info/core/v0.1.0' =>
              array (
                'licenses' =>
                array (
                  0 => 'http://creativecommons.org/licenses/by/3.0/',
                ),
                'entity' => 'https://example.org',
                'servers' =>
                array (
                  0 => 'https://tent.example.com',
                  1 => 'http://eqt5g4fuenphqinx.onion/',
                ),
                'permissions' =>
                array (
                  'public' => true,
                ),
              ),
            ), $data);
    }
}

