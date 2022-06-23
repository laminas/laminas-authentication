<?php

declare(strict_types=1);

namespace Laminas\Authentication\Adapter\Exception;

use Laminas\Authentication\Exception;

class UnexpectedValueException extends Exception\UnexpectedValueException implements
    ExceptionInterface
{
}
