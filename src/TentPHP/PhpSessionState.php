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

namespace TentPHP;

class PhpSessionState implements ApplicationState
{
    public function pushStateToken($state, $entityUrl, $serverUrl)
    {
        $_SESSION['tentphp'][$state] = array($entityUrl, $serverUrl);
    }

    public function popStateToken($state)
    {
        if ( ! isset($_SESSION['tentphp'][$state])) {
            return;
        }

        return $_SESSION['tentphp'][$state];
    }
}

