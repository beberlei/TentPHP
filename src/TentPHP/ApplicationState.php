<?php

namespace TentPHP;

/**
 * Save and reload the session state for the OAuth authorization.
 */
interface ApplicationState
{
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

