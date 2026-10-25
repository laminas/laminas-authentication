<?php

declare(strict_types=1);

namespace LaminasTest\Authentication\TestAsset;

use Laminas\Authentication\Adapter\AbstractAdapter as AuthenticationAdapter;
use Laminas\Authentication\Result;
use Laminas\Authentication\Result as AuthenticationResult;
use Override;

final class ValidatableAdapter extends AuthenticationAdapter
{
    public function __construct(
        /** @var int Authentication result code */
        private readonly int $code = AuthenticationResult::SUCCESS
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function authenticate(): Result
    {
        return new AuthenticationResult($this->code, 'someIdentity');
    }
}
