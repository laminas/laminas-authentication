<?php

declare(strict_types=1);

namespace LaminasTest\Authentication\Adapter;

use Laminas\Authentication;
use Laminas\Authentication\Adapter;
use PHPUnit\Framework\TestCase;

use function count;

class DigestTest extends TestCase
{
    protected string $filesPath = __DIR__ . '/TestAsset/Digest';

    /**
     * Ensures that the adapter throws an exception when authentication is attempted before
     * setting a required option
     */
    public function testOptionRequiredException(): void
    {
        $adapter = new Adapter\Digest();
        try {
            $adapter->authenticate();
            $this->fail('Expected Laminas_Auth_Adapter_Exception not thrown upon authentication attempt before setting '
                      . 'a required option');
        } catch (Adapter\Exception\ExceptionInterface $e) {
            $this->assertStringContainsString('must be set before authentication', $e->getMessage());
        }
    }

    /**
     * Ensures that an exception is thrown upon authenticating against a nonexistent file
     */
    public function testFileNonExistentException(): void
    {
        $adapter = new Adapter\Digest('nonexistent', 'realm', 'username', 'password');
        try {
            $adapter->authenticate();
            $this->fail('Expected Laminas_Auth_Adapter_Exception not thrown upon authenticating against nonexistent '
                      . 'file');
        } catch (Adapter\Exception\ExceptionInterface $e) {
            $this->assertStringContainsString('Cannot open', $e->getMessage());
        }
    }

    /**
     * Ensures expected behavior upon realm not found for existing user
     */
    public function testUserExistsRealmNonexistent(): void
    {
        $filename = "$this->filesPath/htdigest.1";
        $realm    = 'Nonexistent Realm';
        $username = 'someUser';
        $password = 'somePassword';

        $adapter = new Adapter\Digest($filename, $realm, $username, $password);

        $result = $adapter->authenticate();

        $this->assertFalse($result->isValid());

        $messages = $result->getMessages();
        $this->assertEquals(1, count($messages));
        $this->assertEquals($result->getCode(), Authentication\Result::FAILURE_IDENTITY_NOT_FOUND);
        $this->assertStringContainsString('combination not found', $messages[0]);

        $identity = $result->getIdentity();
        $this->assertEquals($identity['realm'], $realm);
        $this->assertEquals($identity['username'], $username);
    }

    /**
     * Ensures expected behavior upon user not found in existing realm
     */
    public function testUserNonexistentRealmExists(): void
    {
        $filename = "$this->filesPath/htdigest.1";
        $realm    = 'Some Realm';
        $username = 'nonexistentUser';
        $password = 'somePassword';

        $adapter = new Adapter\Digest($filename, $realm, $username, $password);

        $result = $adapter->authenticate();

        $this->assertFalse($result->isValid());
        $this->assertEquals($result->getCode(), Authentication\Result::FAILURE_IDENTITY_NOT_FOUND);

        $messages = $result->getMessages();
        $this->assertEquals(1, count($messages));
        $this->assertStringContainsString('combination not found', $messages[0]);

        $identity = $result->getIdentity();
        $this->assertEquals($identity['realm'], $realm);
        $this->assertEquals($identity['username'], $username);
    }

    /**
     * Ensures expected behavior upon incorrect password
     */
    public function testIncorrectPassword(): void
    {
        $filename = "$this->filesPath/htdigest.1";
        $realm    = 'Some Realm';
        $username = 'someUser';
        $password = 'incorrectPassword';

        $adapter = new Adapter\Digest($filename, $realm, $username, $password);

        $result = $adapter->authenticate();

        $this->assertFalse($result->isValid());
        $this->assertEquals($result->getCode(), Authentication\Result::FAILURE_CREDENTIAL_INVALID);

        $messages = $result->getMessages();
        $this->assertEquals(1, count($messages));
        $this->assertStringContainsString('Password incorrect', $messages[0]);

        $identity = $result->getIdentity();
        $this->assertEquals($identity['realm'], $realm);
        $this->assertEquals($identity['username'], $username);
    }

    /**
     * Ensures that successful authentication works as expected
     */
    public function testAuthenticationSuccess(): void
    {
        $filename = "$this->filesPath/htdigest.1";
        $realm    = 'Some Realm';
        $username = 'someUser';
        $password = 'somePassword';

        $adapter = new Adapter\Digest($filename, $realm, $username, $password);

        $result = $adapter->authenticate();

        $this->assertTrue($result->isValid());
        $this->assertEquals($result->getCode(), Authentication\Result::SUCCESS);

        $this->assertEquals([], $result->getMessages());

        $identity = $result->getIdentity();
        $this->assertEquals($identity['realm'], $realm);
        $this->assertEquals($identity['username'], $username);
    }

    /**
     * Ensures that getFilename() returns expected default value
     */
    public function testGetFilename(): void
    {
        $adapter = new Adapter\Digest();
        $this->assertEquals(null, $adapter->getFilename());
    }

    /**
     * Ensures that getRealm() returns expected default value
     */
    public function testGetRealm(): void
    {
        $adapter = new Adapter\Digest();
        $this->assertEquals(null, $adapter->getRealm());
    }

    /**
     * Ensures that getUsername() returns expected default value
     */
    public function testGetUsername(): void
    {
        $adapter = new Adapter\Digest();
        $this->assertEquals(null, $adapter->getUsername());
    }

    /**
     * Ensures that getPassword() returns expected default value
     */
    public function testGetPassword(): void
    {
        $adapter = new Adapter\Digest();
        $this->assertEquals(null, $adapter->getPassword());
    }
}
