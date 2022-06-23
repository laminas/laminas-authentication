<?php

declare(strict_types=1);

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 */

namespace Laminas\Authentication\Adapter\Exception;

use Laminas\Authentication\Exception;

class RuntimeException extends Exception\RuntimeException implements
    ExceptionInterface
{
}
