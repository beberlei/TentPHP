<?php
/**
 * Tent PHP Client (c) Benjamin Eberlei
 *
 * LICENSE
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace TentPHP;

use TentPHP\Server\AppRegistration;
use TentPHP\Server\EntityDiscovery;
use Guzzle\Http\Client as HttpClient;

/**
 * Tent.io Client
 */
class Client
{
    private $application;
    private $httpClient;
    private $discovery;
    private $appRegistration;
    private $state;

    /**
     * @param Application      $application
     * @param HttpClient       $httpClient
     * @param EntityDiscovery  $discovery
     * @param AppRegistration  $appRegistration
     * @param ApplicationState $state
     */
    public function __construct(
        Application $application,
        HttpClient $httpClient,
        ApplicationState $state,
        EntityDiscovery $discovery = null,
        AppRegistration $appRegistration = null)
    {
        $this->application     = $application;
        $this->httpClient      = $httpClient;
        $this->state           = $state;
        $this->discovery       = $discovery ?: new EntityDiscovery($httpClient);
        $this->appRegistration = $appRegistration ?: new AppRegistration($httpClient);
    }

    /**
     * Get a client for a specific user.
     *
     * @param string $entityUrl
     *
     * @return UserClient
     */
    public function getUserClient($entityUrl)
    {
        $servers = $this->state->getServers($entityUrl);
        $configs = array();

        if ( ! $servers) {
            throw new \RuntimeException("User " . $entityUrl . " has not authorized the application yet.");
        }

        foreach ($servers as $serverUrl) {
            $config = $this->state->getApplicationConfig($serverUrl, $this->application);

            if (!$config) {
                throw new \RuntimeException("User " . $entityUrl . " has not authorized the application yet.");
            }

            $configs[$serverUrl] = $config;
        }

        $userData = $this->state->getUserAuthorization($entityUrl, $configs[0]);

        return new UserClient($this->httpClient, $this->application, $configs, $userData);
    }

    private static function base64UrlEncode($input)
    {
        $str = strtr(base64_encode($input), '+/', '-_');
        $str = str_replace('=', '', $str);
        return $str;
    }

    /**
     * Authorize an application for an entity user encoded into the state token.
     *
     * The state token contains a reference to the entity and server url that are
     * part of the authorize operation. The code is sent to the server.
     *
     * @param string $state
     * @param string $code
     */
    public function authorize($state, $code)
    {
        list($entityUrl, $serverUrl) = $this->state->popStateToken($state);

        $config  = $this->state->getApplicationConfig($serverUrl, $this->application);
        $payload = json_encode(array('code' => $code, 'token_type' => 'mac'));
        $mac     = hash_hmac('sha256', self::base64UrlEncode($payload), $config->getMacKey());

        $auth = sprintf(
            'Authorization: MAC id="%s", ts="%s", nonce="%s", mac="%s"',
            $config->getApplicationId(),
            time(),
            md5($entityUrl.uniqid()),
            $mac
        );

        $headers = array(
            'Content-Type: application/vnd.tent.v0+json',
            'Accept: application/vnd.tent.v0+json',
            $auth
        );

        $url      = $serverUrl . "/apps/" . $config->getApplicationId() . "/authorize";
        $response = $this->httpClient->post($url, $headers, $payload)->send();

        $userData = json_decode($response->getBody(), true);
        $this->state->saveUserAuthorization($entityUrl, $config, new UserAuthorization($userData));
    }

    /**
     * Get the login url for an entity
     *
     * @param string $entityUrl
     * @param array $scopes
     * @param string $redirectUri
     * @param array|string $infoTypes
     * @param array|string $postTypes
     * @param string $notificationUrl
     *
     * @return string
     */
    public function getLoginUrl($entityUrl, array $scopes = null, $redirectUri = null, array $infoTypes = null, array $postTypes = null, $notificationUrl = null)
    {
        $firstServerUrl = $this->getFirstServerUrl($entityUrl);
        $config         = $this->getApplicationConfig($firstServerUrl);

        $state  = self::base64UrlEncode(openssl_random_pseudo_bytes(64));
        $params = array(
            'client_id'               => $config->getApplicationId(),
            'redirect_uri'            => $redirectUri ?: $this->application->getFirstRedirectUri(),
            'scope'                   => implode(", ", $scopes ?: array_keys($this->application->getScopes())),
            'state'                   => $state,
        );

        $this->state->pushStateToken($state, $entityUrl, $firstServerUrl);

        if ($infoTypes) {
            $params['tent_profile_info_types'] = is_array($infoTypes) ? implode(",", $infoTypes) : $infoTypes;
        }

        if ($postTypes) {
            $params['tent_post_types'] = is_array($postTypes) ? implode(",", $postTypes) : $postTypes;
        }

        if ($notificationUrl) {
            $params['tent_notification_url'] = $notificationUrl;
        }

        return sprintf(
            '%s/oauth/authorize?%s',
            $firstServerUrl,
            http_build_query($params)
        );
    }

    private function getFirstServerUrl($entityUrl)
    {
        $servers = $this->state->getServers($entityUrl);

        if ($servers === false) {
            $servers = $this->discovery->discoverServers($entityUrl);
            $this->state->saveServers($entityUrl, $servers);
        }

        return array_shift($servers);
    }

    private function getApplicationConfig($serverUrl)
    {
        $config = $this->state->getApplicationConfig($serverUrl, $this->application);

        if (!$config) {
            $config = $this->appRegistration->register($this->application, $serverUrl);
            $this->state->saveApplicationConfig($serverUrl, $this->application, $config);
        }

        return $config;
    }
}

