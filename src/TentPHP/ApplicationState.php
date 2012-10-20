<?php

namespace TentPHP;

interface ApplicationState
{
    public function getServers($entityUrl);
    public function getApplicationConfig($serverUrl, Application $application);
    public function saveServers($entityUrl, $serverUrls);
    public function saveApplicationConfig($serverUrl, ApplicationConfig $config);
    public function getUserAccessToken($entityUrl, Application $application);
    public function saveUserAccessToken($entityUrl, Application $application, $token);
}

