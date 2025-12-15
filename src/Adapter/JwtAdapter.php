<?php
/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */
namespace Laminas\Authentication\Adapter;

use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Result;
use Laminas\Authentication\Jwt;

class JwtAdapter implements AdapterInterface
{

    /** @var string **/
    private $token;

    /** @var string **/
    private $subject;

    /**
     *
     * @param string $token
     * @param string $subject
     */
    public function __construct(string $token, string $subject)
    {
        $this->token = $token;
        $this->subject = $subject;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Laminas\Authentication\Adapter\AdapterInterface::authenticate()
     */
    public function authenticate()
    {
        $payload = Jwt::getPayload($this->token);
        $result = Result::FAILURE_IDENTITY_NOT_FOUND;
        $identity = 'unknown';
        $messages = [
            'Failure identity not found'
        ];
        if ($payload == false) {
            $result = Result::FAILURE_CREDENTIAL_INVALID;
            $messages[] = 'Failure credential invalid';
        } elseif ($payload->sub == $this->subject) {
            $result = Result::SUCCESS;
            $identity = $this->subject;
        }
        return new Result($result, $identity, $messages);
    }
}