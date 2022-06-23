<?php

declare(strict_types=1);

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 */

namespace LaminasTest\Authentication\Adapter\Http\TestAsset;

use Laminas\Authentication\Adapter\Http\ResolverInterface;
use Laminas\Authentication\Result as AuthenticationResult;
use stdClass;

class BasicAuthObjectResolver implements ResolverInterface
{
    public function resolve($username, $realm, $password = null): AuthenticationResult
    {
        if ($username == 'Bryce' && $password == 'ThisIsNotMyPassword') {
            $identity = new stdClass();

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
