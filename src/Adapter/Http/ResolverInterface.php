<?php

declare(strict_types=1);

namespace Laminas\Authentication\Adapter\Http;

/**
 * Auth HTTP Resolver Interface
 *
 * Defines an interface to resolve a username/realm combination into a shared
 * secret usable by HTTP Authentication.
 */
interface ResolverInterface
{
    /**
     * Resolve username/realm to password/hash/etc.
     *
     * @param  string $username Username
     * @param  string $realm    Authentication Realm
     * @param  string $password Password (optional)
     * @return string|array|false User's shared secret as string if found in realm, or User's identity as array
     *         if resolved, false otherwise.
     */
    public function resolve($username, $realm, $password = null);
}
