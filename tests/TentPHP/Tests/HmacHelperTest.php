<?php

namespace TentPHP\Tests;

use TentPHP\HmacHelper;

class HmacHelperTest extends TestCase
{
    static public function dataGenerate()
    {
        return array(
            array(
                'GET', 'http://www.example.com/resource/1?b=1&a=2', null, 'h480djs93hd8', '489dks293j39',
                'MAC id="h480djs93hd8", ts="1336363200", nonce="dj83hs9s", mac="lZWElzYo0LJevKwPpSXTWl73KR/mPzI/FVe/XLAv6GE="'
            ),
            array(
                'POST', 'http://example.com/resource/1?b=1&a=2', 'asdf\nasdf', 'h480djs93hd8', '489dks293j39',
                'MAC id="h480djs93hd8", ts="1336363200", nonce="dj83hs9s", mac="Xt51rtHY5F+jxKXMCoiKgXa3geofWW/7RANCXB1yu08="'
            )
        );
    }

    /**
     * @dataProvider dataGenerate
     */
    public function testGenerate($method, $url, $body, $macKeyId, $macKey, $expected)
    {
        $time  = 1336363200;
        $nonce = 'dj83hs9s';
        $this->assertEquals($expected, HmacHelper::getAuthorizationHeader($method, $url, $macKeyId, $macKey, $time, $nonce));
    }
}

