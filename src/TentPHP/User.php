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

/**
 * Tent User Object that contains app and user authentication.
 */
class User
{
    public $entity;
    public $serverUrl;
    public $appId;
    public $appMacKey;
    public $appMacSecret;
    public $appMacAlgorithm;
    public $macKey;
    public $macSecret;
    public $macAlgorithm;
    public $tokenType;
    public $profileInfoTypes;
    public $postTypes;
    public $notificationUrl;

    public function __construct($entity)
    {
        $this->entity = $entity;
    }
}

