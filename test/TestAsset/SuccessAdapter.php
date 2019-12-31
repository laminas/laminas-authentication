<?php

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Authentication\TestAsset;

use Laminas\Authentication\Adapter\AbstractAdapter as AuthenticationAdapter;
use Laminas\Authentication\Result as AuthenticationResult;

class SuccessAdapter extends AuthenticationAdapter
{
    public function authenticate()
    {
        return new AuthenticationResult(true, 'someIdentity');
    }
}
