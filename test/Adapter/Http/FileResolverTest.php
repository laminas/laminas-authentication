<?php

declare(strict_types=1);

namespace LaminasTest\Authentication\Adapter\Http;

use Laminas\Authentication\Adapter\Http;
use PHPUnit\Framework\TestCase;

use function rename;

/**
 * @group      Laminas_Auth
 */
class FileResolverTest extends TestCase
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
     * Resolver instance
     *
     * @var Http\FileResolver
     */
    protected $_resolver;
    // @codingStandardsIgnoreEnd

    /**
     * Sets the paths to files used in this test, and creates a shared resolver instance
     * having a valid path.
     */
    public function setUp(): void
    {
        $this->_filesPath = __DIR__ . '/TestAsset';
        $this->_validPath = "$this->_filesPath/htdigest.3";
        $this->_badPath   = 'doesnotexist';
        $this->_resolver  = new Http\FileResolver($this->_validPath);
    }

    /**
     * Ensures that setFile() works as expected for valid input
     *
     * @return void
     */
    public function testSetFileValid()
    {
        $this->_resolver->setFile($this->_validPath);
        $this->assertEquals($this->_validPath, $this->_resolver->getFile());
    }

    /**
     * Ensures that setFile() works as expected for invalid input
     *
     * @return void
     */
    public function testSetFileInvalid()
    {
        $this->expectException(Http\Exception\ExceptionInterface::class);
        $this->expectExceptionMessage('Path not readable');
        $this->_resolver->setFile($this->_badPath);
    }

    /**
     * Ensures that __construct() works as expected for valid input
     *
     * @return void
     */
    public function testConstructValid()
    {
        $v = new Http\FileResolver($this->_validPath);
        $this->assertEquals($this->_validPath, $v->getFile());
    }

    /**
     * Ensures that __construct() works as expected for invalid input
     *
     * @return void
     */
    public function testConstructInvalid()
    {
        $this->expectException(Http\Exception\ExceptionInterface::class);
        $this->expectExceptionMessage('Path not readable');
        new Http\FileResolver($this->_badPath);
    }

    /**
     * Ensures that resolve() works as expected for empty username
     *
     * @return void
     */
    public function testResolveUsernameEmpty()
    {
        $this->expectException(Http\Exception\ExceptionInterface::class);
        $this->expectExceptionMessage('Username is required');
        $this->_resolver->resolve('', '');
    }

    /**
     * Ensures that resolve() works as expected for empty realm
     *
     * @return void
     */
    public function testResolveRealmEmpty()
    {
        $this->expectException(Http\Exception\ExceptionInterface::class);
        $this->expectExceptionMessage('Realm is required');
        $this->_resolver->resolve('username', '');
    }

    /**
     * Ensures that resolve() works as expected for invalid username
     *
     * @return void
     */
    public function testResolveUsernameInvalid()
    {
        try {
            $this->_resolver->resolve('bad:name', 'realm');
            $this->fail('Accepted malformed username with colon');
        } catch (Http\Exception\ExceptionInterface $e) {
            $this->assertStringContainsString('Username must consist', $e->getMessage());
        }
        try {
            $this->_resolver->resolve("badname\n", 'realm');
            $this->fail('Accepted malformed username with newline');
        } catch (Http\Exception\ExceptionInterface $e) {
            $this->assertStringContainsString('Username must consist', $e->getMessage());
        }
    }

    /**
     * Ensures that resolve() works as expected for invalid realm
     *
     * @return void
     */
    public function testResolveRealmInvalid()
    {
        try {
            $this->_resolver->resolve('username', 'bad:realm');
            $this->fail('Accepted malformed realm with colon');
        } catch (Http\Exception\ExceptionInterface $e) {
            $this->assertStringContainsString('Realm must consist', $e->getMessage());
        }
        try {
            $this->_resolver->resolve('username', "badrealm\n");
            $this->fail('Accepted malformed realm with newline');
        } catch (Http\Exception\ExceptionInterface $e) {
            $this->assertStringContainsString('Realm must consist', $e->getMessage());
        }
    }

    /**
     * Ensures that resolve() works as expected when a previously readable file becomes unreadable
     *
     * @return void
     */
    public function testResolveFileDisappearsMystery()
    {
        if (rename("$this->_filesPath/htdigest.3", "$this->_filesPath/htdigest.3.renamed")) {
            try {
                $this->_resolver->resolve('username', 'realm');
                $this->fail('Expected thrown exception upon resolve() after moving valid file');
            } catch (Http\Exception\ExceptionInterface $e) {
                $this->assertStringContainsString('Unable to open password file', $e->getMessage());
            }
            rename("$this->_filesPath/htdigest.3.renamed", "$this->_filesPath/htdigest.3");
        }
    }

    /**
     * Ensures that resolve() works as expected when provided valid credentials
     *
     * @return void
     */
    public function testResolveValid()
    {
        $this->assertEquals(
            $this->_resolver->resolve('Bryce', 'Test Realm'),
            'd5b7c330d5685beb782a9e22f0f20579',
            'Rejected valid credentials'
        );
    }

    /**
     * Ensures that resolve() works as expected when provided nonexistent realm
     *
     * @return void
     */
    public function testResolveRealmNonexistent()
    {
        $this->assertFalse(
            $this->_resolver->resolve('Bryce', 'nonexistent'),
            'Accepted a valid user in the wrong realm'
        );
    }

    /**
     * Ensures that resolve() works as expected when provided nonexistent user
     *
     * @return void
     */
    public function testResolveUserNonexistent()
    {
        $this->assertFalse(
            $this->_resolver->resolve('nonexistent', 'Test Realm'),
            'Accepted a nonexistent user from an existing realm'
        );
    }
}
