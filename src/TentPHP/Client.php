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
    private $userStorage;

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
        UserStorage $userStorage,
        ApplicationState $state = null,
        EntityDiscovery $discovery = null,
        AppRegistration $appRegistration = null)
    {
        $this->application     = $application;
        $this->httpClient      = $httpClient;
        $this->userStorage     = $userStorage;
        $this->state           = $state ?: new PhpSessionState();
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
        $user = $this->userStorage->load($entityUrl);

        if (!$user) {
            $servers   = $this->discovery->discoverServers($entityUrl);
            $serverUrl = array_shift($servers);
        } else {
            $serverUrl = $user->serverUrl;
        }

        return new UserClient($this->httpClient, $serverUrl, $user);
    }

    /**
     * Authorize an application for an entity user encoded into the state token.
     *
     * The state token contains a reference to the entity and server url that are
     * part of the authorize operation. The code is sent to the server.
     *
     * @param string $state
     * @param string $code
     *
     * @return string $entityUrl
     */
    public function authorize($state, $code)
    {
        $data = $this->state->popStateToken($state);

        if (!$data) {
            throw new \RuntimeException("No data found for state token " . $state);
        }

        list($entityUrl, $serverUrl) = $data;

        $user = $this->userStorage->load($entityUrl);

        if (!$user) {
            throw new \RuntimeException("Could not find application config for " . $serverUrl);
        }

        $url      = $serverUrl . "/apps/" . $user->appId . "/authorizations";
        $payload  = json_encode(array('code' => $code, 'token_type' => 'mac'));

        $auth = HmacHelper::generateAuthorizationHeader('POST', $url, $user->appMacKey, $user->appMacSecret);

        $headers = array(
            'Content-Type'  => 'application/vnd.tent.v0+json',
            'Accept'        => 'application/vnd.tent.v0+json',
            'Authorization' => $auth
        );

        $response = $this->httpClient->post($url, $headers, $payload)->send();

        $userAuthorization = json_decode($response->getBody(), true);

        $user->macKey       = $userAuthorization['access_token'];
        $user->macSecret    = $userAuthorization['mac_key'];
        $user->macAlgorithm = $userAuthorization['mac_algorithm'];

        $this->userStorage->save($user);

        return $entityUrl;
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
        $user = $this->userStorage->load($entityUrl);

        if ($user) {
            try {
                $this->getApplication($entityUrl);
            } catch(GuzzleException $e) {
                $user->appId = $user->appMacKey = $user->appMacSecret = $user->appMacAlgorithm = null;
            }
        } else {
            $user    = new User($entityUrl);
            $servers = $this->discovery->discoverServers($entityUrl);

            $user->serverUrl = array_shift($servers);
        }

        if ( ! $user->appId) {
            $this->appRegistration->register($this->application, $user);
        }

        $state  = str_replace(array('/', '+', '='), '', base64_encode(openssl_random_pseudo_bytes(64)));
        $params = array(
            'client_id'    => $user->appId,
            'redirect_uri' => $redirectUri ?: $this->application->getFirstRedirectUri(),
            'scope'        => implode(",", $scopes ?: array_keys($this->application->getScopes())),
            'state'        => $state,
        );

        $this->state->pushStateToken($state, $entityUrl, $user->serverUrl);

        if ($infoTypes) {
            $params['tent_profile_info_types'] = is_array($infoTypes) ? implode(",", $infoTypes) : $infoTypes;
            $user->profileTypes = $params['tent_profile_info_types'];
        }

        if ($postTypes) {
            $params['tent_post_types'] = is_array($postTypes) ? implode(",", $postTypes) : $postTypes;
            $user->postTypes = $params['tent_post_types'];
        }

        if ($notificationUrl) {
            $params['tent_notification_url'] = $notificationUrl;
            $user->notificationUrl = $notificationUrl;
        }

        $this->userStorage->save($user);

        return sprintf(
            '%s/oauth/authorize?%s',
            $user->serverUrl,
            http_build_query($params)
        );
    }

    /**
     * Get application details saved on the given tent server.
     *
     * @param string $entityUrl
     *
     * @return array
     */
    public function getApplication($entityUrl)
    {
        $user = $this->userStorage->load($entityUrl);

        if (!$user->appId) {
            throw new \RuntimeException("Could not find application config for " . $serverUrl);
        }

        $url  = $user->serverUrl . "/apps/" . $user->appId;
        $auth = HmacHelper::generateAuthorizationHeader('GET', $url, $user->appMacKey, $user->appMacSecret);

        $headers = array(
            'Content-Type'  => 'application/vnd.tent.v0+json',
            'Accept'        => 'application/vnd.tent.v0+json',
            'Authorization' => $auth
        );

        $response = $this->httpClient->get($url, $headers)->send();

        return json_decode($response->getBody(), true);
    }

    /**
     * Update the application with the current data on the given server.
     *
     * @param string $entityUrl
     */
    public function updateApplication($entityUrl)
    {
        $user = $this->userStorage->load($entityUrl);

        if (!$user->appId) {
            throw new \RuntimeException("Could not find application config for " . $serverUrl);
        }

        return $this->appRegistration->update($this->application, $config, $serverUrl);
    }
}

