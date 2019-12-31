<?php

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Authentication;

use Laminas\Authentication as Auth;
use Laminas\Authentication\AuthenticationService;

/**
 * @group      Laminas_Auth
 */
class AuthenticationServiceTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
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
        $this->assertTrue($storage instanceof Auth\Storage\Session);
    }

    public function testAdapter()
    {
        $this->assertNull($this->auth->getAdapter());
        $successAdapter = new TestAsset\SuccessAdapter();
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
        $this->assertTrue($result instanceof Auth\Result);
        $this->assertTrue($this->auth->hasIdentity());
        $this->assertEquals('someIdentity', $this->auth->getIdentity());
    }

    public function testAuthenticateSetAdapter()
    {
        $result = $this->authenticate(new TestAsset\SuccessAdapter());
        $this->assertTrue($result instanceof Auth\Result);
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

    protected function authenticate($adapter = null)
    {
        if ($adapter === null) {
            $adapter = new TestAsset\SuccessAdapter();
        }
        return $this->auth->authenticate($adapter);
    }
}
