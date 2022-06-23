<?php

declare(strict_types=1);

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 */

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
