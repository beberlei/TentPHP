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

namespace TentPHP\DBAL;

use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use TentPHP\Util\Encryption;

/**
 * Encryption datatype.
 */
class EncryptedString extends StringType
{
    /**
     * @var Encryption
     */
    private $encryption;

    public function setEncryption(Encryption $encryption)
    {
        $this->encryption = $encryption;
    }

    public function getName()
    {
        return 'tentecstring';
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $this->encryption->encrypt($value);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $this->encryption->decrypt($value);
    }
}

