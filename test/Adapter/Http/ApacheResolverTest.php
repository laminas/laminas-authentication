<?php

declare(strict_types=1);

namespace LaminasTest\Authentication\Adapter\Http;

use Laminas\Authentication\Adapter\Http\ApacheResolver as Apache;
use Laminas\Authentication\Adapter\Http\Exception\ExceptionInterface;
use Laminas\Authentication\Result;
use PHPUnit\Framework\TestCase;

class ApacheResolverTest extends TestCase
{
    /**
     * Path to a valid file
     */
    private string $validPath;

    /**
     * Invalid path; does not exist
     */
    private string $badPath;
    private Apache $apache;
    private string $path;
    private string $digest;

    /**
     * Sets the paths to files used in this test, and creates a shared resolver instance
     * having a valid path.
     */
    public function setUp(): void
    {
        $this->path      = __DIR__ . '/TestAsset';
        $this->validPath = $this->path . '/htbasic.plaintext';
        $this->digest    = $this->path . '/htdigest';
        $this->apache    = new Apache($this->validPath);
        $this->badPath   = 'invalid path';
    }

    /**
     * Ensures that setFile() works as expected for valid input
     *
     * @return void
     */
    public function testSetFileValid()
    {
        $this->apache->setFile($this->validPath);
        $this->assertEquals($this->validPath, $this->apache->getFile());
    }

    /**
     * Ensures that setFile() works as expected for invalid input
     *
     * @return void
     */
    public function testSetFileInvalid()
    {
        $this->expectException(ExceptionInterface::class);
        $this->expectExceptionMessage('Path not readable');
        $this->apache->setFile($this->badPath);
    }

    /**
     * Ensures that __construct() works as expected for valid input
     *
     * @return void
     */
    public function testConstructValid()
    {
        $apache = new Apache($this->validPath);
        $this->assertEquals($this->validPath, $apache->getFile());
    }

    /**
     * Ensures that __construct() works as expected for invalid input
     *
     * @return void
     */
    public function testConstructInvalid()
    {
        $this->expectException(ExceptionInterface::class);
        $this->expectExceptionMessage('Path not readable');
        new Apache($this->badPath);
    }

    /**
     * @return string[][]
     * @psalm-return array<array-key, array{0: string}>
     */
    public function providePasswordFiles(): array
    {
        $path = __DIR__ . '/TestAsset';
        return [
            [$path . '/htbasic.plaintext'],
            [$path . '/htbasic.md5'],
            [$path . '/htbasic.sha1'],
            [$path . '/htbasic.crypt'],
            [$path . '/htbasic.bcrypt'],
        ];
    }

    /**
     * Ensure that resolve() works fine with the specified password format
     *
     * @dataProvider providePasswordFiles
     */
    public function testResolveValidBasic(string $file): void
    {
        $this->apache->setFile($file);
        $result = $this->apache->resolve('test', null, 'password');
        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isValid());
    }

    /**
     * Ensure that resolve() works fine with the specified password format
     * even if we pass a realm fake string for a basic authentication
     *
     * @dataProvider providePasswordFiles
     */
    public function testResolveValidBasicWithRealm(string $file): void
    {
        $this->apache->setFile($file);
        $result = $this->apache->resolve('test', 'realm', 'password');
        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isValid());
    }

    /**
     * Ensure that resolve() failed for not valid users
     *
     * @dataProvider providePasswordFiles
     */
    public function testResolveNoUsers(string $file): void
    {
        $this->apache->setFile($file);
        $result = $this->apache->resolve('foo', null, 'password');
        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isValid());
    }

    /**
     * Ensure that resolve() failed for not valid password
     *
     * @dataProvider providePasswordFiles
     */
    public function testResolveNoValidPassword(string $file): void
    {
        $this->apache->setFile($file);
        $result = $this->apache->resolve('test', null, 'bar');
        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isValid());
    }

    /**
     * Ensure that resolve() works fine with the digest password format
     */
    public function testResolveValidDigest(): void
    {
        $this->apache->setFile($this->digest);
        $result = $this->apache->resolve('test', 'auth', 'password');
        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isValid());
    }
}
