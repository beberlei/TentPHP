<?php

namespace TentPHP\Server;

use TentPHP\Application;
use TentPHP\ApplicationConfig;

use Guzzle\Http\Client as HttpClient;
use Guzzle\Http\Exception\ClientErrorResponseException;

class AppRegistration
{
    private $httpClient;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function register(Application $application, $serverUrl)
    {
        $payload = json_encode($application->toArray());
        $headers = array(
            'Content-Type: application/vnd.tent.v0+json',
            'Accept: application/vnd.tent.v0+json',
        );

        $response  = $this->httpClient->post(rtrim($serverUrl, '/') . '/apps', $headers, $payload)->send();
        $appConfig = json_decode($response->getBody(), true);

        return new ApplicationConfig($appConfig);
    }
}

