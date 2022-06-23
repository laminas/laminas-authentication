<?php

declare(strict_types=1);

namespace Laminas\Authentication\Adapter\DbTable\Exception;

use Laminas\Authentication\Adapter\Exception;

class InvalidArgumentException extends Exception\InvalidArgumentException implements ExceptionInterface
{
}
