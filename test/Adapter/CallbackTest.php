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
    /**
     * Callback authentication adapter
     *
     * @var Callback
     */
    protected $adapter;

    /**
     * Set up test configuration
     */
    public function setUp(): void
    {
        $this->setupAuthAdapter();
    }

    public function tearDown(): void
    {
        $this->adapter = null;
    }

    protected function setupAuthAdapter(): void
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
        $that     = $this;
        $callback = function ($identity, $credential) use ($that, $adapter): void {
            $that->assertEquals($identity, $adapter->getIdentity());
            $that->assertEquals($credential, $adapter->getCredential());
        };
        $this->adapter->setCallback($callback);
        $this->adapter->authenticate();
    }

    /**
     * Ensures authentication result is invalid when callback throws exception
     */
    public function testAuthenticateResultIfCallbackThrows(): void
    {
        $adapter   = $this->adapter;
        $exception = new Exception('Callback Exception');
        $callback  = function () use ($exception): void {
            throw $exception;
        };
        $adapter->setCallback($callback);
        $result = $adapter->authenticate();
        $this->assertFalse($result->isValid());
        $this->assertEquals(Result::FAILURE_UNCATEGORIZED, $result->getCode());
        $this->assertEquals([$exception->getMessage()], $result->getMessages());
    }

    /**
     * Ensures authentication result is invalid when callback returns falsy value
     */
    public function testAuthenticateResultIfCallbackReturnsFalsy(): void
    {
        $that        = $this;
        $adapter     = $this->adapter;
        $falsyValues = [false, null, '', '0', [], 0, 0.0];
        array_map(function ($falsy) use ($that, $adapter) {
            $callback = /**
            $callback =  * @return array|false|float|int|null|string
            $callback =  * @psalm-return array<empty, empty>|false|float|int|null|string
             */
            function () use ($falsy) {
                return $falsy;
            };
            $adapter->setCallback($callback);
            $result = $adapter->authenticate();
            $that->assertFalse($result->isValid());
            $that->assertEquals(Result::FAILURE, $result->getCode());
            $that->assertEquals(['Authentication failure'], $result->getMessages());
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
        $this->assertTrue($result->isValid());
        $this->assertEquals(Result::SUCCESS, $result->getCode());
        $this->assertEquals('identity', $result->getIdentity());
        $this->assertEquals(['Authentication success'], $result->getMessages());
    }
}
