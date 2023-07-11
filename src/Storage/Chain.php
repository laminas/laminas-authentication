<?php

declare(strict_types=1);

namespace Laminas\Authentication\Storage;

use Laminas\Stdlib\PriorityQueue;

class Chain implements StorageInterface
{
    /**
     * Contains all storage that this authentication method uses. A storage
     * placed in the priority queue with a higher priority is always used
     * before using a storage with a lower priority.
     *
     * @var PriorityQueue<StorageInterface, int>
     */
    protected $storageChain;

    /**
     * Initializes the priority queue.
     */
    public function __construct()
    {
        /** @psalm-var PriorityQueue<StorageInterface, int> */
        $this->storageChain = new PriorityQueue();
    }

    /**
     * @param int          $priority
     * @return void
     */
    public function add(StorageInterface $storage, $priority = 1)
    {
        $this->storageChain->insert($storage, $priority);
    }

    /**
     * Loop over the queue of storage until a storage is found that is non-empty. If such
     * storage is not found, then this chain storage itself is empty.
     *
     * In case a non-empty storage is found then this chain storage is also non-empty. Report
     * that, but also make sure that all storage with higher priority that are empty
     * are filled.
     *
     * @see StorageInterface::isEmpty()
     *
     * @return bool
     */
    public function isEmpty()
    {
        $storageWithHigherPriority = [];

        // Loop invariant: $storageWithHigherPriority contains all storage with higher priority
        // than the current one.
        foreach ($this->storageChain as $storage) {
            if ($storage->isEmpty()) {
                $storageWithHigherPriority[] = $storage;
                continue;
            }

            $storageValue = $storage->read();
            foreach ($storageWithHigherPriority as $higherPriorityStorage) {
                $higherPriorityStorage->write($storageValue);
            }

            return false;
        }

        return true;
    }

    /**
     * If the chain is non-empty then the storage with the top priority is guaranteed to be
     * filled. Return its value.
     *
     * @see StorageInterface::read()
     *
     * @return mixed
     */
    public function read()
    {
        return $this->storageChain->top()->read();
    }

    /**
     * Write the new $contents to all storage in the chain.
     *
     * @see StorageInterface::write()
     *
     * @param mixed $contents
     * @return void
     */
    public function write($contents)
    {
        foreach ($this->storageChain as $storage) {
            $storage->write($contents);
        }
    }

    /**
     * Clear all storage in the chain.
     *
     * @see StorageInterface::clear()
     */
    public function clear()
    {
        foreach ($this->storageChain as $storage) {
            $storage->clear();
        }
    }
}
