<?php

namespace TentPHP\DBAL;

use TentPHP\ApplicationState;
use TentPHP\ApplicationConfig;
use TentPHP\Application;
use TentPHP\UserAuthorization;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

/**
 * Application State with Doctrine DBAL backend.
 */
class DoctrineDBALState implements ApplicationState
{
    /**
     * @var Connection
     */
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Create a new schema with the tables required for the Doctrine DBAL AppState
     *
     * @return Schema
     */
    public function createSchema()
    {
        $schema = new Schema();
        $this->extendSchema($schema);

        return $schema;
    }

    /**
     * Add tent client tables to an existing schema instance
     *
     * @param Schema $schema
     */
    public function extendSchema(Schema $schema)
    {
        $table = $schema->createTable('tentc_servers');
        $table->addColumn('id', 'integer', array('auto_increment' => true));
        $table->addColumn('entity_url', 'string');
        $table->addColumn('server_url', 'string');
        $table->setPrimaryKey(array('id'));
        $table->addUniqueIndex(array('entity_url', 'server_url'));

        $table = $schema->createTable('tentc_application_config');
        $table->addColumn('id', 'integer', array('auto_increment' => true));
        $table->addColumn('name', 'string');
        $table->addColumn('server_url', 'string');
        $table->addColumn('application_id', 'string');
        $table->addColumn('mac_key_id', 'string');
        $table->addColumn('mac_key', 'string');
        $table->addColumn('mac_algorithm', 'string');
        $table->setPrimaryKey(array('id'));
        $table->addUniqueIndex(array('name', 'server_url'));

        $table = $schema->createTable('tentc_user_authorizations');
        $table->addColumn('id', 'integer', array('auto_increment' => true));
        $table->addColumn('entity_url', 'string');
        $table->addColumn('application_id', 'string');
        $table->addColumn('access_token', 'string');
        $table->addColumn('mac_key', 'string');
        $table->addColumn('mac_algorithm', 'string');
        $table->addColumn('token_type', 'string');
        $table->setPrimaryKey(array('id'));
        $table->addUniqueIndex(array('entity_url', 'application_id'));

        $table = $schema->createTable('tentc_authorize_state_tokens');
        $table->addColumn('id', 'integer', array('auto_increment' => true));
        $table->addColumn('state_token', 'string', array('unique' => true));
        $table->addColumn('entity_url', 'string');
        $table->addColumn('server_url', 'string');
        $table->setPrimaryKey(array('id'));
    }


    /**
     * Given an entity url return the tent server urls this entity is managed on.
     *
     * Return false, if no servers have been saved for this entity yet.
     *
     * @param string
     * @return array|false
     */
    public function getServers($entityUrl)
    {
        $data = $this->conn->fetchAll('SELECT server_url FROM tentc_servers WHERE entity_url = ?', array($entityUrl));

        return array_map(function($row) { return $row['server_url']; }, $data);
    }

    /**
     * Get the auth-configuration for an application and tent server pair.
     *
     * @param string $serverUrl
     * @param Application $application
     *
     * @return ApplicationConfig
     */
    public function getApplicationConfig($serverUrl, Application $application)
    {
        $sql = 'SELECT application_id as id, mac_key_id, mac_key, mac_algorithm FROM tentc_application_config WHERE server_url = ? AND name = ?';
        $row = $this->conn->fetchAssoc($sql, array($serverUrl, $application->getName()));

        if (!$row) {
            return null;
        }

        return new ApplicationConfig($row);
    }

    /**
     * Get the access token for an user entity and application config pair.
     *
     * @param string $entityUrl
     * @param ApplicationConfig $config
     *
     * @return string
     */
    public function getUserAuthorization($entityUrl, ApplicationConfig $config)
    {
        $sql = 'SELECT mac_key, access_token, mac_algorithm, token_type ' .
               'FROM tentc_user_authorizations WHERE entity_url = ? AND application_id = ?';
        $row = $this->conn->fetchAssoc($sql, array($entityUrl, $config->getApplicationId()));

        return new UserAuthorization($row);
    }

    /**
     * Save the tent servers responsible for a given entity.
     *
     * @param string $entityUrl
     * @param array $serverUrls
     */
    public function saveServers($entityUrl, array $serverUrls)
    {
        $this->conn->transactional(function ($conn) use ($entityUrl, $serverUrls) {
            $conn->delete('tentc_servers', array('entity_url' => $entityUrl));

            foreach ($serverUrls as $serverUrl) {
                $conn->insert('tentc_servers', array(
                    'entity_url' => $entityUrl,
                    'server_url' => $serverUrl,
                ));
            }
        });
    }

    /**
     * Save the application authorization config for a given server url.
     *
     * @param string $serverUrl
     * @param Application $application
     * @param ApplicationConfig $config
     */
    public function saveApplicationConfig($serverUrl, Application $application, ApplicationConfig $config)
    {
        $this->conn->insert('tentc_application_config', array(
            'server_url'     => $serverUrl,
            'name'           => $application->getName(),
            'application_id' => $config->getApplicationId(),
            'mac_key_id'     => $config->getMacKeyId(),
            'mac_key'        => $config->getMacKey(),
            'mac_algorithm'  => $config->getMacAlgorithm(),
        ));
    }

    /**
     * Save the user access token for a given entity and application pair.
     *
     * @param string $entityUrl
     * @param ApplicationConfig $config
     * @param string $token
     */
    public function saveUserAuthorization($entityUrl, ApplicationConfig $config, UserAuthorization $user)
    {
        $this->conn->insert('tentc_user_authorizations', array(
            'entity_url'     => $entityUrl,
            'application_id' => $config->getApplicationId(),
            'access_token'   => $user->getAccessToken(),
            'mac_key'        => $user->getMacKey(),
            'mac_algorithm'  => $user->getMacAlgorithm(),
            'token_type'     => $user->getTokenType(),
        ));
    }

    /**
     * Save entity and server url that a state token is used to authorize for.
     *
     * @param string $state
     * @param string $entityUrl
     * @param string $serverUrl
     */
    public function pushStateToken($state, $entityUrl, $serverUrl)
    {
        $this->conn->insert('tentc_authorize_state_tokens', array(
            'state_token' => $state,
            'entity_url'  => $entityUrl,
            'server_url'  => $serverUrl,
        ));
    }

    /**
     * Return entity and server url for a given state token. Remove token from stack.
     *
     * @param string $state
     * @return array
     */
    public function popStateToken($state)
    {
        $sql = 'SELECT * FROM tentc_authorize_state_tokens WHERE state_token = ?';
        $row = $this->conn->fetchAssoc($sql, array($state));

        if (!$row) {
            return false;
        }

        $this->conn->delete('tentc_authorize_state_tokens', array('id' => $row['id']));

        return array($row['entity_url'], $row['server_url']);
    }
}

