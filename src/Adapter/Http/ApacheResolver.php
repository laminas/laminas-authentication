<?php

declare(strict_types=1);

namespace Laminas\Authentication\Adapter\Http;

use Laminas\Authentication\Result as AuthResult;
use Laminas\Crypt\Password\Apache as ApachePassword;
use Laminas\Stdlib\ErrorHandler;

use function ctype_print;
use function fclose;
use function fgetcsv;
use function fopen;
use function is_readable;
use function strpos;

use const E_WARNING;

/**
 * Apache Authentication Resolver
 *
 * @see http://httpd.apache.org/docs/2.2/misc/password_encryptions.html
 */
class ApacheResolver implements ResolverInterface
{
    /**
     * Path to credentials file
     *
     * @var string|null
     */
    protected $file;

    /**
     * Apache password object
     *
     * @var ApachePassword|null
     */
    protected $apachePassword;

    /**
     * Constructor
     *
     * @param  string $path Complete filename where the credentials are stored
     */
    public function __construct($path = '')
    {
        if (! empty($path)) {
            $this->setFile($path);
        }
    }

    /**
     * Set the path to the credentials file
     *
     * @param  string $path
     * @return self Provides a fluent interface
     * @throws Exception\InvalidArgumentException If path is not readable.
     */
    public function setFile($path)
    {
        if (empty($path) || ! is_readable($path)) {
            throw new Exception\InvalidArgumentException('Path not readable: ' . $path);
        }
        $this->file = $path;

        return $this;
    }

    /**
     * Returns the path to the credentials file
     *
     * @return string|null
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Returns the Apache Password object
     *
     * @return ApachePassword
     */
    protected function getApachePassword()
    {
        if (empty($this->apachePassword)) {
            $this->apachePassword = new ApachePassword();
        }
        return $this->apachePassword;
    }

    /**
     * Resolve credentials
     *
     * @param  string $username Username
     * @param  string $realm    Authentication Realm
     * @param  string $password The password to authenticate
     * @return AuthResult
     * @throws Exception\ExceptionInterface
     */
    public function resolve($username, $realm, $password = null)
    {
        if (empty($username)) {
            throw new Exception\InvalidArgumentException('Username is required');
        }

        if (! ctype_print($username) || strpos($username, ':') !== false) {
            throw new Exception\InvalidArgumentException(
                'Username must consist only of printable characters, excluding the colon'
            );
        }

        if (! empty($realm) && (! ctype_print($realm) || strpos($realm, ':') !== false)) {
            throw new Exception\InvalidArgumentException(
                'Realm must consist only of printable characters, excluding the colon'
            );
        }

        if (empty($password)) {
            throw new Exception\InvalidArgumentException('Password is required');
        }

        // Open file, read through looking for matching credentials
        ErrorHandler::start(E_WARNING);
        $fp    = fopen($this->file, 'r');
        $error = ErrorHandler::stop();
        if (! $fp) {
            throw new Exception\RuntimeException('Unable to open password file: ' . $this->file, 0, $error);
        }

        // No real validation is done on the contents of the password file. The
        // assumption is that we trust the administrators to keep it secure.
        while (($line = fgetcsv($fp, 512, ':')) !== false) {
            if ($line[0] !== $username) {
                continue;
            }

            if (isset($line[2])) {
                if ($line[1] === $realm) {
                    $matchedHash = $line[2];
                    break;
                }
                continue;
            }

            $matchedHash = $line[1];
            break;
        }
        fclose($fp);

        if (! isset($matchedHash)) {
            return new AuthResult(
                AuthResult::FAILURE_IDENTITY_NOT_FOUND,
                null,
                ['Username not found in provided htpasswd file']
            );
        }

        // Plaintext password
        if ($matchedHash === $password) {
            return new AuthResult(AuthResult::SUCCESS, $username);
        }

        $apache = $this->getApachePassword();
        $apache->setUserName($username);
        if (! empty($realm)) {
            $apache->setAuthName($realm);
        }

        if ($apache->verify($password, $matchedHash)) {
            return new AuthResult(AuthResult::SUCCESS, $username);
        }

        return new AuthResult(AuthResult::FAILURE_CREDENTIAL_INVALID, null, ['Passwords did not match.']);
    }
}
