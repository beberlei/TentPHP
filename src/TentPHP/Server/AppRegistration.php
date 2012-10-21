<?php

namespace TentPHP\Server;

use TentPHP\Application;
use TentPHP\ApplicationConfig;
use TentPHP\HmacHelper;

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
     * @param string $serverUrl
     *
     * @return ApplicationConfig
     */
    public function register(Application $application, $serverUrl)
    {
        $payload = json_encode($application->toArray());
        $headers = array(
            'Content-Type' => 'application/vnd.tent.v0+json',
            'Accept' => 'application/vnd.tent.v0+json',
        );

        try {
            $response  = $this->httpClient->post(rtrim($serverUrl, '/') . '/apps', $headers, $payload)->send();
        } catch(ServerErrorResponseException $e) {
            throw new \RuntimeException("Error registering application: " . $e->getMessage(), 0, $e);
        } catch(ClientErrorResponseException $e) {
            throw new \RuntimeException("Error registering application: " . $e->getMessage(), 0, $e);
        }

        $appConfig = json_decode($response->getBody(), true);

        return new ApplicationConfig($appConfig);
    }

    /**
     * Update application details on the given server
     *
     * @param Application $application
     * @param ApplicationConfig $config
     * @param string $serverUrl
     * @return ApplicationConfig
     */
    public function update(Application $application, ApplicationConfig $config, $serverUrl)
    {
        $payload = json_encode($application->toArray());
        $auth    = HmacHelper::generateAuthorizationHeader('PUT', $url, $config->getMacKeyId(), $config->getMacKey());
        $headers = array(
            'Content-Type'  => 'application/vnd.tent.v0+json',
            'Accept'        => 'application/vnd.tent.v0+json',
            'Authorization' => $auth,
        );

        try {
            $url      = rtrim($serverUrl, '/') . '/apps/' . $config->getApplicationId();
            $response = $this->httpClient->put($url, $headers, $payload)->send();
        } catch(ServerErrorResponseException $e) {
            throw new \RuntimeException("Error registering application: " . $e->getMessage(), 0, $e);
        } catch(ClientErrorResponseException $e) {
            throw new \RuntimeException("Error registering application: " . $e->getMessage(), 0, $e);
        }

        return json_decode($response->getBody(), true);
    }
}

