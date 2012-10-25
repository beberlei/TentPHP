<?php

namespace TentPHP\Tests\DBAL;

use Doctrine\DBAL\DriverManager;
use TentPHP\DBAL\DoctrineUserStorage;
use TentPHP\User;

class DoctrineUserStorageTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->conn = DriverManager::getConnection(array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ));
        $this->userStorage = new DoctrineUserStorage($this->conn);

        $schema = $this->userStorage->createSchema();
        foreach ($schema->toSQL($this->conn->getDatabasePlatform()) as $sql) {
            $this->conn->exec($sql);
        }
    }

    public function testLoadNoUserFoundNull()
    {
        $this->assertNull($this->userStorage->load("lala"));
    }

    public function testUserSaveThenLoad()
    {
        $user = new User("lala");

        $user->serverUrl       = "server";
        $user->appId           = 1234;
        $user->appMacKey       = "abdecf";
        $user->appMacSecret    = "lj";
        $user->appMacAlgorithm = "sha-256";

        $this->userStorage->save($user);

        $loadedUser = $this->userStorage->load("lala");

        $this->assertNotNull($loadedUser);
        $this->assertEquals($loadedUser, $user);
    }
}

