<?php

declare(strict_types=1);

namespace Laminas\Authentication\Storage;

/**
 * Non-Persistent Authentication Storage
 *
 * Since HTTP Authentication happens again on each request, this will always be
 * re-populated. So there's no need to use sessions, this simple value class
 * will hold the data for rest of the current request.
 */
class NonPersistent implements StorageInterface
{
    /**
     * Holds the actual auth data
     *
     * @var mixed
     */
    protected $data;

    /**
     * Returns true if and only if storage is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->data);
    }

    /**
     * Returns the contents of storage
     * Behavior is undefined when storage is empty.
     *
     * @return mixed
     */
    public function read()
    {
        return $this->data;
    }

    /**
     * Writes $contents to storage
     *
     * @param  mixed $contents
     * @return void
     */
    public function write($contents)
    {
        $this->data = $contents;
    }

    /**
     * Clears contents from storage
     *
     * @return void
     */
    public function clear()
    {
        $this->data = null;
    }
}
