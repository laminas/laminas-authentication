<?php

declare(strict_types=1);

namespace LaminasTest\Authentication\Adapter;

use Laminas\Authentication\Adapter;

/**
 * @group      Laminas_Auth
 * @group      Laminas_Db_Table
 */
class DbTableTest extends DbTable\CredentialTreatmentAdapterTest
{
    // @codingStandardsIgnoreStart
    /**
     * @return void
     */
    protected function _setupAuthAdapter()
    {
        // @codingStandardsIgnoreEnd
        $this->_adapter = new Adapter\DbTable($this->_db, 'users', 'username', 'password');
    }
}
