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

class Group
{
    public $id;
    public $name;
    public $created;
    public $updated;

    public function __construct($id, $name, \DateTime $created = null, \DateTime $updated = null)
    {
        $this->id      = $id;
        $this->name    = $name;
        $this->created = $created;
        $this->updated = $updated;
    }
}

