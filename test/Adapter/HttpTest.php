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
        $this->expectNotToPerformAssertions();

        $this->wrapper->setResponse(new Response());
        $this->wrapper->challengeClient();
    }
}
