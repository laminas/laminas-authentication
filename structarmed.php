<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;

return Architecture::define()
    ->withPresets(Preset::PSR4(), Preset::CODEQUALITY())
    ->layer('Exception', 'src/Exception')
    ->layer('Result', 'src/Result.php')
    ->layer('Storage', 'src/Storage')
    ->layer('AdapterException', 'src/Adapter/Exception')
    ->layer('Adapter', 'src/Adapter', [
        'src/Adapter/Exception',
        'src/Adapter/DbTable.php',
        'src/Adapter/DbTable',
        'src/Adapter/Http.php',
        'src/Adapter/Http',
    ])
    ->layer('DbTableException', 'src/Adapter/DbTable/Exception')
    ->layer('DbTable', ['src/Adapter/DbTable.php', 'src/Adapter/DbTable'], 'src/Adapter/DbTable/Exception')
    ->layer('HttpException', 'src/Adapter/Http/Exception')
    ->layer('Http', ['src/Adapter/Http.php', 'src/Adapter/Http'], 'src/Adapter/Http/Exception')
    ->layer('AuthenticationService', [
        'src/AuthenticationService.php',
        'src/AuthenticationServiceInterface.php',
    ])
    ->layer('Validator', 'src/Validator')
    ->ruleset([
        'Exception'             => [],
        'Result'                => [],
        'Storage'               => ['Exception'],
        'AdapterException'      => ['Exception'],
        'Adapter'               => ['+AdapterException', 'Result'],
        'DbTableException'      => ['+AdapterException'],
        'DbTable'               => ['+Adapter', '+DbTableException'],
        'HttpException'         => ['+AdapterException'],
        'Http'                  => ['+Adapter', '+HttpException'],
        'AuthenticationService' => ['+Adapter', '+Storage'],
        'Validator'             => ['+AuthenticationService'],
    ]);
