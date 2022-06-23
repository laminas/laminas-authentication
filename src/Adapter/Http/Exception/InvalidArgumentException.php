<?php

declare(strict_types=1);

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 */

namespace Laminas\Authentication\Adapter\Http\Exception;

use Laminas\Authentication\Adapter\Exception;

class InvalidArgumentException extends Exception\InvalidArgumentException implements
    ExceptionInterface
{
}
