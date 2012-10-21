<?php

namespace TentPHP;

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
        $normalizedRequestString = implode("\n", $requestParts);

        $mac = base64_encode(hash_hmac('sha256', $normalizedRequestString, $macKey, true));

        return sprintf(
            'MAC id="%s", ts="%s", nonce="%s", mac="%s"',
            $macKeyId,
            $ts,
            $nonce,
            $mac
        );
    }
}

