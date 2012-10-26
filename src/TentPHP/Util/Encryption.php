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

namespace TentPHP\Util;

/**
 * Encryption service that works a symmetric Blowfish encryption.
 *
 * Hides the hideous details of mcrypt api behind a simple to use service.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class Encryption
{
    private $iv;
    private $key;
    private $blockSize;

    public function __construct($key)
    {
        if ( ! is_string($key) || empty($key)) {
            throw new \RuntimeException("Missing encryption key.");
        }

        $this->key = $key;
    }

    private function init()
    {
        if (!$this->iv) {
            $ivSize   = mcrypt_get_iv_size(MCRYPT_TWOFISH, MCRYPT_MODE_ECB);
            $this->iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
        }
    }

    /**
     * Encrypt a value with the current key.
     *
     * @param string $value
     * @return string
     */
    public function encrypt($value)
    {
        if (empty($value)) {
            return $value;
        }

        $this->init();

        // pad last block with whitespaces
        $block = mcrypt_get_block_size(MCRYPT_TWOFISH, MCRYPT_MODE_ECB);
        $pad = $block - (strlen($value) % $block);
        $value .= str_repeat(' ', $pad);

        return base64_encode(mcrypt_encrypt(MCRYPT_TWOFISH, $this->key, $value, MCRYPT_MODE_ECB, $this->iv));
    }

    /**
     * Given an encrypted value, decrypt it with the current key.
     *
     * @param string $value
     * @return string
     */
    public function decrypt($value)
    {
        if (empty($value)) {
            return $value;
        }

        $this->init();

        // Why rtrim()? blocks are encrypted
        return rtrim(mcrypt_decrypt(MCRYPT_TWOFISH, $this->key, base64_decode($value), MCRYPT_MODE_ECB, $this->iv));
    }
}
