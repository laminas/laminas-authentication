<?php

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Authentication\Adapter;

use Laminas\Authentication\Adapter;

class HttpTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Wrapper
     */
    protected $_wrapper;

    public function setUp()
    {
        $config = [
            'accept_schemes' => 'basic',
            'realm'          => 'testing',
        ];

        $this->_wrapper = new Wrapper($config);
    }

    public function tearDown()
    {
        unset($this->_wrapper);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Deprecated
     */
    public function testProtectedMethodChallengeClientTriggersErrorDeprecated()
    {
        $this->_wrapper->_challengeClient();
    }
}

class Wrapper extends Adapter\Http
{
    public function __call($method, $args)
    {
        return call_user_func_array([$this, $method], $args);
    }
}
