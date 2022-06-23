<?php

declare(strict_types=1);

namespace Laminas\Authentication\Storage;

use Laminas\Authentication\Exception\ExceptionInterface;

interface StorageInterface
{
    /**
     * Returns true if and only if storage is empty
     *
     * @throws ExceptionInterface If it is impossible to determine whether storage is empty.
     * @return bool
     */
    public function isEmpty();

    /**
     * Returns the contents of storage
     *
     * Behavior is undefined when storage is empty.
     *
     * @throws ExceptionInterface If reading contents from storage is impossible.
     * @return mixed
     */
    public function read();

    /**
     * Writes $contents to storage
     *
     * @param  mixed $contents
     * @throws ExceptionInterface If writing $contents to storage is impossible.
     * @return void
     */
    public function write($contents);

    /**
     * Clears contents from storage
     *
     * @throws ExceptionInterface If clearing contents from storage is impossible.
     * @return void
     */
    public function clear();
}
