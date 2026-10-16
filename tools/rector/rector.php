<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPhpSets()
    ->withPaths([
        __DIR__ . '/../../src',
        __DIR__ . '/../../test',
    ]);
