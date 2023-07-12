<?php

declare(strict_types=1);

namespace Laminas\Authentication\Adapter;

use Exception;
use Laminas\Authentication\Exception\InvalidArgumentException;
use Laminas\Authentication\Exception\RuntimeException;
use Laminas\Authentication\Result;

use function call_user_func;
use function is_callable;

/**
 * Authentication Adapter authenticates using callback function.
 *
 * The Callback function must return an identity on authentication success,
 * and false on authentication failure.
 */
class Callback extends AbstractAdapter
{
    /** @var callable(mixed, mixed): mixed|false */
    protected $callback;

    /**
     * @param callable(mixed, mixed): mixed|false $callback The authentication callback
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
     * @return null|(callable(mixed, mixed): mixed|false)
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * Sets the value of callback.
     *
     * @param callable(mixed, mixed): mixed|false $callback the callback
     * @throws InvalidArgumentException
     * @return void
     */
    public function setCallback($callback)
    {
        if (! is_callable($callback)) {
            throw new InvalidArgumentException('Invalid callback provided');
        }

        $this->callback = $callback;
    }
}
