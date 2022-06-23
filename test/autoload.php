<?php

declare(strict_types=1);

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 */

use PHPUnit\Framework\Error\Deprecated;

if (! class_exists(Deprecated::class)) {
    class_alias(PHPUnit_Framework_Error_Deprecated::class, Deprecated::class, true);
}
