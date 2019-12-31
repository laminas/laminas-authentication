<?php

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Authentication\Adapter\Ldap;

use Laminas\Authentication\Adapter;
use Laminas\Ldap;

/**
 * @group      Laminas_Auth
 */
class OfflineTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Authentication adapter instance
     *
     * @var Adapter\Ldap
     */
    protected $adapter = null;

    /**
     * Setup operations run prior to each test method:
     *
     * * Creates an instance of Laminas\Authentication\Adapter\Ldap
     *
     * @return void
     */
    public function setUp()
    {
        $this->adapter = new Adapter\Ldap();
    }

    public function testGetSetLdap()
    {
        if (!extension_loaded('ldap')) {
            $this->markTestSkipped('LDAP is not enabled');
        }
        $this->adapter->setLdap(new Ldap\Ldap());
        $this->assertInstanceOf('Laminas\Ldap\Ldap', $this->adapter->getLdap());
    }

    public function testUsernameIsNullIfNotSet()
    {
        $this->assertNull($this->adapter->getUsername());
    }

    public function testPasswordIsNullIfNotSet()
    {
        $this->assertNull($this->adapter->getPassword());
    }

    public function testSetAndGetUsername()
    {
        $usernameExpected = 'someUsername';
        $usernameActual = $this->adapter->setUsername($usernameExpected)
                                         ->getUsername();
        $this->assertSame($usernameExpected, $usernameActual);
    }

    public function testSetAndGetPassword()
    {
        $passwordExpected = 'somePassword';
        $passwordActual = $this->adapter->setPassword($passwordExpected)
                                         ->getPassword();
        $this->assertSame($passwordExpected, $passwordActual);
    }

    public function testSetIdentityProxiesToSetUsername()
    {
        $usernameExpected = 'someUsername';
        $usernameActual = $this->adapter->setIdentity($usernameExpected)
                                         ->getUsername();
        $this->assertSame($usernameExpected, $usernameActual);
    }

    public function testSetCredentialProxiesToSetPassword()
    {
        $passwordExpected = 'somePassword';
        $passwordActual = $this->adapter->setCredential($passwordExpected)
                                         ->getPassword();
        $this->assertSame($passwordExpected, $passwordActual);
    }
}
