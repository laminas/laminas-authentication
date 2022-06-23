<?php

declare(strict_types=1);

namespace Laminas\Authentication\Adapter\Exception;

use Laminas\Authentication\Exception;

class InvalidArgumentException extends Exception\InvalidArgumentException implements ExceptionInterface
{
}
