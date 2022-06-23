<?php

declare(strict_types=1);

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 */

namespace LaminasTest\Authentication\Adapter\Http;

use Laminas\Authentication\Adapter\Http\ApacheResolver as Apache;
use Laminas\Authentication\Adapter\Http\Exception\ExceptionInterface;
use Laminas\Authentication\Result;
use PHPUnit\Framework\TestCase;

/**
 * @group      Laminas_Auth
 */
class ApacheResolverTest extends TestCase
{
    // @codingStandardsIgnoreStart
    /**
     * Path to test files
     *
     * @var string
     */
    protected $_filesPath;

    /**
     * Path to a valid file
     *
     * @var string
     */
    protected $_validPath;

    /**
     * Invalid path; does not exist
     *
     * @var string
     */
    protected $_badPath;

    /**
     * @var Apache
     */
    protected $_apache;
    // @codingStandardsIgnoreEnd

    /**
     * Sets the paths to files used in this test, and creates a shared resolver instance
     * having a valid path.
     */
    public function setUp(): void
    {
        $this->_path      = __DIR__ . '/TestAsset';
        $this->_validPath = $this->_path . '/htbasic.plaintext';
        $this->_digest    = $this->_path . '/htdigest';
        $this->_apache    = new Apache($this->_validPath);
        $this->_badPath   = 'invalid path';
    }

    /**
     * Ensures that setFile() works as expected for valid input
     *
     * @return void
     */
    public function testSetFileValid()
    {
        $this->_apache->setFile($this->_validPath);
        $this->assertEquals($this->_validPath, $this->_apache->getFile());
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
        $this->_apache->setFile($this->_badPath);
    }

    /**
     * Ensures that __construct() works as expected for valid input
     *
     * @return void
     */
    public function testConstructValid()
    {
        $apache = new Apache($this->_validPath);
        $this->assertEquals($this->_validPath, $apache->getFile());
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
        new Apache($this->_badPath);
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
    public function testResolveValidBasic($file): void
    {
        $this->_apache->setFile($file);
        $result = $this->_apache->resolve('test', null, 'password');
        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isValid());
    }

    /**
     * Ensure that resolve() works fine with the specified password format
     * even if we pass a realm fake string for a basic authentication
     *
     * @dataProvider providePasswordFiles
     */
    public function testResolveValidBasicWithRealm($file): void
    {
        $this->_apache->setFile($file);
        $result = $this->_apache->resolve('test', 'realm', 'password');
        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isValid());
    }

    /**
     * Ensure that resolve() failed for not valid users
     *
     * @dataProvider providePasswordFiles
     */
    public function testResolveNoUsers($file): void
    {
        $this->_apache->setFile($file);
        $result = $this->_apache->resolve('foo', null, 'password');
        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isValid());
    }

        /**
         * Ensure that resolve() failed for not valid password
         *
         * @dataProvider providePasswordFiles
         */
    public function testResolveNoValidPassword($file): void
    {
        $this->_apache->setFile($file);
        $result = $this->_apache->resolve('test', null, 'bar');
        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isValid());
    }

    /**
     * Ensure that resolve() works fine with the digest password format
     */
    public function testResolveValidDigest(): void
    {
        $this->_apache->setFile($this->_digest);
        $result = $this->_apache->resolve('test', 'auth', 'password');
        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isValid());
    }
}
