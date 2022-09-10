<?php

declare(strict_types=1);

namespace Laminas\Authentication\Adapter\Http\Exception;

use Laminas\Authentication\Adapter\Exception;

class RuntimeException extends Exception\RuntimeException implements
    ExceptionInterface
{
}
