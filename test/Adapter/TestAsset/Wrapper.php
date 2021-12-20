<?php

namespace LaminasTest\Authentication\Adapter\TestAsset;

use Laminas\Authentication\Adapter;

class Wrapper extends Adapter\Http
{
    public function __call($method, $args)
    {
        return call_user_func_array([$this, $method], $args);
    }
}
