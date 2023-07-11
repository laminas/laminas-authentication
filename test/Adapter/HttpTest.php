<?php

declare(strict_types=1);

namespace LaminasTest\Authentication\Adapter;

use Laminas\Authentication\Adapter\Http;

use Laminas\Http\Response;

use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
    /** @var Http */
    private $wrapper;

    public function setUp(): void
    {
        $config = [
            'accept_schemes' => 'basic',
            'realm'          => 'testing',
        ];

        $this->wrapper = new Http($config);
    }

    public function tearDown(): void
    {
        unset($this->wrapper);
    }

    public function testChallengeClient(): void
    {
        $this->wrapper->setResponse(new Response());
        $this->wrapper->challengeClient();
    }

    public function testSetGetBasicResolver(): void
    {
        $adapter = new Http(['accept_schemes' => 'basic', 'realm' => 'foo']);
        self::assertNull($adapter->getBasicResolver());

        $resolver = $this->createMock(Http\ResolverInterface::class);
        $adapter->setBasicResolver($resolver);

        self::assertSame($resolver, $adapter->getBasicResolver());
    }

    public function testSetGetDigestResolver(): void
    {
        $adapter = new Http(['accept_schemes' => 'basic', 'realm' => 'foo']);
        self::assertNull($adapter->getDigestResolver());

        $resolver = $this->createMock(Http\ResolverInterface::class);
        $adapter->setDigestResolver($resolver);

        self::assertSame($resolver, $adapter->getDigestResolver());
    }
}
