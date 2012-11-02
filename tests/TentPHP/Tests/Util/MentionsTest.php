<?php

namespace TentPHP\Tests\Util;

use TentPHP\Util\Mentions;

class MentionsTest extends \PHPUnit_Framework_TestCase
{
    private $mentions;

    public function setUp()
    {
        $this->mentions = new Mentions();
    }

    public function testParseMultiple()
    {
        $data = $this->mentions->extractMentions("^beberlei ^lala", "https://beberlei.tent.is");

        $this->assertEquals(array(
            array("entity" => "https://beberlei.tent.is", "pos" => 0, "length" => 9),
            array("entity" => "https://lala.tent.is", "pos" => 10, "length" => 5)
        ), $data);
    }

    public function testParseAtTheBeginning()
    {
        $data = $this->mentions->extractMentions("^beberlei", "https://beberlei.tent.is");

        $this->assertEquals(array(array("entity" => "https://beberlei.tent.is", "pos" => 0, "length" => 9)), $data);
    }

    public function testParseFullIdentifer()
    {
        $data = $this->mentions->extractMentions("^https://foo.bar.is", "https://beberlei.tent.is");

        $this->assertEquals(array(array('entity' => 'https://foo.bar.is', 'pos' => 0, 'length' => 19)), $data);
    }

    public function testTrimSignsAtTheEnd()
    {
        $data = $this->mentions->extractMentions("^beberlei? ^beberlei2! ^beberlei3.", "https://beberlei.tent.is");

        $this->assertEquals(array(
            array("entity" => "https://beberlei.tent.is", "pos" => 0, "length" => 9),
            array("entity" => "https://beberlei2.tent.is", "pos" => 11, "length" => 10),
            array("entity" => "https://beberlei3.tent.is", "pos" => 23, "length" => 10),
        ), $data);
    }

    public function testParseErrorCase()
    {
        $data = $this->mentions->extractMentions("^shawnj.tent.is", "https://beberlei.tent.is");

        $this->assertEquals(array(array('entity' => 'https://shawnj.tent.is', 'pos' => 0, 'length' => 15)), $data);
    }

    public function testErrorExternal()
    {
        $data = $this->mentions->extractMentions("^jeena.net", "https://beberlei.tent.is");

        $this->assertEquals(array(array('entity' => 'http://jeena.net', 'pos' => 0, 'length' => 10)), $data);
    }

    public function testNormalizeMention()
    {
        $this->assertEquals('http://jeena.net', $this->mentions->normalize('jeena.net', 'https://beberlei.tent.is'));
        $this->assertEquals('https://foo.tent.is', $this->mentions->normalize('foo', 'https://beberlei.tent.is'));
    }
}

