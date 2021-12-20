<?php

declare(strict_types=1);

namespace Laminas\Authentication\Adapter\DbTable;

use Exception;
use Laminas\Authentication\Adapter\AbstractAdapter as BaseAdapter;
use Laminas\Authentication\Adapter\DbTable\Exception\RuntimeException;
use Laminas\Authentication\Result as AuthenticationResult;
use Laminas\Db\Adapter\Adapter as DbAdapter;
use Laminas\Db\Sql;
use stdClass;

use function array_keys;
use function count;
use function in_array;
use function is_bool;
use function is_int;

abstract class AbstractAdapter extends BaseAdapter
{
    /**
     * Database Connection
     *
     * @var DbAdapter
     */
    protected $laminasDb;

    /** @var Sql\Select */
    protected $dbSelect;
    /**
     * $tableName - the table name to check
     *
     * @var string
     */
    protected $tableName;

    /**
     * $identityColumn - the column to use as the identity
     *
     * @var string
     */
    protected $identityColumn;

    /**
     * $credentialColumns - columns to be used as the credentials
     *
     * @var string
     */
    protected $credentialColumn;

    /**
     * $authenticateResultInfo
     *
     * @var array
     */
    protected $authenticateResultInfo;

    /**
     * $resultRow - Results of database authentication query
     *
     * @var array
     */
    protected $resultRow;

    /**
     * $ambiguityIdentity - Flag to indicate same Identity can be used with
     * different credentials. Default is FALSE and need to be set to true to
     * allow ambiguity usage.
     *
     * @var bool
     */
    protected $ambiguityIdentity = false;

    /**
     * __construct() - Sets configuration options
     *
     * @param string    $tableName           Optional
     * @param string    $identityColumn      Optional
     * @param string    $credentialColumn    Optional
     */
    public function __construct(
        DbAdapter $laminasDb,
        $tableName = null,
        $identityColumn = null,
        $credentialColumn = null
    ) {
        $this->laminasDb = $laminasDb;

        if (null !== $tableName) {
            $this->setTableName($tableName);
        }

        if (null !== $identityColumn) {
            $this->setIdentityColumn($identityColumn);
        }

        if (null !== $credentialColumn) {
            $this->setCredentialColumn($credentialColumn);
        }
    }

    /**
     * setTableName() - set the table name to be used in the select query
     *
     * @param  string $tableName
     * @return self Provides a fluent interface
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * setIdentityColumn() - set the column name to be used as the identity column
     *
     * @param  string $identityColumn
     * @return self Provides a fluent interface
     */
    public function setIdentityColumn($identityColumn)
    {
        $this->identityColumn = $identityColumn;
        return $this;
    }

    /**
     * setCredentialColumn() - set the column name to be used as the credential column
     *
     * @param  string $credentialColumn
     * @return self Provides a fluent interface
     */
    public function setCredentialColumn($credentialColumn)
    {
        $this->credentialColumn = $credentialColumn;
        return $this;
    }

    /**
     * setAmbiguityIdentity() - sets a flag for usage of identical identities
     * with unique credentials. It accepts integers (0, 1) or boolean (true,
     * false) parameters. Default is false.
     *
     * @param  int|bool $flag
     * @return self Provides a fluent interface
     */
    public function setAmbiguityIdentity($flag)
    {
        if (is_int($flag)) {
            $this->ambiguityIdentity = 1 === $flag;
        } elseif (is_bool($flag)) {
            $this->ambiguityIdentity = $flag;
        }
        return $this;
    }

    /**
     * getAmbiguityIdentity() - returns TRUE for usage of multiple identical
     * identities with different credentials, FALSE if not used.
     *
     * @return bool
     */
    public function getAmbiguityIdentity()
    {
        return $this->ambiguityIdentity;
    }

    /**
     * getDbSelect() - Return the preauthentication Db Select object for userland select query modification
     *
     * @return Sql\Select
     */
    public function getDbSelect()
    {
        if ($this->dbSelect === null) {
            $this->dbSelect = new Sql\Select();
        }
        return $this->dbSelect;
    }

    /**
     * getResultRowObject() - Returns the result row as a stdClass object
     *
     * @param  string|array $returnColumns
     * @param  string|array $omitColumns
     * @return stdClass|bool
     */
    public function getResultRowObject($returnColumns = null, $omitColumns = null)
    {
        if (! $this->resultRow) {
            return false;
        }

        $returnObject = new stdClass();

        if (null !== $returnColumns) {
            $availableColumns = array_keys($this->resultRow);
            foreach ((array) $returnColumns as $returnColumn) {
                if (in_array($returnColumn, $availableColumns)) {
                    $returnObject->{$returnColumn} = $this->resultRow[$returnColumn];
                }
            }
            return $returnObject;
        } elseif (null !== $omitColumns) {
            $omitColumns = (array) $omitColumns;
            foreach ($this->resultRow as $resultColumn => $resultValue) {
                if (! in_array($resultColumn, $omitColumns)) {
                    $returnObject->{$resultColumn} = $resultValue;
                }
            }
            return $returnObject;
        }

        foreach ($this->resultRow as $resultColumn => $resultValue) {
            $returnObject->{$resultColumn} = $resultValue;
        }
        return $returnObject;
    }

    /**
     * This method is called to attempt an authentication. Previous to this
     * call, this adapter would have already been configured with all
     * necessary information to successfully connect to a database table and
     * attempt to find a record matching the provided identity.
     *
     * @throws RuntimeException If answering the authentication query is impossible.
     * @return AuthenticationResult
     */
    public function authenticate()
    {
        $this->authenticateSetup();
        $dbSelect         = $this->authenticateCreateSelect();
        $resultIdentities = $this->authenticateQuerySelect($dbSelect);

        if (($authResult = $this->authenticateValidateResultSet($resultIdentities)) instanceof AuthenticationResult) {
            return $authResult;
        }

        // At this point, ambiguity is already done. Loop, check and break on success.
        foreach ($resultIdentities as $identity) {
            $authResult = $this->authenticateValidateResult($identity);
            if ($authResult->isValid()) {
                break;
            }
        }

        return $authResult;
    }

    /**
     * _authenticateValidateResult() - This method attempts to validate that
     * the record in the resultset is indeed a record that matched the
     * identity provided to this adapter.
     *
     * @param  array $resultIdentity
     * @return AuthenticationResult
     */
    abstract protected function authenticateValidateResult($resultIdentity);

    /**
     * _authenticateCreateSelect() - This method creates a Laminas\Db\Sql\Select object that
     * is completely configured to be queried against the database.
     *
     * @return Sql\Select
     */
    abstract protected function authenticateCreateSelect();

    /**
     * _authenticateSetup() - This method abstracts the steps involved with
     * making sure that this adapter was indeed setup properly with all
     * required pieces of information.
     *
     * @throws RuntimeException In the event that setup was not done properly.
     * @return bool
     */
    protected function authenticateSetup()
    {
        $exception = null;

        if ((string) $this->tableName === '') {
            $exception = 'A table must be supplied for the DbTable authentication adapter.';
        } elseif ((string) $this->identityColumn === '') {
            $exception = 'An identity column must be supplied for the DbTable authentication adapter.';
        } elseif ((string) $this->credentialColumn === '') {
            $exception = 'A credential column must be supplied for the DbTable authentication adapter.';
        } elseif ((string) $this->identity === '') {
            $exception = 'A value for the identity was not provided prior to authentication with DbTable.';
        } elseif ($this->credential === null) {
            $exception = 'A credential value was not provided prior to authentication with DbTable.';
        }

        if (null !== $exception) {
            throw new RuntimeException($exception);
        }

        $this->authenticateResultInfo = [
            'code'     => AuthenticationResult::FAILURE,
            'identity' => $this->identity,
            'messages' => [],
        ];

        return true;
    }

    /**
     * _authenticateQuerySelect() - This method accepts a Laminas\Db\Sql\Select object and
     * performs a query against the database with that object.
     *
     * @throws RuntimeException When an invalid select object is encountered.
     * @return array
     */
    protected function authenticateQuerySelect(Sql\Select $dbSelect)
    {
        $sql       = new Sql\Sql($this->laminasDb);
        $statement = $sql->prepareStatementForSqlObject($dbSelect);
        try {
            $result           = $statement->execute();
            $resultIdentities = [];
            // iterate result, most cross platform way
            foreach ($result as $row) {
                // Laminas-6428 - account for db engines that by default return uppercase column names
                if (isset($row['LAMINAS_AUTH_CREDENTIAL_MATCH'])) {
                    $row['laminas_auth_credential_match'] = $row['LAMINAS_AUTH_CREDENTIAL_MATCH'];
                    unset($row['LAMINAS_AUTH_CREDENTIAL_MATCH']);
                }
                $resultIdentities[] = $row;
            }
        } catch (Exception $e) {
            throw new RuntimeException(
                'The supplied parameters to DbTable failed to '
                . 'produce a valid sql statement, please check table and column names '
                . 'for validity.',
                0,
                $e
            );
        }
        return $resultIdentities;
    }

    /**
     * _authenticateValidateResultSet() - This method attempts to make
     * certain that only one record was returned in the resultset
     *
     * @param  array $resultIdentities
     * @return bool|AuthenticationResult
     */
    protected function authenticateValidateResultSet(array $resultIdentities)
    {
        if (! $resultIdentities) {
            $this->authenticateResultInfo['code']       = AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND;
            $this->authenticateResultInfo['messages'][] = 'A record with the supplied identity could not be found.';
            return $this->authenticateCreateAuthResult();
        } elseif (count($resultIdentities) > 1 && false === $this->getAmbiguityIdentity()) {
            $this->authenticateResultInfo['code']       = AuthenticationResult::FAILURE_IDENTITY_AMBIGUOUS;
            $this->authenticateResultInfo['messages'][] = 'More than one record matches the supplied identity.';
            return $this->authenticateCreateAuthResult();
        }

        return true;
    }

    /**
     * Creates a Laminas\Authentication\Result object from the information that
     * has been collected during the authenticate() attempt.
     *
     * @return AuthenticationResult
     */
    protected function authenticateCreateAuthResult()
    {
        return new AuthenticationResult(
            $this->authenticateResultInfo['code'],
            $this->authenticateResultInfo['identity'],
            $this->authenticateResultInfo['messages']
        );
    }
}
