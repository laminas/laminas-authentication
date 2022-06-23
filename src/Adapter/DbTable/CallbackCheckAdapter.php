<?php

declare(strict_types=1);

namespace Laminas\Authentication\Adapter\DbTable;

use Exception;
use Laminas\Authentication\Adapter\DbTable\Exception\InvalidArgumentException;
use Laminas\Authentication\Result as AuthenticationResult;
use Laminas\Db\Adapter\Adapter as DbAdapter;
use Laminas\Db\Sql;
use Laminas\Db\Sql\Predicate\Operator as SqlOp;

use function call_user_func;
use function is_callable;

class CallbackCheckAdapter extends AbstractAdapter
{
    /**
     * $credentialValidationCallback - This overrides the Treatment usage to provide a callback
     * that allows for validation to happen in code
     *
     * @var callable
     */
    protected $credentialValidationCallback;

    /**
     * __construct() - Sets configuration options
     *
     * @param string    $tableName                    Optional
     * @param string    $identityColumn               Optional
     * @param string    $credentialColumn             Optional
     * @param callable  $credentialValidationCallback Optional
     */
    public function __construct(
        DbAdapter $laminasDb,
        $tableName = null,
        $identityColumn = null,
        $credentialColumn = null,
        $credentialValidationCallback = null
    ) {
        parent::__construct($laminasDb, $tableName, $identityColumn, $credentialColumn);

        if (null !== $credentialValidationCallback) {
            $this->setCredentialValidationCallback($credentialValidationCallback);
        } else {
            $this->setCredentialValidationCallback(function ($a, $b) {
                return $a === $b;
            });
        }
    }

    /**
     * setCredentialValidationCallback() - allows the developer to use a callback as a way of checking the
     * credential.
     *
     * @param callable $validationCallback
     * @return self Provides a fluent interface
     * @throws InvalidArgumentException
     */
    public function setCredentialValidationCallback($validationCallback)
    {
        if (! is_callable($validationCallback)) {
            throw new InvalidArgumentException('Invalid callback provided');
        }
        $this->credentialValidationCallback = $validationCallback;
        return $this;
    }

    /**
     * _authenticateCreateSelect() - This method creates a Laminas\Db\Sql\Select object that
     * is completely configured to be queried against the database.
     *
     * @return Sql\Select
     */
    protected function authenticateCreateSelect()
    {
        // get select
        $dbSelect = clone $this->getDbSelect();
        $dbSelect->from($this->tableName)
            ->columns([Sql\Select::SQL_STAR])
            ->where(new SqlOp($this->identityColumn, '=', $this->identity));

        return $dbSelect;
    }

    /**
     * _authenticateValidateResult() - This method attempts to validate that
     * the record in the resultset is indeed a record that matched the
     * identity provided to this adapter.
     *
     * @param  array $resultIdentity
     * @return AuthenticationResult
     */
    protected function authenticateValidateResult($resultIdentity)
    {
        try {
            $callbackResult = call_user_func(
                $this->credentialValidationCallback,
                $resultIdentity[$this->credentialColumn],
                $this->credential
            );
        } catch (Exception $e) {
            $this->authenticateResultInfo['code']       = AuthenticationResult::FAILURE_UNCATEGORIZED;
            $this->authenticateResultInfo['messages'][] = $e->getMessage();
            return $this->authenticateCreateAuthResult();
        }
        if ($callbackResult !== true) {
            $this->authenticateResultInfo['code']       = AuthenticationResult::FAILURE_CREDENTIAL_INVALID;
            $this->authenticateResultInfo['messages'][] = 'Supplied credential is invalid.';
            return $this->authenticateCreateAuthResult();
        }

        $this->resultRow = $resultIdentity;

        $this->authenticateResultInfo['code']       = AuthenticationResult::SUCCESS;
        $this->authenticateResultInfo['messages'][] = 'Authentication successful.';
        return $this->authenticateCreateAuthResult();
    }
}
