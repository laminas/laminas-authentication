<?php

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Authentication\Adapter;

use Laminas\Authentication\Adapter;

/**
 * @category   Laminas
 * @package    Laminas_Auth
 * @subpackage UnitTests
 * @group      Laminas_Auth
 * @group      Laminas_Db_Table
 */
class DbTableTest extends DbTable\CredentialTreatmentAdapterTest
{

    protected function _setupAuthAdapter()
    {
        $this->_adapter = new Adapter\DbTable($this->_db, 'users', 'username', 'password');
    }

}
