<?php

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Authentication\Adapter;

use Laminas\Authentication\Adapter;

/**
 * @group      Laminas_Auth
 * @group      Laminas_Db_Table
 */
class DbTableTest extends DbTable\CredentialTreatmentAdapterTest
{
    // @codingStandardsIgnoreStart
    protected function _setupAuthAdapter()
    {
        // @codingStandardsIgnoreEnd
        $this->_adapter = new Adapter\DbTable($this->_db, 'users', 'username', 'password');
    }
}
