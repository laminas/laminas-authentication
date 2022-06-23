<?php

declare(strict_types=1);

namespace LaminasTest\Authentication\TestAsset;

use Laminas\Authentication\Adapter\AbstractAdapter as AuthenticationAdapter;
use Laminas\Authentication\Result as AuthenticationResult;

class ValidatableAdapter extends AuthenticationAdapter
{
    /** @var int Authentication result code */
    private $code;

    /**
     * @param int $code
     */
    public function __construct($code = AuthenticationResult::SUCCESS)
    {
        $this->code = $code;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate()
    {
        return new AuthenticationResult($this->code, 'someIdentity');
    }
}
