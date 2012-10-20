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

use Guzzle\Http\Client as HttpClient;

class Client
{
    private $httpClient;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
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
        return new ApplicationConfig();
    }

    /**
     * Discover the Tent Servers responsible for a given tent entity.
     *
     * @param string $entityUrl
     * @return array
     */
    public function discoverServers($entityUrl)
    {
        $profiles = $servers = array();
        $response = $this->httpClient->head($entityUrl)->send();

        if ($response->getStatusCode() !== 200) {
            throw new EntityNotFoundException("Unsuccessful response querying the entity url for a profile link.");
        }

        $links = $response->getHeader('Link');

        foreach ($links as $link) {
            if (preg_match('(<([^>]+)>; rel="https://tent.io/rels/profile")', $link, $match)) {
                $profiles[] = $match[1];
            }
        }

        if (!$profiles) {
            throw new EntityNotFoundException("No profile links found when querying the entity url.");
        }

        foreach ($profiles as $profileUrl) {
            $response = $this->httpClient->get($profileUrl, array('Accept: application/vnd.tent.v0+json'))->send();

            if ($response->getStatusCode() !== 200) {
                throw new EntityNotFoundException("Unsuccessful resposne querying for profile " . $profileUrl);
            }

            $profile = json_decode($response->getBody(), true);

            if ( ! isset($profile['https://tent.io/types/info/core/v0.1.0']['servers'])) {
                throw new EntityNotFoundException("Incomplete response querying for profile " . $profileUrl . ". No servers key found in tent core info type.");
            }

            $servers = array_merge($servers, $profile['https://tent.io/types/info/core/v0.1.0']['servers']);
        }

        return $servers;
    }
}

