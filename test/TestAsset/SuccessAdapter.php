<?php

declare(strict_types=1);

namespace LaminasTest\Authentication\TestAsset;

use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Result as AuthenticationResult;
use Override;

final class SuccessAdapter implements AdapterInterface
{
    #[Override]
    public function authenticate(): AuthenticationResult
    {
        return new AuthenticationResult(AuthenticationResult::SUCCESS, 'someIdentity');
    }
}
