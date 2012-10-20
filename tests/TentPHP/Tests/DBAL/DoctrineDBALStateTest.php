<?php

namespace TentPHP\Tests\DBAL;

use TentPHP\Tests\TestCase;
use TentPHP\DBAL\DoctrineDBALState;
use TentPHP\Application;
use TentPHP\ApplicationConfig;
use TentPHP\UserAuthorization;
use Doctrine\DBAL\DriverManager;

class DoctrineDBALStateTest extends TestCase
{
    private $conn;
    private $state;

    public function setUp()
    {
        $this->conn = DriverManager::getConnection(array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ));
        $this->state = new DoctrineDBALState($this->conn);

        $schema = $this->state->createSchema();
        foreach ($schema->toSQL($this->conn->getDatabasePlatform()) as $sql) {
            $this->conn->exec($sql);
        }
    }

    public function testSaveGetServers()
    {
        $this->state->saveServers('https://beberlei.tent.is', array('https://tent.is/tent'));
        $this->assertEquals(array('https://tent.is/tent'), $this->state->getServers('https://beberlei.tent.is'));

        $this->state->saveServers('https://beberlei.tent.is', array('https://tent.beberlei.de/tent'));
        $this->assertEquals(array('https://tent.beberlei.de/tent'), $this->state->getServers('https://beberlei.tent.is'));
    }

    public function testSaveGetApplicationConfig()
    {
        $app    = new Application(array('name' => 'Test'));
        $config = new ApplicationConfig(array(
            'id'            => 'e12345',
            'mac_key_id'    => 'ab1234',
            'mac_key'       => 'abcdefg',
            'mac_algorithm' => 'hmac-sha-256',
        ));

        $this->state->saveApplicationConfig('https://tent.is/tent', $app, $config);
        $loadedConfig = $this->state->getApplicationConfig('https://tent.is/tent', $app);

        $this->assertEquals('e12345', $loadedConfig->getApplicationId());
        $this->assertEquals('ab1234', $loadedConfig->getMacKeyId());
        $this->assertEquals('abcdefg', $loadedConfig->getMacKey());
        $this->assertEquals('hmac-sha-256', $loadedConfig->getMacAlgorithm());
    }

    public function testSaveLoadUserAuthorization()
    {
        $config = new ApplicationConfig(array(
            'id' => 'e12345',
        ));
        $user = new UserAuthorization(array(
            'access_token'  => 'abcdefg',
            'mac_key'       => 'klimnj',
            'mac_algorithm' => 'hmac-sha-256',
            'token_type'    => 'hmac',
        ));

        $this->state->saveUserAuthorization('https://beberlei.tent.is', $config, $user);
        $loadedUserAuthorization = $this->state->getUserAuthorization('https://beberlei.tent.is', $config);
    }

    public function testPushPopStateToken()
    {
        $this->state->pushStateToken('a', 'b', 'c');
        $this->assertEquals(array('b', 'c'), $this->state->popStateToken('a'));
        $this->assertFalse($this->state->popStateToken('a'));
    }
}

