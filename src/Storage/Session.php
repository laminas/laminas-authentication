<?php

declare(strict_types=1);

namespace Laminas\Authentication\Storage;

use Laminas\Session\Container as SessionContainer;
use Laminas\Session\ManagerInterface as SessionManager;

class Session implements StorageInterface
{
    /**
     * Default session namespace
     */
    public const NAMESPACE_DEFAULT = 'Laminas_Auth';

    /**
     * Default session object member name
     */
    public const MEMBER_DEFAULT = 'storage';

    /**
     * Object to proxy $_SESSION storage
     *
     * @var SessionContainer
     */
    protected $session;

    /**
     * Session namespace
     *
     * @var string
     */
    protected $namespace = self::NAMESPACE_DEFAULT;

    /**
     * Session object member
     *
     * @var string
     */
    protected $member = self::MEMBER_DEFAULT;

    /**
     * Sets session storage options and initializes session namespace object
     *
     * @param  string|null $namespace
     * @param  string|null $member
     */
    public function __construct($namespace = null, $member = null, ?SessionManager $manager = null)
    {
        if ($namespace !== null) {
            $this->namespace = $namespace;
        }
        if ($member !== null) {
            $this->member = $member;
        }
        $this->session = new SessionContainer($this->namespace, $manager);
    }

    /**
     * Returns the session namespace
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Returns the name of the session object member
     *
     * @return string
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * Defined by Laminas\Authentication\Storage\StorageInterface
     *
     * @return bool
     */
    public function isEmpty()
    {
        return ! isset($this->session->{$this->member});
    }

    /**
     * Defined by Laminas\Authentication\Storage\StorageInterface
     *
     * @return mixed
     */
    public function read()
    {
        return $this->session->{$this->member};
    }

    /**
     * Defined by Laminas\Authentication\Storage\StorageInterface
     *
     * @param  mixed $contents
     * @return void
     */
    public function write($contents)
    {
        $this->session->{$this->member} = $contents;
    }

    /**
     * Defined by Laminas\Authentication\Storage\StorageInterface
     *
     * @return void
     */
    public function clear()
    {
        unset($this->session->{$this->member});
    }
}
