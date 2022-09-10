<?php

declare(strict_types=1);

namespace Laminas\Authentication\Adapter;

use Laminas\Authentication\Result as AuthenticationResult;
use Laminas\Crypt\Utils as CryptUtils;
use Laminas\Stdlib\ErrorHandler;

use function fgets;
use function fopen;
use function md5;
use function strpos;
use function substr;
use function trim;

use const E_WARNING;

/**
 * @deprecated Since 2.10.0; to be removed in 3.0.0. Digest authentication has
 *     known security issues due to the usage of MD5 for hash comparisons.
 *     We recommend usage of HTTP Basic, LDAP, DbTable, or a custom adapter that
 *     makes usage of strong hashing algorithms, preferably via usage of
 *     password_hash and password_verify.
 */
class Digest extends AbstractAdapter
{
    /**
     * Filename against which authentication queries are performed
     *
     * @var string
     */
    protected $filename;

    /**
     * Digest authentication realm
     *
     * @var string
     */
    protected $realm;

    /**
     * Sets adapter options
     *
     * @param  mixed $filename
     * @param  mixed $realm
     * @param  mixed $identity
     * @param  mixed $credential
     */
    public function __construct($filename = null, $realm = null, $identity = null, $credential = null)
    {
        if ($filename !== null) {
            $this->setFilename($filename);
        }
        if ($realm !== null) {
            $this->setRealm($realm);
        }
        if ($identity !== null) {
            $this->setIdentity($identity);
        }
        if ($credential !== null) {
            $this->setCredential($credential);
        }
    }

    /**
     * Returns the filename option value or null if it has not yet been set
     *
     * @return string|null
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Sets the filename option value
     *
     * @param  mixed $filename
     * @return self Provides a fluent interface
     */
    public function setFilename($filename)
    {
        $this->filename = (string) $filename;
        return $this;
    }

    /**
     * Returns the realm option value or null if it has not yet been set
     *
     * @return string|null
     */
    public function getRealm()
    {
        return $this->realm;
    }

    /**
     * Sets the realm option value
     *
     * @param  mixed $realm
     * @return self Provides a fluent interface
     */
    public function setRealm($realm)
    {
        $this->realm = (string) $realm;
        return $this;
    }

    /**
     * Returns the username option value or null if it has not yet been set
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->getIdentity();
    }

    /**
     * Sets the username option value
     *
     * @param  mixed $username
     * @return self Provides a fluent interface
     */
    public function setUsername($username)
    {
        return $this->setIdentity($username);
    }

    /**
     * Returns the password option value or null if it has not yet been set
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->getCredential();
    }

    /**
     * Sets the password option value
     *
     * @param  mixed $password
     * @return self Provides a fluent interface
     */
    public function setPassword($password)
    {
        return $this->setCredential($password);
    }

    /**
     * Defined by Laminas\Authentication\Adapter\AdapterInterface
     *
     * @throws Exception\ExceptionInterface
     * @return AuthenticationResult
     */
    public function authenticate()
    {
        $optionsRequired = ['filename', 'realm', 'identity', 'credential'];
        foreach ($optionsRequired as $optionRequired) {
            if (null === $this->$optionRequired) {
                throw new Exception\RuntimeException("Option '$optionRequired' must be set before authentication");
            }
        }

        ErrorHandler::start(E_WARNING);
        $fileHandle = fopen($this->filename, 'r');
        $error      = ErrorHandler::stop();
        if (false === $fileHandle) {
            throw new Exception\UnexpectedValueException("Cannot open '$this->filename' for reading", 0, $error);
        }

        $id = "$this->identity:$this->realm";

        $result = [
            'code'     => AuthenticationResult::FAILURE,
            'identity' => [
                'realm'    => $this->realm,
                'username' => $this->identity,
            ],
            'messages' => [],
        ];

        while (($line = fgets($fileHandle)) !== false) {
            $line = trim($line);
            if (empty($line)) {
                break;
            }
            if (0 === strpos($line, $id)) {
                if (
                    CryptUtils::compareStrings(
                        substr($line, -32),
                        md5("$this->identity:$this->realm:$this->credential")
                    )
                ) {
                    return new AuthenticationResult(
                        AuthenticationResult::SUCCESS,
                        $result['identity'],
                        $result['messages']
                    );
                }
                $result['messages'][] = 'Password incorrect';
                return new AuthenticationResult(
                    AuthenticationResult::FAILURE_CREDENTIAL_INVALID,
                    $result['identity'],
                    $result['messages']
                );
            }
        }

        $result['messages'][] = "Username '$this->identity' and realm '$this->realm' combination not found";
        return new AuthenticationResult(
            AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND,
            $result['identity'],
            $result['messages']
        );
    }
}
