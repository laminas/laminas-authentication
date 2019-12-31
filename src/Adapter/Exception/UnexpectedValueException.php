<?php

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Authentication\Adapter\Exception;

use Laminas\Authentication\Exception;

/**
 * @category   Laminas
 * @package    Laminas_Authentication
 * @subpackage Adapter
 */
class UnexpectedValueException extends Exception\UnexpectedValueException implements
    ExceptionInterface
{
}
