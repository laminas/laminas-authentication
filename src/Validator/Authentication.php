<?php

declare(strict_types=1);

namespace Laminas\Authentication\Validator;

use Laminas\Authentication\Adapter\ValidatableAdapterInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Exception;
use Laminas\Authentication\Result;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Validator\AbstractValidator;
use Traversable;

use function array_key_exists;
use function gettype;
use function is_array;
use function is_object;
use function is_string;
use function sprintf;

/**
 * Authentication Validator
 */
class Authentication extends AbstractValidator
{
    /**
     * Error codes
     *
     * @const string
     */
    public const IDENTITY_NOT_FOUND = 'identityNotFound';
    public const IDENTITY_AMBIGUOUS = 'identityAmbiguous';
    public const CREDENTIAL_INVALID = 'credentialInvalid';
    public const UNCATEGORIZED      = 'uncategorized';
    public const GENERAL            = 'general';

    /**
     * Authentication\Result codes mapping
     *
     * @const array
     */
    public const CODE_MAP = [
        Result::FAILURE_IDENTITY_NOT_FOUND => self::IDENTITY_NOT_FOUND,
        Result::FAILURE_CREDENTIAL_INVALID => self::CREDENTIAL_INVALID,
        Result::FAILURE_IDENTITY_AMBIGUOUS => self::IDENTITY_AMBIGUOUS,
        Result::FAILURE_UNCATEGORIZED      => self::UNCATEGORIZED,
    ];

    /**
     * Authentication\Result codes mapping configurable overrides
     *
     * @var string[]
     */
    protected $codeMap = [];

    /**
     * Error Messages
     *
     * @var array
     */
    protected $messageTemplates = [
        self::IDENTITY_NOT_FOUND => 'Invalid identity',
        self::IDENTITY_AMBIGUOUS => 'Identity is ambiguous',
        self::CREDENTIAL_INVALID => 'Invalid password',
        self::UNCATEGORIZED      => 'Authentication failed',
        self::GENERAL            => 'Authentication failed',
    ];

    /**
     * Authentication Adapter
     *
     * @var null|ValidatableAdapterInterface
     */
    protected $adapter;

    /**
     * Identity (or field)
     *
     * @var string
     */
    protected $identity;

    /**
     * Credential (or field)
     *
     * @var string
     */
    protected $credential;

    /**
     * Authentication Service
     *
     * @var null|AuthenticationService
     */
    protected $service;

    /**
     * Sets validator options
     *
     * @param array<string, mixed>|Traversable<string, mixed> $options
     */
    public function __construct($options = null)
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (is_array($options)) {
            if (isset($options['adapter'])) {
                $this->setAdapter($options['adapter']);
            }
            if (isset($options['identity'])) {
                $this->setIdentity($options['identity']);
            }
            if (isset($options['credential'])) {
                $this->setCredential($options['credential']);
            }
            if (isset($options['service'])) {
                $this->setService($options['service']);
            }
            if (isset($options['code_map'])) {
                foreach ($options['code_map'] as $code => $template) {
                    if (empty($template) || ! is_string($template)) {
                        throw new Exception\InvalidArgumentException(
                            'Message key in code_map option must be a non-empty string'
                        );
                    }
                    if (! isset($this->messageTemplates[$template])) {
                        $this->messageTemplates[$template] = $this->messageTemplates[static::GENERAL];
                    }
                    $this->codeMap[(int) $code] = $template;
                }
            }
        }
        parent::__construct($options);
    }

    /**
     * Get Adapter
     *
     * @return null|ValidatableAdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Set Adapter
     *
     * @return self Provides a fluent interface
     */
    public function setAdapter(ValidatableAdapterInterface $adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * Get Identity
     *
     * @return mixed
     */
    public function getIdentity()
    {
        return $this->identity;
    }

    /**
     * Set Identity
     *
     * @param mixed $identity
     * @return self Provides a fluent interface
     */
    public function setIdentity($identity)
    {
        $this->identity = $identity;
        return $this;
    }

    /**
     * Get Credential
     *
     * @return mixed
     */
    public function getCredential()
    {
        return $this->credential;
    }

    /**
     * Set Credential
     *
     * @param mixed $credential
     * @return self Provides a fluent interface
     */
    public function setCredential($credential)
    {
        $this->credential = $credential;

        return $this;
    }

    /**
     * Get Service
     *
     * @return null|AuthenticationService
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Set Service
     *
     * @return self Provides a fluent interface
     */
    public function setService(AuthenticationService $service)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Returns true if and only if authentication result is valid
     *
     * If authentication result fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param null|mixed $value OPTIONAL Credential (or field)
     * @param null|array $context OPTIONAL Authentication data (identity and/or credential)
     * @return bool
     * @throws Exception\RuntimeException
     */
    public function isValid($value = null, $context = null)
    {
        if ($value !== null) {
            $this->setCredential($value);
        }

        if ($this->identity === null) {
            throw new Exception\RuntimeException('Identity must be set prior to validation');
        }

        $identity = ($context !== null) && array_key_exists($this->identity, $context)
            ? $context[$this->identity]
            : $this->identity;

        if ($this->credential === null) {
            throw new Exception\RuntimeException('Credential must be set prior to validation');
        }

        $credential = ($context !== null) && array_key_exists($this->credential, $context)
            ? $context[$this->credential]
            : $this->credential;

        if (! $this->service) {
            throw new Exception\RuntimeException('AuthenticationService must be set prior to validation');
        }

        $adapter = $this->adapter ?: $this->getAdapterFromAuthenticationService();
        $adapter->setIdentity($identity);
        $adapter->setCredential($credential);

        $result = $this->service->authenticate($adapter);

        if ($result->isValid()) {
            return true;
        }

        $messageKey = $this->mapResultCodeToMessageKey($result->getCode());
        $this->error($messageKey);

        return false;
    }

    /**
     * @param int $code Authentication result code
     * @return string Message key that should be used for the code
     */
    protected function mapResultCodeToMessageKey($code)
    {
        if (isset($this->codeMap[$code])) {
            return $this->codeMap[$code];
        }
        if (array_key_exists($code, static::CODE_MAP)) {
            return static::CODE_MAP[$code];
        }
        return self::GENERAL;
    }

    /**
     * @return ValidatableAdapterInterface
     * @throws Exception\RuntimeException If no adapter present in
     *     authentication service.
     * @throws Exception\RuntimeException If adapter present in authentication
     *     service is not a ValidatableAdapterInterface instance.
     */
    private function getAdapterFromAuthenticationService()
    {
        if (! $this->service) {
            throw new Exception\RuntimeException('Adapter must be set prior to validation');
        }

        $adapter = $this->service->getAdapter();
        if (! $adapter) {
            throw new Exception\RuntimeException('Adapter must be set prior to validation');
        }

        if (! $adapter instanceof ValidatableAdapterInterface) {
            throw new Exception\RuntimeException(sprintf(
                'Adapter must be an instance of %s; %s given',
                ValidatableAdapterInterface::class,
                is_object($adapter) ? $adapter::class : gettype($adapter)
            ));
        }

        return $adapter;
    }
}
