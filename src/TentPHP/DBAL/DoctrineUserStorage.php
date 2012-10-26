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

use TentPHP\UserStorage;
use TentPHP\User;
use TentPHP\Util\Encryption;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * UserStorage with Doctrine DBAL backend.
 */
class DoctrineUserStorage implements UserStorage
{
    /**
     * @var Connection
     */
    private $conn;

    /**
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * Identity Map
     *
     * @var array
     */
    private $users = array();

    /**
     * @var array
     */
    private $fieldMappings = array(
        'entity'           => array('columnName' => 'entity', 'type' => 'string'),
        'serverUrl'        => array('columnName' => 'server_url', 'type' => 'string'),
        'appId'            => array('columnName' => 'app_id', 'type' => 'string'),
        'appMacKey'        => array('columnName' => 'app_mac_key', 'type' => 'tentecstring'),
        'appMacSecret'     => array('columnName' => 'app_mac_secret', 'type' => 'tentecstring'),
        'appMacAlgorithm'  => array('columnName' => 'app_mac_algorithm', 'type' => 'string'),
        'macKey'           => array('columnName' => 'mac_key', 'type' => 'tentecstring', 'options' => array('notnull' => false)),
        'macSecret'        => array('columnName' => 'mac_secret', 'type' => 'tentecstring', 'options' => array('notnull' => false)),
        'macAlgorithm'     => array('columnName' => 'mac_algorithm', 'type' => 'string', 'options' => array('notnull' => false)),
        'tokenType'        => array('columnName' => 'token_type', 'type' => 'string', 'options' => array('notnull' => false)),
        'profileInfoTypes' => array('columnName' => 'profile_info_types', 'type' => 'json_array', 'options' => array('notnull' => false)),
        'postTypes'        => array('columnName' => 'post_types', 'type' => 'json_array', 'options' => array('notnull' => false)),
        'notificationUrl'  => array('columnName' => 'notification_url', 'type' => 'string', 'options' => array('notnull' => false)),
    );

    public function __construct(Connection $conn, $encryptionKey)
    {
        $this->conn     = $conn;
        $this->platform = $conn->getDatabasePlatform();

        self::registerTentEncryptionStringType();
    }

    /**
     * Register Tent Encryption String
     */
    static public function registerTentEncryptionStringType()
    {
        if (!Type::hasType('tentecstring')) {
            Type::addType('tentecstring', __NAMESPACE__ . '\\EncryptedString');
            Type::getType('tentecstring')->setEncryption(new Encryption($encryptionKey));
        }
    }

    /**
     * Load a user object from an entity url.
     *
     * @param string $entityUrl
     * @return User|null
     */
    public function load($entityUrl)
    {
        $sql = "SELECT * FROM tentc_user WHERE entity = ?";
        $row = $this->conn->fetchAssoc($sql, array($entityUrl));

        if ( ! $row) {
            return null;
        }

        $this->users[$entityUrl] = $row['id'];
        unset ($row['id']);

        $user = new User($entityUrl);

        foreach ($this->fieldMappings as $fieldName => $mapping) {
            $value = Type::getType($mapping['type'])
                ->convertToPHPValue($row[$mapping['columnName']], $this->platform);
            $user->$fieldName = $value;
        }

        return $user;
    }

    /**
     * Save a user object
     *
     * @param User $user
     */
    public function save(User $user)
    {
        $data = array();

        foreach ($this->fieldMappings as $fieldName => $mapping) {
            $value = $user->$fieldName;
            $data[$mapping['columnName']] =
                Type::getType($mapping['type'])->convertToDatabaseValue($value, $this->platform);
        }

        if (isset($this->users[$user->entity])) {
            $this->conn->update('tentc_user', $data, array('id' => $this->users[$user->entity]));
        } else {
            $this->conn->insert('tentc_user', $data);
            $this->users[$user->entity] = $this->conn->lastInsertId();
        }
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
        $table = $schema->createTable('tentc_user');
        $table->addColumn('id', 'integer', array('autoincrement' => true));

        foreach ($this->fieldMappings as $field => $mapping) {
            $options = isset($mapping['options']) ? $mapping['options'] : array();
            $table->addColumn($mapping['columnName'], $mapping['type'], $options);
        }

        $table->setPrimaryKey(array('id'));
        $table->addUniqueIndex(array('entity'));
    }
}

