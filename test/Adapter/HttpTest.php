<?php

declare(strict_types=1);

namespace LaminasTest\Authentication\Adapter;

use Laminas\Authentication\Adapter\Http;
use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
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
