<?php

declare(strict_types=1);

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 */

namespace LaminasTest\Authentication\Adapter\TestAsset;

use Laminas\Http\Response;
use Laminas\OpenId\OpenId;

OpenId::$exitOnRedirect = false;

class OpenIdResponseHelper extends Response
{
    private $_canSendHeaders;

    public function __construct($canSendHeaders)
    {
        $this->_canSendHeaders = $canSendHeaders;
    }

    public function canSendHeaders($throw = false)
    {
        return $this->_canSendHeaders;
    }

    public function sendResponse(): void
    {
    }
}
