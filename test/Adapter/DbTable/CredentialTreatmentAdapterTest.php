<?php

declare(strict_types=1);

namespace LaminasTest\Authentication\Adapter\DbTable;

use Laminas\Authentication;
use Laminas\Authentication\Adapter\DbTable\CredentialTreatmentAdapter;
use Laminas\Authentication\Adapter\DbTable\Exception\RuntimeException;
use Laminas\Db\Adapter\Adapter as DbAdapter;
use Laminas\Db\Sql\Select;
use PDO;
use PHPUnit\Framework\TestCase;
use stdClass;

use function serialize;

use function array_pop;
use function count;
use function extension_loaded;
use function getenv;
use function in_array;
use function serialize;

/**
 * @group      Laminas_Auth
 * @group      Laminas_Db_Table
 */
class CredentialTreatmentAdapterTest extends TestCase
{
    /**
     * SQLite database connection
     *
     * @var DbAdapter
     */
    protected $db;

    /**
     * Database table authentication adapter
     *
     * @var CredentialTreatmentAdapter
     */
    protected $adapter;

    /**
     * Set up test configuration
     */
    public function setUp(): void
    {
        if (! getenv('TESTS_LAMINAS_AUTH_ADAPTER_DBTABLE_PDO_SQLITE_ENABLED')) {
            $this->markTestSkipped('Tests are not enabled in phpunit.xml');
        } elseif (! extension_loaded('pdo')) {
            $this->markTestSkipped('PDO extension is not loaded');
<<<<<<< HEAD
        } elseif (! in_array('sqlite', \PDO::getAvailableDrivers())) {
=======
            return;
        } elseif (! in_array('sqlite', PDO::getAvailableDrivers())) {
>>>>>>> f65af83 (Add automated phpcbf fixes)
            $this->markTestSkipped('SQLite PDO driver is not available');
        }

        $this->setupDbAdapter();
        $this->setupAuthAdapter();
    }

    public function tearDown(): void
    {
        $this->adapter = null;

        $this->db->query('DROP TABLE [users]');
        $this->db = null;
    }

    /**
     * Ensures expected behavior for authentication success
     */
    public function testAuthenticateSuccess(): void
    {
        $this->adapter->setIdentity('my_username');
        $this->adapter->setCredential('my_password');
        $result = $this->adapter->authenticate();
        $this->assertTrue($result->isValid());
    }

    /**
     * Ensures expected behavior for authentication success
     */
    public function testAuthenticateSuccessWithTreatment(): void
    {
        $this->adapter = new CredentialTreatmentAdapter($this->db, 'users', 'username', 'password', '?');
        $this->adapter->setIdentity('my_username');
        $this->adapter->setCredential('my_password');
        $result = $this->adapter->authenticate();
        $this->assertTrue($result->isValid());
    }

    /**
     * Ensures expected behavior for for authentication failure
     * reason: Identity not found.
     */
    public function testAuthenticateFailureIdentityNotFound(): void
    {
        $this->adapter->setIdentity('non_existent_username');
        $this->adapter->setCredential('my_password');

        $result = $this->adapter->authenticate();
        $this->assertEquals(Authentication\Result::FAILURE_IDENTITY_NOT_FOUND, $result->getCode());
    }

    /**
     * Ensures expected behavior for for authentication failure
     * reason: Identity not found.
     */
    public function testAuthenticateFailureIdentityAmbiguous(): void
    {
        $sqlInsert = 'INSERT INTO users (username, password, real_name) '
            . 'VALUES ("my_username", "my_password", "My Real Name")';
        $this->db->query($sqlInsert, DbAdapter::QUERY_MODE_EXECUTE);

        $this->adapter->setIdentity('my_username');
        $this->adapter->setCredential('my_password');

        $result = $this->adapter->authenticate();
        $this->assertEquals(Authentication\Result::FAILURE_IDENTITY_AMBIGUOUS, $result->getCode());
    }

    /**
     * Ensures expected behavior for authentication failure because of a bad password
     */
    public function testAuthenticateFailureInvalidCredential(): void
    {
        $this->adapter->setIdentity('my_username');
        $this->adapter->setCredential('my_password_bad');
        $result = $this->adapter->authenticate();
        $this->assertFalse($result->isValid());
    }

    /**
     * Ensures that getResultRowObject() works for successful authentication
     */
    public function testGetResultRow(): void
    {
        $this->adapter->setIdentity('my_username');
        $this->adapter->setCredential('my_password');
        $this->adapter->authenticate();
        $resultRow = $this->adapter->getResultRowObject();
        $this->assertEquals($resultRow->username, 'my_username');
    }

    /**
     * Ensure that ResultRowObject returns only what told to be included
     */
    public function testGetSpecificResultRow(): void
    {
        $this->adapter->setIdentity('my_username');
        $this->adapter->setCredential('my_password');
        $this->adapter->authenticate();
        $resultRow = $this->adapter->getResultRowObject(['username', 'real_name']);
        $this->assertEquals(
            'O:8:"stdClass":2:{s:8:"username";s:11:"my_username";s:9:"real_name";s:12:"My Real Name";}',
            serialize($resultRow)
        );
    }

    /**
     * Ensure that ResultRowObject returns an object has specific omissions
     */
    public function testGetOmittedResultRow(): void
    {
        $this->adapter->setIdentity('my_username');
        $this->adapter->setCredential('my_password');
        $this->adapter->authenticate();
        $resultRow = $this->adapter->getResultRowObject(null, 'password');
        $expected = new stdClass();
        $expected->id = 1;
        $expected->username = 'my_username';
        $expected->real_name = 'My Real Name';
        $this->assertEquals($expected, $resultRow);
    }

    /**
     * @group Laminas-5957
     */
    public function testAdapterCanReturnDbSelectObject(): void
    {
        $this->assertInstanceOf(Select::class, $this->adapter->getDbSelect());
    }

    /**
     * @group Laminas-5957
     */
    public function testAdapterCanUseModifiedDbSelectObject(): void
    {
        $select = $this->adapter->getDbSelect();
        $select->where('1 = 0');
        $this->adapter->setIdentity('my_username');
        $this->adapter->setCredential('my_password');

        $result = $this->adapter->authenticate();
        $this->assertEquals(Authentication\Result::FAILURE_IDENTITY_NOT_FOUND, $result->getCode());
    }

    /**
     * @group Laminas-5957
     */
    public function testAdapterReturnsASelectObjectWithoutAuthTimeModificationsAfterAuth(): void
    {
        $select = $this->adapter->getDbSelect();
        $select->where('1 = 1');
        $this->adapter->setIdentity('my_username');
        $this->adapter->setCredential('my_password');
        $this->adapter->authenticate();
        $selectAfterAuth = $this->adapter->getDbSelect();
        $whereParts      = $selectAfterAuth->where->getPredicates();
        $this->assertEquals(1, count($whereParts));

        $lastWherePart  = array_pop($whereParts);
        $expressionData = $lastWherePart[1]->getExpressionData();
        $this->assertEquals('1 = 1', $expressionData[0][0]);
    }

    /**
     * Ensure that exceptions are caught
     */
    public function testCatchExceptionNoTable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A table must be supplied for');
        $adapter = new CredentialTreatmentAdapter($this->db);
        $adapter->authenticate();
    }

    /**
     * Ensure that exceptions are caught
     */
    public function testCatchExceptionNoIdentityColumn(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('An identity column must be supplied for the');
        $adapter = new CredentialTreatmentAdapter($this->db, 'users');
        $adapter->authenticate();
    }

    /**
     * Ensure that exceptions are caught
     */
    public function testCatchExceptionNoCredentialColumn(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A credential column must be supplied');
        $adapter = new CredentialTreatmentAdapter($this->db, 'users', 'username');
        $adapter->authenticate();
    }

    /**
     * Ensure that exceptions are caught
     */
    public function testCatchExceptionNoIdentity(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A value for the identity was not provided prior');
        $this->adapter->authenticate();
    }

    /**
     * Ensure that exceptions are caught
     */
    public function testCatchExceptionNoCredential(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A credential value was not provided prior');
        $this->adapter->setIdentity('my_username');
        $this->adapter->authenticate();
    }

    /**
     * Ensure that exceptions are caught
     */
    public function testCatchExceptionBadSql(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The supplied parameters to');
        $this->adapter->setTableName('bad_table_name');
        $this->adapter->setIdentity('value');
        $this->adapter->setCredential('value');
        $this->adapter->authenticate();
    }

    /**
     * Test to see same usernames with different passwords can not authenticate
     * when flag is not set. This is the current state of
     * Laminas_Auth_Adapter_DbTable (up to Laminas 1.10.6)
     *
     * @group Laminas-7289
     */
    public function testEqualUsernamesDifferentPasswordShouldNotAuthenticateWhenFlagIsNotSet(): void
    {
        $sqlInsert = 'INSERT INTO users (username, password, real_name) '
                   . 'VALUES ("my_username", "my_otherpass", "Test user 2")';
        $this->db->query($sqlInsert, DbAdapter::QUERY_MODE_EXECUTE);

        // test if user 1 can authenticate
        $this->adapter->setIdentity('my_username')
                       ->setCredential('my_password');
        $result = $this->adapter->authenticate();
        $this->assertContains(
            'More than one record matches the supplied identity.',
            $result->getMessages()
        );
        $this->assertFalse($result->isValid());
    }

    /**
     * Test to see same usernames with different passwords can authenticate when
     * a flag is set
     *
     * @group Laminas-7289
     */
    public function testEqualUsernamesDifferentPasswordShouldAuthenticateWhenFlagIsSet(): void
    {
        $sqlInsert = 'INSERT INTO users (username, password, real_name) '
                   . 'VALUES ("my_username", "my_otherpass", "Test user 2")';
        $this->db->query($sqlInsert, DbAdapter::QUERY_MODE_EXECUTE);

        // test if user 1 can authenticate
        $this->adapter->setIdentity('my_username')
                       ->setCredential('my_password')
                       ->setAmbiguityIdentity(true);
        $result = $this->adapter->authenticate();
        $this->assertNotContains(
            'More than one record matches the supplied identity.',
            $result->getMessages()
        );
        $this->assertTrue($result->isValid());
        $this->assertEquals('my_username', $result->getIdentity());

        $this->adapter = null;
        $this->setupAuthAdapter();

        // test if user 2 can authenticate
        $this->adapter->setIdentity('my_username')
                       ->setCredential('my_otherpass')
                       ->setAmbiguityIdentity(true);
        $result2 = $this->adapter->authenticate();
        $this->assertNotContains(
            'More than one record matches the supplied identity.',
            $result->getMessages()
        );
        $this->assertTrue($result2->isValid());
        $this->assertEquals('my_username', $result2->getIdentity());
    }

    protected function setupDbAdapter($optionalParams = []): void
    {
        $params = [
            'driver' => 'pdo_sqlite',
            'dbname' => getenv('TESTS_LAMINAS_AUTH_ADAPTER_DBTABLE_PDO_SQLITE_DATABASE'),
        ];

        if (! empty($optionalParams)) {
            $params['options'] = $optionalParams;
        }

        $this->db = new DbAdapter($params);

        $sqlCreate = 'CREATE TABLE IF NOT EXISTS [users] ( '
                   . '[id] INTEGER  NOT NULL PRIMARY KEY, '
                   . '[username] VARCHAR(50) NOT NULL, '
                   . '[password] VARCHAR(32) NULL, '
                   . '[real_name] VARCHAR(150) NULL)';
        $this->db->query($sqlCreate, DbAdapter::QUERY_MODE_EXECUTE);

        $sqlDelete = 'DELETE FROM users';
        $this->db->query($sqlDelete, DbAdapter::QUERY_MODE_EXECUTE);

        $sqlInsert = 'INSERT INTO users (username, password, real_name) '
                   . 'VALUES ("my_username", "my_password", "My Real Name")';
        $this->db->query($sqlInsert, DbAdapter::QUERY_MODE_EXECUTE);
    }

    protected function setupAuthAdapter(): void
    {
        $this->adapter = new CredentialTreatmentAdapter($this->db, 'users', 'username', 'password');
    }
}
