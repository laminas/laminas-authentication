<?php

declare(strict_types=1);

namespace LaminasTest\Authentication\TestAsset;

use Laminas\Authentication\Adapter\AbstractAdapter as AuthenticationAdapter;
use Laminas\Authentication\Result;
use Laminas\Authentication\Result as AuthenticationResult;

class ValidatableAdapter extends AuthenticationAdapter
{
    /** @var int Authentication result code */
    private int $code;

    public function __construct(int $code = AuthenticationResult::SUCCESS)
    {
        $this->code = $code;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(): Result
    {
        return new AuthenticationResult($this->code, 'someIdentity');
    }
}
