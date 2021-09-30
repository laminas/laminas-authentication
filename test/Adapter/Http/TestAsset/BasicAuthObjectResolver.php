<?php

namespace LaminasTest\Authentication\Adapter\Http\TestAsset;

use Laminas\Authentication\Result as AuthenticationResult;
use Laminas\Authentication\Adapter\Http\ResolverInterface;

class BasicAuthObjectResolver implements ResolverInterface
{
    public function resolve($username, $realm, $password = null): AuthenticationResult
    {
        if ($username == 'Bryce' && $password == 'ThisIsNotMyPassword') {
            $identity = new \stdClass();

            return new AuthenticationResult(
                AuthenticationResult::SUCCESS,
                $identity,
                ['Authentication successful.']
            );
        }

        return new AuthenticationResult(
            AuthenticationResult::FAILURE,
            null,
            ['Authentication failed.']
        );
    }
}
