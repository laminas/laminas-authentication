<?php

declare(strict_types=1);

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 */

namespace Laminas\Authentication\Adapter\DbTable\Exception;

use Laminas\Authentication\Adapter\Exception;

class RuntimeException extends Exception\RuntimeException implements
    ExceptionInterface
{
}
