<?php

namespace Laminas\Authentication\Adapter\DbTable\Exception;

use Laminas\Authentication\Adapter\Exception;

class RuntimeException extends Exception\RuntimeException implements
    ExceptionInterface
{
}
