<?php

namespace TentPHP\Tests;

use TentPHP\Post;

class PostTest extends TestCase
{
    public function testCreate()
    {
        $post = Post::create('https://tent.io/types/post/status/v0.1.0');

        $this->assertEquals('https://tent.io/types/post/status/v0.1.0', $post->getType());
    }

    public function testAddMention()
    {
        $post = Post::create('https://tent.io/types/post/status/v0.1.0');

        $post->addMention('https://beberlei.tent.is', 'abcdefg');

        $this->assertEquals(array(
            array(
                'entity' => 'https://beberlei.tent.is',
                'post'   => 'abcdefg'
            )
        ), $post->getMentions());
    }

    public function testAddLicense()
    {
        $post = Post::create('https://tent.io/types/post/status/v0.1.0');

        $post->addLicense('http://creativecommons.org/licenses/by/3.0/');

        $this->assertEquals(array('http://creativecommons.org/licenses/by/3.0/'), $post->getLicenses());
    }

    public function testPermissions()
    {
        $post = Post::create('https://tent.io/types/post/status/v0.1.0');
        $post->markPublic()
             ->markVisibleEntity('http://beberlei.tent.is')
             ->markVisibleGroup('abcdefg');

        $this->assertEquals(array(
            'public' => true,
            'entities' => array('http://beberlei.tent.is' => true),
            'groups' => array(array('id' => 'abcdefg')),
        ), $post->getPermissions());
    }
}

