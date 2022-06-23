<?php

declare(strict_types=1);

namespace LaminasTest\Authentication\Adapter;

use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
    /** @var TestAsset\Wrapper */
    private $wrapper;

    public function setUp(): void
    {
        $config = [
            'accept_schemes' => 'basic',
            'realm'          => 'testing',
        ];

        $this->wrapper = new TestAsset\Wrapper($config);
    }

    public function tearDown(): void
    {
        unset($this->wrapper);
    }

    public function testProtectedMethodChallengeClientTriggersErrorDeprecated(): void
    {
        $this->expectDeprecation();
        $this->wrapper->_challengeClient();
    }
}
