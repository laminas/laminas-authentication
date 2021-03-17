<?php

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */
namespace LaminasTest\Authentication\Adapter\DbTable;

use Laminas\Authentication;
use Laminas\Authentication\Adapter\DbTable\AbstractAdapter;
use Laminas\Authentication\Adapter\DbTable\CredentialTreatmentAdapter;
use Laminas\Authentication\Adapter\DbTable\Exception\RuntimeException;
use Laminas\Db\Adapter\Adapter as DbAdapter;
use PHPUnit\Framework\TestCase;

/**
 * @group Laminas_Auth
 * @group Laminas_Db_Table
 */
class CredentialTreatmentAdapterDb2Test extends TestCase
{
    /**
     * IbmDb2 database connection
     *
     * @var DbAdapter
     */
    protected $db;

    /**
     * Database table authentication adapter
     *
     * @var AbstractAdapter
     */
    protected $adapter;

    /**
     * Database adapter configuration
     *
     * @var array
     */
    protected $dbAdapterParams;

    /**
     * DB2 table to use for testing
     *
     * @var string in the format 'LIBRARY_NAME.TABLE_NAME' or
     */
    protected $tableName;

    /**
     * Set up test configuration
     */
    public function setUp(): void
    {
        if (! getenv('TESTS_LAMINAS_AUTH_ADAPTER_DBTABLE_DB2_ENABLED')) {
            $this->markTestSkipped('Tests are not enabled in phpunit.xml');
        }

        if (! extension_loaded('ibm_db2')) {
            $this->markTestSkipped('ibm_db2 extension is not loaded');
        }

        $this->dbAdapterParams = [
            'driver'           => 'IbmDb2',
            'dbname'           => getenv('TESTS_LAMINAS_AUTH_ADAPTER_DBTABLE_DB2_DATABASE'),
            'username'         => getenv('TESTS_LAMINAS_AUTH_ADAPTER_DBTABLE_DB2_USERNAME'),
            'password'         => getenv('TESTS_LAMINAS_AUTH_ADAPTER_DBTABLE_DB2_PASSWORD'),
            'platform_options' => ['quote_identifiers' => false],
            'driver_options'   => [],
        ];
        $this->dbAdapterParams['driver_options']['i5_commit'] = constant('DB2_I5_TXN_NO_COMMIT');
        $this->dbAdapterParams['driver_options']['i5_naming'] = constant('DB2_I5_NAMING_OFF');
        $this->tableName = getenv('TESTS_LAMINAS_AUTH_ADAPTER_DBTABLE_DB2_CREDENTIAL_TABLE');

        $this->setupDbAdapter();
        $this->setupAuthAdapter();
    }

    public function tearDown(): void
    {
        $this->authAdapter = null;

        // BIND, REBIND or DROP operations fail when the package is in use
        // by the same application process
        $this->db->getDriver()
            ->getConnection()
            ->disconnect();

        $this->db = new DbAdapter($this->dbAdapterParams);

        $this->db->query("DROP TABLE {$this->tableName}", DbAdapter::QUERY_MODE_EXECUTE);
        $this->db->getDriver()
            ->getConnection()
            ->disconnect();

        $this->db = null;
    }

    /**
     * Ensures expected behavior for authentication success
     *
     * @return void
     */
    public function testAuthenticateSuccess(): void
    {
        $this->authAdapter->setIdentity('my_username');
        $this->authAdapter->setCredential('my_password');
        $result = $this->authAdapter->authenticate();
        $this->assertTrue($result->isValid());
    }

    /**
     * Ensures expected behavior for authentication success
     *
     * @return void
     */
    public function testAuthenticateSuccessWithTreatment(): void
    {
        $this->authAdapter = new CredentialTreatmentAdapter($this->db, $this->tableName, 'username', 'password', '?');
        $this->authAdapter->setIdentity('my_username');
        $this->authAdapter->setCredential('my_password');
        $result = $this->authAdapter->authenticate();
        $this->assertTrue($result->isValid());
    }

    /**
     * Ensures expected behavior for for authentication failure
     * reason: Identity not found.
     *
     * @return void
     */
    public function testAuthenticateFailureIdentityNotFound(): void
    {
        $this->authAdapter->setIdentity('non_existent_username');
        $this->authAdapter->setCredential('my_password');

        $result = $this->authAdapter->authenticate();
        $this->assertEquals(Authentication\Result::FAILURE_IDENTITY_NOT_FOUND, $result->getCode());
    }

    /**
     * Ensures expected behavior for for authentication failure
     * reason: Identity ambiguous.
     *
     * @return void
     */
    public function testAuthenticateFailureIdentityAmbiguous(): void
    {
        $sqlInsert = "INSERT INTO {$this->tableName} (id, username, password, real_name) "
            . "VALUES (2, 'my_username', 'my_password', 'My Real Name')";
        $this->db->query($sqlInsert, DbAdapter::QUERY_MODE_EXECUTE);

        $this->authAdapter->setIdentity('my_username');
        $this->authAdapter->setCredential('my_password');

        $result = $this->authAdapter->authenticate();
        $this->assertEquals(Authentication\Result::FAILURE_IDENTITY_AMBIGUOUS, $result->getCode());
    }

    /**
     * Ensures expected behavior for authentication failure because of a bad password
     *
     * @return void
     */
    public function testAuthenticateFailureInvalidCredential(): void
    {
        $this->authAdapter->setIdentity('my_username');
        $this->authAdapter->setCredential('my_password_bad');
        $result = $this->authAdapter->authenticate();
        $this->assertFalse($result->isValid());
    }

    /**
     * Ensures that getResultRowObject() works for successful authentication
     *
     * @return void
     */
    public function testGetResultRow(): void
    {
        $this->authAdapter->setIdentity('my_username');
        $this->authAdapter->setCredential('my_password');
        $this->authAdapter->authenticate();
        $resultRow = $this->authAdapter->getResultRowObject();
        // Since we did not set db2_attr_case, column name is upper case, as expected
        $this->assertEquals($resultRow->USERNAME, 'my_username');
    }

    /**
     * Ensure that ResultRowObject returns only what told to be included
     *
     * @return void
     */
    public function testGetSpecificResultRow(): void
    {
        $this->authAdapter->setIdentity('my_username');
        $this->authAdapter->setCredential('my_password');
        $this->authAdapter->authenticate();
        // Since we did not set db2_attr_case, column names will be upper case, as expected
        $resultRow = $this->authAdapter->getResultRowObject([
            'USERNAME',
            'REAL_NAME'
        ]);
        $this->assertEquals(
            'O:8:"stdClass":2:{s:8:"USERNAME";s:11:"my_username";s:9:"REAL_NAME";s:12:"My Real Name";}',
            serialize($resultRow)
        );
    }

    /**
     * Ensure that ResultRowObject returns an object that has specific omissions
     *
     * @return void
     */
    public function testGetOmittedResultRow(): void
    {
        $this->authAdapter->setIdentity('my_username');
        $this->authAdapter->setCredential('my_password');
        $this->authAdapter->authenticate();
        // Since we did not set db2_attr_case, column names will be upper case, as expected
        $resultRow = $this->authAdapter->getResultRowObject(null, 'PASSWORD');
        $this->assertEquals(
            'O:8:"stdClass":3:{s:2:"ID";i:1;s:8:"USERNAME";s:11:"my_username";s:9:"REAL_NAME";s:12:"My Real Name";}',
            serialize($resultRow)
        );
    }

    /**
     * @group Laminas-5957
     *
     * @return void
     */
    public function testAdapterCanReturnDbSelectObject(): void
    {
        $this->assertInstanceOf('Laminas\Db\Sql\Select', $this->authAdapter->getDbSelect());
    }

    /**
     * @group Laminas-5957
     *
     * @return void
     */
    public function testAdapterCanUseModifiedDbSelectObject(): void
    {
        $select = $this->authAdapter->getDbSelect();
        $select->where('1 = 0');
        $this->authAdapter->setIdentity('my_username');
        $this->authAdapter->setCredential('my_password');

        $result = $this->authAdapter->authenticate();
        $this->assertEquals(Authentication\Result::FAILURE_IDENTITY_NOT_FOUND, $result->getCode());
    }

    /**
     * @group Laminas-5957
     *
     * @return void
     */
    public function testAdapterReturnsASelectObjectWithoutAuthTimeModificationsAfterAuth(): void
    {
        $select = $this->authAdapter->getDbSelect();
        $select->where('1 = 1');
        $this->authAdapter->setIdentity('my_username');
        $this->authAdapter->setCredential('my_password');
        $this->authAdapter->authenticate();
        $selectAfterAuth = $this->authAdapter->getDbSelect();
        $whereParts = $selectAfterAuth->where->getPredicates();
        $this->assertEquals(1, count($whereParts));

        $lastWherePart = array_pop($whereParts);
        $expressionData = $lastWherePart[1]->getExpressionData();
        $this->assertEquals('1 = 1', $expressionData[0][0]);
    }

    /**
     * Ensure that exceptions are caught
     *
     * @return void
     */
    public function testCatchExceptionNoTable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A table must be supplied for');
        $adapter = new CredentialTreatmentAdapter($this->db);
        $adapter->authenticate();
    }

    /**
     * Ensure that exceptions are thrown
     *
     * @return void
     */
    public function testCatchExceptionNoIdentityColumn(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('An identity column must be supplied for the');
        $adapter = new CredentialTreatmentAdapter($this->db, 'users');
        $adapter->authenticate();
    }

    /**
     * Ensure that exceptions are thrown
     *
     * @return void
     */
    public function testCatchExceptionNoCredentialColumn(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A credential column must be supplied');
        $adapter = new CredentialTreatmentAdapter($this->db, 'users', 'username');
        $adapter->authenticate();
    }

    /**
     * Ensure that exceptions are thrown
     *
     * @return void
     */
    public function testCatchExceptionNoIdentity(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A value for the identity was not provided prior');
        $this->authAdapter->authenticate();
    }

    /**
     * Ensure that exceptions are thrown
     *
     * @return void
     */
    public function testCatchExceptionNoCredential(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A credential value was not provided prior');
        $this->authAdapter->setIdentity('my_username');
        $this->authAdapter->authenticate();
    }

    /**
     * Ensure that exceptions are thrown
     *
     * @return void
     */
    public function testCatchExceptionBadSql(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The supplied parameters to');
        $this->authAdapter->setTableName('bad_table_name');
        $this->authAdapter->setIdentity('value');
        $this->authAdapter->setCredential('value');
        $this->authAdapter->authenticate();
    }

    /**
     * Test to see same usernames with different passwords can not authenticate
     * when flag is not set.
     * This is the current state of
     * Laminas_Auth_Adapter_DbTable (up to Laminas 1.10.6)
     *
     * @group Laminas-7289
     *
     * @return void
     */
    public function testEqualUsernamesDifferentPasswordShouldNotAuthenticateWhenFlagIsNotSet(): void
    {
        $sqlInsert = "INSERT INTO $this->tableName (id, username, password, real_name) "
                   . "VALUES (2, 'my_username', 'my_otherpass', 'Test user 2')";
        $this->db->query($sqlInsert, DbAdapter::QUERY_MODE_EXECUTE);

        // test if user 1 can authenticate
        $this->authAdapter->setIdentity('my_username')->setCredential('my_password');
        $result = $this->authAdapter->authenticate();
        $this->assertContains('More than one record matches the supplied identity.', $result->getMessages());
        $this->assertFalse($result->isValid());
    }

    /**
     * Test to see same usernames with different passwords can authenticate when
     * a flag is set
     *
     * @group Laminas-7289
     *
     * @return void
     */
    public function testEqualUsernamesDifferentPasswordShouldAuthenticateWhenFlagIsSet(): void
    {
        $sqlInsert = "INSERT INTO $this->tableName (id, username, password, real_name) "
                   . "VALUES (2, 'my_username', 'my_otherpass', 'Test user 2')";
        $this->db->query($sqlInsert, DbAdapter::QUERY_MODE_EXECUTE);

        // test if user 1 can authenticate
        $this->authAdapter->setIdentity('my_username')
            ->setCredential('my_password')
            ->setAmbiguityIdentity(true);
        $result = $this->authAdapter->authenticate();
        $this->assertNotContains('More than one record matches the supplied identity.', $result->getMessages());
        $this->assertTrue($result->isValid());
        $this->assertEquals('my_username', $result->getIdentity());

        $this->authAdapter = null;
        $this->setupAuthAdapter();

        // test if user 2 can authenticate
        $this->authAdapter->setIdentity('my_username')
            ->setCredential('my_otherpass')
            ->setAmbiguityIdentity(true);
        $result2 = $this->authAdapter->authenticate();
        $this->assertNotContains('More than one record matches the supplied identity.', $result->getMessages());
        $this->assertTrue($result2->isValid());
        $this->assertEquals('my_username', $result2->getIdentity());
    }

    protected function setupDbAdapter($optionalParams = []): void
    {
        $this->createDbAdapter($optionalParams);

        $sqlInsert = "INSERT INTO $this->tableName (id, username, password, real_name) "
                   . "VALUES (1, 'my_username', 'my_password', 'My Real Name')";

        $this->db->query($sqlInsert, DbAdapter::QUERY_MODE_EXECUTE);
    }

    protected function createDbAdapter($optionalParams = []): void
    {
        if (! empty($optionalParams)) {
            $this->dbAdapterParams['options'] = $optionalParams;
        }

        $this->db = new DbAdapter($this->dbAdapterParams);

        $sqlCreate = "CREATE TABLE {$this->tableName} ( "
                   . 'id INTEGER NOT NULL, '
                   . 'username VARCHAR(50) NOT NULL, '
                   . 'password VARCHAR(32), '
                   . 'real_name VARCHAR(150), '
                   . 'PRIMARY KEY(id))';

        $this->db->query($sqlCreate, DbAdapter::QUERY_MODE_EXECUTE);
    }

    protected function setupAuthAdapter(): void
    {
        $this->authAdapter = new CredentialTreatmentAdapter(
            $this->db,
            $this->tableName,
            'username',
            'password'
        );
    }
}
