<?php

declare(strict_types=1);

namespace LaminasTest\Authentication\Adapter\Http;

use Laminas\Authentication;
use Laminas\Authentication\Adapter;
use Laminas\Authentication\Adapter\Http;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use Laminas\Http\Response;
use PHPUnit\Framework\TestCase;

use function sprintf;

/**
 * @group      Laminas_Auth
 */
class ObjectTest extends TestCase
{
    // @codingStandardsIgnoreStart
    /**
     * Path to test files
     *
     * @var string
     */
    protected $_filesPath;

    /**
     * HTTP Basic configuration
     *
     * @var array
     */
    protected $_basicConfig;

    /**
     * HTTP Digest configuration
     *
     * @var array
     */
    protected $_digestConfig;

    /**
     * HTTP Basic Digest configuration
     *
     * @var array
     */
    protected $_bothConfig;

    /**
     * File resolver setup against with HTTP Basic auth file
     *
     * @var Http\FileResolver
     */
    protected $_basicResolver;

    /**
     * File resolver setup against with HTTP Digest auth file
     *
     * @var Http\FileResolver
     */
    protected $_digestResolver;
    // @codingStandardsIgnoreEnd

    /**
     * Sets up test configuration
     */
    public function setUp(): void
    {
        $this->_filesPath      = __DIR__ . '/TestAsset';
        $this->_basicResolver  = new Http\FileResolver("$this->_filesPath/htbasic.1");
        $this->_digestResolver = new Http\FileResolver("$this->_filesPath/htdigest.3");
        $this->_basicConfig    = [
            'accept_schemes' => 'basic',
            'realm'          => 'Test Realm',
        ];
        $this->_digestConfig   = [
            'accept_schemes' => 'digest',
            'realm'          => 'Test Realm',
            'digest_domains' => '/ http://localhost/',
            'nonce_timeout'  => 300,
        ];
        $this->_bothConfig     = [
            'accept_schemes' => 'basic digest',
            'realm'          => 'Test Realm',
            'digest_domains' => '/ http://localhost/',
            'nonce_timeout'  => 300,
        ];
    }

    public function testValidConfigs(): void
    {
        $configs = [
            $this->_basicConfig,
            $this->_digestConfig,
            $this->_bothConfig,
        ];
        foreach ($configs as $config) {
            new Adapter\Http($config);
        }
        $this->addToAssertionCount(1);
    }

    /**
     * @return array
     */
    public function invalidConfigs()
    {
        return [
            'bad1' => [
                [
                    'auth_type' => 'bogus',
                    'realm'     => 'Test Realm',
                ],
            ],
            'bad2' => [
                [
                    'auth_type'      => 'digest',
                    'realm'          => 'Bad: "Chars"' . "\n",
                    'digest_domains' => '/ /admin',
                    'nonce_timeout'  => 300,
                ],
            ],
            'bad3' => [
                [
                    'auth_type'      => 'digest',
                    'realm'          => 'Test Realm',
                    'digest_domains' => 'no"quotes' . "\tor tabs",
                    'nonce_timeout'  => 300,
                ],
            ],
            'bad4' => [
                [
                    'auth_type'      => 'digest',
                    'realm'          => 'Test Realm',
                    'digest_domains' => '/ /admin',
                    'nonce_timeout'  => 'junk',
                ],
            ],
        ];
    }

    /**
     * @dataProvider invalidConfigs
     */
    public function testInvalidConfigs(array $cfg): void
    {
        $this->expectException(Adapter\Exception\ExceptionInterface::class);
        new Adapter\Http($cfg);
    }

    public function testAuthenticateArgs(): void
    {
        $a = new Adapter\Http($this->_basicConfig);

        try {
            $a->authenticate();
            $this->fail('Attempted authentication without request/response objects');
        } catch (Adapter\Exception\ExceptionInterface $e) {
            // Good, it threw an exception
        }

        $request  = new Request();
        $response = new Response();

        // If this throws an exception, it fails
        $response = $a->setRequest($request)
          ->setResponse($response)
          ->authenticate();

        $this->assertInstanceOf(Authentication\Result::class, $response);
    }

    /**
     * @return string[][]
     * @psalm-return array{basic: array{0: string, 1: string}, digest: array{0: string, 1: string}}
     */
    public function noResolvers(): array
    {
        return [
            'basic'  => [
                'Basic',
                '_basicConfig',
            ],
            'digest' => [
                'Digest',
                '_digestConfig',
            ],
        ];
    }

    /**
     * @dataProvider noResolvers
     */
    public function testNoResolvers(string $authHeader, string $cfgProperty): void
    {
        // Stub request for Basic auth
        $headers = new Headers();
        $headers->addHeaderLine(
            'Authorization',
            sprintf(
                '%s <followed by a space character',
                $authHeader
            )
        );

        $request = new Request();
        $request->setHeaders($headers);
        $response = new Response();

        $a = new Adapter\Http($this->$cfgProperty);
        $a->setRequest($request)
          ->setResponse($response);

        $this->expectException(Adapter\Exception\ExceptionInterface::class);
        $a->authenticate();
    }

    public function testWrongResolverUsed(): void
    {
        $response = new Response();
        $headers  = new Headers();
        $request  = new Request();

        $headers->addHeaderLine('Authorization', 'Basic <followed by a space character');
        $request->setHeaders($headers);

        // Test a Digest auth process while the request is containing a Basic auth header
        $adapter = new Adapter\Http($this->_digestConfig);
        $adapter->setDigestResolver($this->_digestResolver)
                ->setRequest($request)
                ->setResponse($response);
        $result = $adapter->authenticate();

        $this->assertEquals($result->getCode(), Authentication\Result::FAILURE_CREDENTIAL_INVALID);
    }

    public function testUnsupportedScheme(): void
    {
        $response = new Response();
        $headers  = new Headers();
        $request  = new Request();

        $headers->addHeaderLine('Authorization', 'NotSupportedScheme <followed by a space character');
        $request->setHeaders($headers);

        $a = new Adapter\Http($this->_digestConfig);
        $a->setDigestResolver($this->_digestResolver)
          ->setRequest($request)
          ->setResponse($response);
        $result = $a->authenticate();
        $this->assertEquals($result->getCode(), Authentication\Result::FAILURE_UNCATEGORIZED);
    }
}
