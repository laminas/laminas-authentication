<?php

declare(strict_types=1);

namespace LaminasTest\Authentication\TestAsset;

use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Result as AuthenticationResult;

class SuccessAdapter implements AdapterInterface
{
    public function authenticate()
    {
        return new AuthenticationResult(AuthenticationResult::SUCCESS, 'someIdentity');
    }
}
