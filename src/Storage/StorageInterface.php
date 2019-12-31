<?php

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Authentication\Storage;

interface StorageInterface
{
    /**
     * Returns true if and only if storage is empty
     *
     * @throws \Laminas\Authentication\Exception\ExceptionInterface If it is impossible to determine whether storage
     *                                                           is empty
     * @return bool
     */
    public function isEmpty();

    /**
     * Returns the contents of storage
     *
     * Behavior is undefined when storage is empty.
     *
     * @throws \Laminas\Authentication\Exception\ExceptionInterface If reading contents from storage is impossible
     * @return mixed
     */
    public function read();

    /**
     * Writes $contents to storage
     *
     * @param  mixed $contents
     * @throws \Laminas\Authentication\Exception\ExceptionInterface If writing $contents to storage is impossible
     * @return void
     */
    public function write($contents);

    /**
     * Clears contents from storage
     *
     * @throws \Laminas\Authentication\Exception\ExceptionInterface If clearing contents from storage is impossible
     * @return void
     */
    public function clear();
}
