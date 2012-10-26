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
 * HMAC Authentication related helper methods.
 */
class HmacHelper
{
    static public function generateAuthorizationHeader($method, $url, $macKeyId, $macKey)
    {
        $ts       = time();
        $nonce    = uniqid('', true);

        return self::getAuthorizationHeader($method, $url, $macKeyId, $macKey, $ts, $nonce);
    }

    static public function getAuthorizationHeader($method, $url, $macKeyId, $macKey, $ts, $nonce)
    {
        $normalizedRequestString = self::getNormalizedRequestString($ts, $nonce, $method, $url);
        $mac = base64_encode(hash_hmac('sha256', $normalizedRequestString, $macKey, true));

        return sprintf(
            'MAC id="%s", ts="%s", nonce="%s", mac="%s"',
            $macKeyId,
            $ts,
            $nonce,
            $mac
        );
    }

    static private function getNormalizedRequestString($ts, $nonce, $method, $url)
    {
        $parts = parse_url($url);

        $requestParts = array(
            $ts,
            $nonce,
            $method,
            $parts['path'] . ((isset($parts['query']) && $parts['query']) ? "?" . $parts['query'] : ""),
            $parts['host'],
            (isset($parts['port']) ?: (($parts['scheme']=="https") ? 443 : 80)),
            "",
            ""
        );

        return implode("\n", $requestParts);
    }

    static public function validateMacAuthorizationHeader(User $user, $currentUrl, $method = 'POST')
    {
        $auth = self::getMacAuthorizationHeaderFromRequest();

        if ($auth['id'] != $user->macKey) {
            throw new \RuntimeException("Mac ID does not match.");
        }

        $normalizedRequestString = self::getNormalizedRequestString($auth['ts'], $auth['nonce'], $method, $currentUrl);
        $mac = base64_encode(hash_hmac('sha256', $normalizedRequestString, $user->macSecret, true));

        if ($auth['mac'] != $mac) {
            throw new \RuntimeException("Mac Key does not match");
        }
    }

    static public function getMacAuthorizationHeaderFromRequest()
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return self::parseMacAuthorizationHeader($_SERVER['HTTP_AUTHORIZATION']);
        }

        if ( ! method_exists('apache_request_headers')) {
            throw new \RuntimeException("cannot find Authorization header.");
        }

        $headers = apache_request_headers();

        if (!isset($headers["Authorization"])) {
            throw new \RuntimeException("Cannot find Authorization header.");
        }

        return self::parseMacAuthorizationHeader($headers['Authorization']);
    }

    static public function parseMacAuthorizationHeader($headerValue)
    {
        $parts   = explode(" ", $headerValue);
        $options = array();

        if ($parts[0] != "MAC") {
            throw new \RuntimeException("Not a valid MAC Authorization header.");
        }

        foreach ($parts as $part) {
            if (strpos($part, "=") !== false) {
                list($key, $value) = explode("=", $part, 2);
                $value = trim($value, '",');
                $options[$key] = $value;
            }
        }

        return $options;
    }
}

