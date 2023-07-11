<?php

declare(strict_types=1);

namespace LaminasTest\Authentication;

use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Exception\RuntimeException;
use Laminas\Authentication\Result;
use Laminas\Authentication\Storage\Session;
use Laminas\Authentication\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;

class AuthenticationServiceTest extends TestCase
{
    private AuthenticationService $auth;

    public function setUp(): void
    {
        $this->auth = new AuthenticationService();
    }

    /**
     * Ensures that getStorage() returns Laminas_Auth_Storage_Session
     */
    public function testGetStorage(): void
    {
        $storage = $this->auth->getStorage();
        $this->assertInstanceOf(Session::class, $storage);
    }

    public function testAdapter(): void
    {
        $this->assertNull($this->auth->getAdapter());
        $successAdapter = new TestAsset\ValidatableAdapter();
        $ret            = $this->auth->setAdapter($successAdapter);
        $this->assertSame($ret, $this->auth);
        $this->assertSame($successAdapter, $this->auth->getAdapter());
    }

    /**
     * Ensures expected behavior for successful authentication
     */
    public function testAuthenticate(): void
    {
        $result = $this->authenticate();
        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($this->auth->hasIdentity());
        $this->assertEquals('someIdentity', $this->auth->getIdentity());
    }

    public function testAuthenticateSetAdapter(): void
    {
        $result = $this->authenticate(new TestAsset\ValidatableAdapter());
        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($this->auth->hasIdentity());
        $this->assertEquals('someIdentity', $this->auth->getIdentity());
    }

    /**
     * Ensures expected behavior for clearIdentity()
     */
    public function testClearIdentity(): void
    {
        $this->authenticate();
        $this->auth->clearIdentity();
        $this->assertFalse($this->auth->hasIdentity());
        $this->assertEquals(null, $this->auth->getIdentity());
    }

    protected function authenticate(?TestAsset\ValidatableAdapter $adapter = null): Result
    {
        if ($adapter === null) {
            $adapter = new TestAsset\ValidatableAdapter();
        }
        return $this->auth->authenticate($adapter);
    }

    public function testThatIdentityIsNullWhenStorageIsEmpty(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::once())
            ->method('isEmpty')
            ->willReturn(true);

        $service = new AuthenticationService($storage, new TestAsset\ValidatableAdapter());

        self::assertNull($service->getIdentity());
    }

    public function testAnExceptionShouldBeThrownWhenAuthenticatingWithoutAnAdapterPresent(): void
    {
        $service = new AuthenticationService();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('An adapter must be set or passed prior to calling authenticate()');
        $service->authenticate();
    }
}
