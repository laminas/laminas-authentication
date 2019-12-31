<?php

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Authentication\Adapter;

use Exception;
use Laminas\Authentication\Exception\InvalidArgumentException;
use Laminas\Authentication\Exception\RuntimeException;
use Laminas\Authentication\Result;

/**
 * Authentication Adapter authenticates using callback function.
 *
 * The Callback function must return an identity on authentication success,
 * and false on authentication failure.
 */
class Callback extends AbstractAdapter
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * @param callable $callback The authentication callback
     */
    public function __construct($callback = null)
    {
        if (null !== $callback) {
            $this->setCallback($callback);
        }
    }

    /**
     * Authenticate using the provided callback
     *
     * @return Result The authentication result
     * @throws RuntimeException
     */
    public function authenticate()
    {
        $callback = $this->getCallback();
        if (! $callback) {
            throw new RuntimeException('No callback provided');
        }

        try {
            $identity = call_user_func($callback, $this->getIdentity(), $this->getCredential());
        } catch (Exception $e) {
            return new Result(Result::FAILURE_UNCATEGORIZED, null, [$e->getMessage()]);
        }

        if (! $identity) {
            return new Result(Result::FAILURE, null, ['Authentication failure']);
        }

        return new Result(Result::SUCCESS, $identity, ['Authentication success']);
    }

    /**
     * Gets the value of callback.
     *
     * @return null|callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * Sets the value of callback.
     *
     * @param  callable $callback the callback
     * @throws InvalidArgumentException
     */
    public function setCallback($callback)
    {
        if (! is_callable($callback)) {
            throw new InvalidArgumentException('Invalid callback provided');
        }

        $this->callback = $callback;
    }
}
