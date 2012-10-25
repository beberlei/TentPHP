<?php

namespace TentPHP\Server;

use TentPHP\Application;
use TentPHP\HmacHelper;
use TentPHP\User;

use Guzzle\Http\Client as HttpClient;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Exception\ServerErrorResponseException;

class AppRegistration
{
    private $httpClient;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Register application with the server
     *
     * This function performs no checks if this application already
     * exists. This is done at other levels.
     *
     * @param Application $application
     * @param User $user
     *
     * @return void
     */
    public function register(Application $application, User $user)
    {
        $payload = json_encode($application->toArray());
        $headers = array(
            'Content-Type' => 'application/vnd.tent.v0+json',
            'Accept' => 'application/vnd.tent.v0+json',
        );

        try {
            $response  = $this->httpClient->post(rtrim($user->serverUrl, '/') . '/apps', $headers, $payload)->send();
        } catch(ServerErrorResponseException $e) {
            throw new \RuntimeException("Error registering application: " . $e->getMessage(), 0, $e);
        } catch(ClientErrorResponseException $e) {
            throw new \RuntimeException("Error registering application: " . $e->getMessage(), 0, $e);
        }

        $appConfig = json_decode($response->getBody(), true);

        $user->appId           = $appConfig['id'];
        $user->appMacKey       = $appConfig['mac_key_id'];
        $user->appMacSecret    = $appConfig['mac_key'];
        $user->appMacAlgorithm = $appConfig['mac_algorithm'];
    }

    /**
     * Update application details on the given server
     *
     * @param Application $application
     * @param User $user
     */
    public function update(Application $application, User $user)
    {
        $payload = json_encode($application->toArray());
        $url     = rtrim($user->serverUrl, '/') . '/apps/' . $user->appId;
        $auth    = HmacHelper::generateAuthorizationHeader('PUT', $url, $user->appMacKey, $user->appMacSecret);

        $headers = array(
            'Content-Type'  => 'application/vnd.tent.v0+json',
            'Accept'        => 'application/vnd.tent.v0+json',
            'Authorization' => $auth,
        );

        try {
            $response = $this->httpClient->put($url, $headers, $payload)->send();
        } catch(ServerErrorResponseException $e) {
            throw new \RuntimeException("Error registering application: " . $e->getMessage(), 0, $e);
        } catch(ClientErrorResponseException $e) {
            throw new \RuntimeException("Error registering application: " . $e->getMessage(), 0, $e);
        }

        return json_decode($response->getBody(), true);
    }
}

