<?php

declare(strict_types=1);

namespace LaminasTest\Authentication\Adapter;

use Laminas\Authentication\Adapter;

final class DbTableTest extends DbTable\CredentialTreatmentAdapterTest
{
    // @codingStandardsIgnoreStart
    #[\Override]
    protected function _setupAuthAdapter(): void
    {
        // @codingStandardsIgnoreEnd
        $this->_adapter = new Adapter\DbTable($this->_db, 'users', 'username', 'password');
    }
}
