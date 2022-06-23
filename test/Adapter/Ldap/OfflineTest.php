<?php

declare(strict_types=1);

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 */

namespace LaminasTest\Authentication\Adapter\Ldap;

use Laminas\Authentication\Adapter;
use Laminas\Ldap;
use PHPUnit\Framework\TestCase;

use function extension_loaded;

/**
 * @group      Laminas_Auth
 */
class OfflineTest extends TestCase
{
    /**
     * Authentication adapter instance
     *
     * @var Adapter\Ldap
     */
    protected $adapter;

    /**
     * Setup operations run prior to each test method:
     *
     * * Creates an instance of Laminas\Authentication\Adapter\Ldap
     */
    public function setUp(): void
    {
        $this->adapter = new Adapter\Ldap();
    }

    public function testGetSetLdap(): void
    {
        if (! extension_loaded('ldap')) {
            $this->markTestSkipped('LDAP is not enabled');
        }
        $this->adapter->setLdap(new Ldap\Ldap());
        $this->assertInstanceOf(\Laminas\Ldap\Ldap::class, $this->adapter->getLdap());
    }

    public function testUsernameIsNullIfNotSet(): void
    {
        $this->assertNull($this->adapter->getUsername());
    }

    public function testPasswordIsNullIfNotSet(): void
    {
        $this->assertNull($this->adapter->getPassword());
    }

    public function testSetAndGetUsername(): void
    {
        $usernameExpected = 'someUsername';
        $usernameActual   = $this->adapter->setUsername($usernameExpected)
                                         ->getUsername();
        $this->assertSame($usernameExpected, $usernameActual);
    }

    public function testSetAndGetPassword(): void
    {
        $passwordExpected = 'somePassword';
        $passwordActual   = $this->adapter->setPassword($passwordExpected)
                                         ->getPassword();
        $this->assertSame($passwordExpected, $passwordActual);
    }

    public function testSetIdentityProxiesToSetUsername(): void
    {
        $usernameExpected = 'someUsername';
        $usernameActual   = $this->adapter->setIdentity($usernameExpected)
                                         ->getUsername();
        $this->assertSame($usernameExpected, $usernameActual);
    }

    public function testSetCredentialProxiesToSetPassword(): void
    {
        $passwordExpected = 'somePassword';
        $passwordActual   = $this->adapter->setCredential($passwordExpected)
                                         ->getPassword();
        $this->assertSame($passwordExpected, $passwordActual);
    }
}
