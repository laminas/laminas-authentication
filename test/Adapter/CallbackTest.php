<?php

declare(strict_types=1);

namespace LaminasTest\Authentication\Adapter;

use Exception;
use Laminas\Authentication\Adapter\Callback;
use Laminas\Authentication\Exception as AuthenticationException;
use Laminas\Authentication\Result;
use PHPUnit\Framework\TestCase;

use function array_map;

class CallbackTest extends TestCase
{
    private Callback $adapter;

    public function setUp(): void
    {
        $this->adapter = new Callback();
    }

    /**
     * Ensures expected behavior for an invalid callback
     */
    public function testSetCallbackThrowsException(): void
    {
        $this->expectException(AuthenticationException\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid callback provided');
        $this->adapter->setCallback('This is not a valid callback');
    }

    /**
     * Ensures setter/getter behaviour for callback
     */
    public function testCallbackSetGetMethods(): void
    {
        $callback = function (): void {
        };
        $this->adapter->setCallback($callback);
        $this->assertEquals($callback, $this->adapter->getCallback());
    }

    /**
     * Ensures constructor sets callback if provided
     */
    public function testClassConstructorSetCallback(): void
    {
        $callback = function (): void {
        };
        $adapter  = new Callback($callback);
        $this->assertEquals($callback, $adapter->getCallback());
    }

    /**
     * Ensures authenticate throws Exception if no callback is defined
     */
    public function testAuthenticateThrowsException(): void
    {
        $this->expectException(AuthenticationException\RuntimeException::class);
        $this->expectExceptionMessage('No callback provided');
        $this->adapter->authenticate();
    }

    /**
     * Ensures identity and credential are provided as arguments to callback
     */
    public function testAuthenticateProvidesCallbackWithIdentityAndCredentials(): void
    {
        $adapter = $this->adapter;
        $adapter->setIdentity('testIdentity');
        $adapter->setCredential('testCredential');
        $callback = function (mixed $identity, mixed $credential) use ($adapter): void {
            self::assertEquals($identity, $adapter->getIdentity());
            self::assertEquals($credential, $adapter->getCredential());
        };
        $this->adapter->setCallback($callback);
        $this->adapter->authenticate();
    }

    /**
     * Ensures authentication result is invalid when callback throws exception
     */
    public function testAuthenticateResultIfCallbackThrows(): void
    {
        $exception = new Exception('Callback Exception');
        $callback  = function () use ($exception): void {
            throw $exception;
        };
        $this->adapter->setCallback($callback);
        $result = $this->adapter->authenticate();
        self::assertFalse($result->isValid());
        self::assertEquals(Result::FAILURE_UNCATEGORIZED, $result->getCode());
        self::assertEquals([$exception->getMessage()], $result->getMessages());
    }

    /**
     * Ensures authentication result is invalid when callback returns falsy value
     */
    public function testAuthenticateResultIfCallbackReturnsFalsy(): void
    {
        $falsyValues = [false, null, '', '0', [], 0, 0.0];
        array_map(function ($falsy) {
            $callback = static fn (): mixed => $falsy;
            $this->adapter->setCallback($callback);
            $result   = $this->adapter->authenticate();
            self::assertFalse($result->isValid());
            self::assertEquals(Result::FAILURE, $result->getCode());
            self::assertEquals(['Authentication failure'], $result->getMessages());
        }, $falsyValues);
    }

    /**
     * Ensures authentication result is valid when callback returns truthy value
     */
    public function testAuthenticateResultIfCallbackReturnsIdentity(): void
    {
        $adapter  = $this->adapter;
        $callback = function (): string {
            return 'identity';
        };
        $adapter->setCallback($callback);
        $result = $adapter->authenticate();
        self::assertTrue($result->isValid());
        self::assertEquals(Result::SUCCESS, $result->getCode());
        self::assertEquals('identity', $result->getIdentity());
        self::assertEquals(['Authentication success'], $result->getMessages());
    }
}
