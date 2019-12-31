<?php

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Authentication\Adapter;

interface ValidatableAdapterInterface extends AdapterInterface
{
    /**
     * Returns the identity of the account being authenticated, or
     * NULL if none is set.
     *
     * @return mixed
     */
    public function getIdentity();

    /**
     * Sets the identity for binding
     *
     * @param  mixed                       $identity
     * @return ValidatableAdapterInterface
     */
    public function setIdentity($identity);

    /**
     * Returns the credential of the account being authenticated, or
     * NULL if none is set.
     *
     * @return mixed
     */
    public function getCredential();

    /**
     * Sets the credential for binding
     *
     * @param  mixed                       $credential
     * @return ValidatableAdapterInterface
     */
    public function setCredential($credential);
}
