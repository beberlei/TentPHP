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

namespace TentPHP;

class Application
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getName()
    {
        return $this->data['name'];
    }

    public function getFirstRedirectUri()
    {
        if (!isset($this->data['redirect_uri'][0])) {
            throw new \RuntimeException("Application has no redirect urls configured.");
        }

        return $this->data['redirect_uri'][0];
    }

    public function getScopes()
    {
        return $this->data['scopes'];
    }

    public function toArray()
    {
        return $this->data;
    }
}

