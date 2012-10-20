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

class ApplicationConfig
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getApplicationId()
    {
        return $this->data['id'];
    }

    public function getMacKeyId()
    {
        return $this->data['mac_key_id'];
    }

    public function getMacKey()
    {
        return $this->data['mac_key'];
    }

    public function getMacAlgorithm()
    {
        return $this->data['mac_algorithm'];
    }
}

