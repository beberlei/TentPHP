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

    public function getUser($entityUrl)
    {
    }


    /**
     * Get the login url for an entity
     *
     * @param string $entityUrl
     * @param array $scopes
     * @param string $redirectUri
     * @param array|string $infoTypes
     * @param array|string $postTypes
     *
     * @return string
     */
    public function getLoginUrl($entityUrl, array $scopes = null, $redirectUri = null, $infoTypes = 'all', $postTypes = 'all')
    {
        $firstServerUrl = $this->getFirstServerUrl($entityUrl);
        $config         = $this->getApplicationConfig($firstServerUrl);

        $state  = '';
        $params = array(
            'client_id'               => $config->getApplicationId(),
            'redirect_uri'            => $redirectUri ?: $this->application->getFirstRedirectUri(),
            'scope'                   => implode(", ", $scopes ?: array_keys($this->application->getScopes())),
            'state'                   => $state,
            'tent_profile_info_types' => is_array($infoTypes) ? implode(",", $infoTypes) : $infoTypes,
            'tent_post_types'         => is_array($postTypes) ? implode(",", $postTypes) : $postTypes,
        );

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

