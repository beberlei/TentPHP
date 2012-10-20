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
    public function getUserAuthorization($entityUrl, ApplicationConfig $config);

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
     * @param Application $application
     * @param ApplicationConfig $config
     */
    public function saveApplicationConfig($serverUrl, Application $application, ApplicationConfig $config);

    /**
     * Save the user access token for a given entity and application pair.
     *
     * @param string $entityUrl
     * @param ApplicationConfig $config
     * @param string $token
     */
    public function saveUserAuthorization($entityUrl, ApplicationConfig $config, UserAuthorization $user);

    /**
     * Save entity and server url that a state token is used to authorize for.
     *
     * @param string $state
     * @param string $entityUrl
     * @param string $serverUrl
     */
    public function pushStateToken($state, $entityUrl, $serverUrl);

    /**
     * Return entity and server url for a given state token. Remove token from stack.
     *
     * @param string $state
     * @return array
     */
    public function popStateToken($state);
}

