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

use TentPHP\Server\EntityDiscovery;
use Guzzle\Http\Client as HttpClient;
use Guzzle\Http\Exception\ClientErrorResponseException;

class Client
{
    private $httpClient;
    private $discovery;

    public function __construct(HttpClient $httpClient, EntityDiscovery $discovery = null)
    {
        $this->httpClient = $httpClient;
        $this->discovery  = $discovery ?: new EntityDiscovery($httpClient);
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
        $payload = json_encode($application->toArray());
        $headers = array(
            'Content-Type: application/vnd.tent.v0+json',
            'Accept: application/vnd.tent.v0+json',
        );

        foreach ($servers as $serverUrl) {
            $response = $this->httpClient->post(rtrim($serverUrl, '/') . '/apps', $headers, $payload)->send();

            $appConfig = json_decode($response->getBody());
        }

        return new ApplicationConfig();
    }

}

