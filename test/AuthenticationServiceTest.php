<?php

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Authentication;

use Laminas\Authentication\AuthenticationService;
use PHPUnit\Framework\TestCase;

class AuthenticationServiceTest extends TestCase
{
    public function setUp(): void
    {
        $this->auth = new AuthenticationService();
    }

    /**
     * Ensures that getStorage() returns Laminas_Auth_Storage_Session
     *
     * @return void
     */
    public function testGetStorage()
    {
        $storage = $this->auth->getStorage();
        $this->assertInstanceOf('Laminas\Authentication\Storage\Session', $storage);
    }

    public function testAdapter(): void
    {
        $this->assertNull($this->auth->getAdapter());
        $successAdapter = new TestAsset\ValidatableAdapter();
        $ret = $this->auth->setAdapter($successAdapter);
        $this->assertSame($ret, $this->auth);
        $this->assertSame($successAdapter, $this->auth->getAdapter());
    }

    /**
     * Ensures expected behavior for successful authentication
     *
     * @return void
     */
    public function testAuthenticate()
    {
        $result = $this->authenticate();
        $this->assertInstanceOf('Laminas\Authentication\Result', $result);
        $this->assertTrue($this->auth->hasIdentity());
        $this->assertEquals('someIdentity', $this->auth->getIdentity());
    }

    public function testAuthenticateSetAdapter(): void
    {
        $result = $this->authenticate(new TestAsset\ValidatableAdapter());
        $this->assertInstanceOf('Laminas\Authentication\Result', $result);
        $this->assertTrue($this->auth->hasIdentity());
        $this->assertEquals('someIdentity', $this->auth->getIdentity());
    }

    /**
     * Ensures expected behavior for clearIdentity()
     *
     * @return void
     */
    public function testClearIdentity()
    {
        $this->authenticate();
        $this->auth->clearIdentity();
        $this->assertFalse($this->auth->hasIdentity());
        $this->assertEquals(null, $this->auth->getIdentity());
    }

    /**
     * @param TestAsset\ValidatableAdapter|null $adapter
     */
    protected function authenticate(?TestAsset\ValidatableAdapter $adapter = null)
    {
        if ($adapter === null) {
            $adapter = new TestAsset\ValidatableAdapter();
        }
        return $this->auth->authenticate($adapter);
    }
}
