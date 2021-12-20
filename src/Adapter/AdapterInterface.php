<?php

declare(strict_types=1);

namespace Laminas\Authentication\Adapter;

use Laminas\Authentication\Adapter\Exception\ExceptionInterface;
use Laminas\Authentication\Result;

interface AdapterInterface
{
    /**
     * Performs an authentication attempt
     *
     * @return Result
     * @throws ExceptionInterface If authentication cannot be performed.
     */
    public function authenticate();
}
