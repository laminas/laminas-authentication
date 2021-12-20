<?php

declare(strict_types=1);

namespace LaminasTest\Authentication\TestAsset;

use Laminas\Authentication\Adapter;

use function call_user_func_array;

class Wrapper extends Adapter\Http
{
    /**
     * @param callable $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this, $method], $args);
    }
}
