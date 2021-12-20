<?php

namespace Laminas\Authentication\Adapter;

interface AdapterInterface
{
    /**
     * Performs an authentication attempt
     *
     * @return \Laminas\Authentication\Result
     * @throws \Laminas\Authentication\Adapter\Exception\ExceptionInterface If authentication cannot be performed
     */
    public function authenticate();
}
