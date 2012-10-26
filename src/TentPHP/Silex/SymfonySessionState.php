<?php
/**
 * TentPHP
 *
 * LICENSE
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace TentPHP\Silex;

use TentPHP\ApplicationState;

class SymfonySessionState implements ApplicationState
{
    private $session;

    public function __construct($session)
    {
        $this->session = $session;
    }

    public function pushStateToken($state, $entityUrl, $serverUrl)
    {
        $this->session->set('tentc', array(
            'state' => $state,
            'entity' => $entityUrl,
            'server' => $serverUrl
        ));
    }

    public function popStateToken($state)
    {
        $token = $this->session->get('tentc');

        if ($state !== $token['state']) {
            return null;
        }

        return array($token['entity'], $token['server']);
    }
}

