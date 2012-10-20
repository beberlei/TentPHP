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

    public function getLoginUrl($entityUrl)
    {
    }
}

