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

class Client
{
    private $httpClient;
    private $appRegistration;
    private $discovery;

    public function __construct(HttpClient $httpClient, EntityDiscovery $discovery = null, AppRegistration $appRegistration = null)
    {
        $this->httpClient      = $httpClient;
        $this->discovery       = $discovery ?: new EntityDiscovery($httpClient);
        $this->appRegistration = $appRegistration ?: new AppRegistration($httpClient);
    }

    /**
     * Registers application with tent server of given user-entity.
     *
     * @param Application $application
     * @param string $entityUrl
     *
     * @return ApplicationConfig
     */
    public function registerApplication(Application $application, $entityUrl)
    {
        $servers = $this->discovery->discoverServers($entityUrl);

        foreach ($servers as $serverUrl) {
            $config = $this->appRegistration->register($application, $serverUrl);
        }
    }
}

