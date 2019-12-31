<?php

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Authentication\Adapter;

use PHPUnit\Framework\Error\Deprecated;
use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
    /**
     * @var TestAsset\Wrapper
     */
    private $wrapper;

    public function setUp()
    {
        $config = [
            'accept_schemes' => 'basic',
            'realm'          => 'testing',
        ];

        $this->wrapper = new TestAsset\Wrapper($config);
    }

    public function tearDown()
    {
        unset($this->wrapper);
    }

    public function testProtectedMethodChallengeClientTriggersErrorDeprecated()
    {
        $this->expectException(Deprecated::class);
        $this->wrapper->_challengeClient();
    }
}
