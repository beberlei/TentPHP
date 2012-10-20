<?php

namespace TentPHP;

/**
 * Saves the application and entity state required for authorization with a Test server.
 */
interface ApplicationState
{
    /**
     * Given an entity url return the tent server urls this entity is managed on.
     *
     * Return false, if no servers have been saved for this entity yet.
     *
     * @param string
     * @return array|false
     */
    public function getServers($entityUrl);

    /**
     * Get the auth-configuration for an application and tent server pair.
     *
     * @param string $serverUrl
     * @param Application $application
     *
     * @return ApplicationConfig
     */
    public function getApplicationConfig($serverUrl, Application $application);

    /**
     * Get the access token for an user entity and application config pair.
     *
     * @param string $entityUrl
     * @param ApplicationConfig $config
     *
     * @return string
     */
    public function getUserAccessToken($entityUrl, ApplicationConfig $config);

    /**
     * Save the tent servers responsible for a given entity.
     *
     * @param string $entityUrl
     * @param array $serverUrls
     */
    public function saveServers($entityUrl, array $serverUrls);

    /**
     * Save the application authorization config for a given server url.
     *
     * @param string $serverUrl
     * @param ApplicationConfig $config
     */
    public function saveApplicationConfig($serverUrl, ApplicationConfig $config);

    /**
     * Save the user access token for a given entity and application pair.
     *
     * @param string $entityUrl
     * @param ApplicationConfig $config
     * @param string $token
     */
    public function saveUserAccessToken($entityUrl, ApplicationConfig $config, $token);
}

