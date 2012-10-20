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

namespace TentPHP\Server;

use TentPHP\Exception\EntityNotFoundException;
use Guzzle\Http\Client as HttpClient;
use Guzzle\Http\Exception\ClientErrorResponseException;

/**
 * Responsible for discovery of tent servers given entity urls.
 */
class EntityDiscovery
{
    private $httpClient;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
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

        try {
            $response = $this->httpClient->head($entityUrl)->send();
        } catch(ClientErrorResponseException $e) {
            throw new EntityNotFoundException("Unsuccessful response querying the entity url for a profile link.", 0, $e);
        }

        $links = $response->getHeader('Link');

        if (!$links) {
            throw new EntityNotFoundException("No links found when querying the entity url.");
        }

        foreach ($links as $link) {
            if (preg_match('(<([^>]+)>; rel="https://tent.io/rels/profile")', $link, $match)) {
                $profiles[] = $match[1];
            }
        }

        if (!$profiles) {
            throw new EntityNotFoundException("No profile links found when querying the entity url.");
        }

        foreach ($profiles as $profileUrl) {

            try {
                $response = $this->httpClient->get($profileUrl, array('Accept: application/vnd.tent.v0+json'))->send();
            } catch(ClientErrorResponseException $e) {
                throw new EntityNotFoundException("Unsuccessful response querying for profile " . $profileUrl);
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

