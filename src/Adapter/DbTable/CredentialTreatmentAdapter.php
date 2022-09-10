<?php

declare(strict_types=1);

namespace Laminas\Authentication\Adapter\DbTable;

use Laminas\Authentication\Result as AuthenticationResult;
use Laminas\Db\Adapter\Adapter as DbAdapter;
use Laminas\Db\Sql;
use Laminas\Db\Sql\Expression as SqlExpr;
use Laminas\Db\Sql\Predicate\Operator as SqlOp;

use function strpos;

class CredentialTreatmentAdapter extends AbstractAdapter
{
    /**
     * $credentialTreatment - Treatment applied to the credential, such as MD5() or PASSWORD()
     *
     * @var string
     */
    protected $credentialTreatment;

    /**
     * __construct() - Sets configuration options
     *
     * @param string    $tableName           Optional
     * @param string    $identityColumn      Optional
     * @param string    $credentialColumn    Optional
     * @param string    $credentialTreatment Optional
     */
    public function __construct(
        DbAdapter $laminasDb,
        $tableName = null,
        $identityColumn = null,
        $credentialColumn = null,
        $credentialTreatment = null
    ) {
        parent::__construct($laminasDb, $tableName, $identityColumn, $credentialColumn);

        if (null !== $credentialTreatment) {
            $this->setCredentialTreatment($credentialTreatment);
        }
    }

    /**
     * setCredentialTreatment() - allows the developer to pass a parametrized string that is
     * used to transform or treat the input credential data.
     *
     * In many cases, passwords and other sensitive data are encrypted, hashed, encoded,
     * obscured, or otherwise treated through some function or algorithm. By specifying a
     * parametrized treatment string with this method, a developer may apply arbitrary SQL
     * upon input credential data.
     *
     * Examples:
     *
     *  'PASSWORD(?)'
     *  'MD5(?)'
     *
     * @param  string $treatment
     * @return self Provides a fluent interface
     */
    public function setCredentialTreatment($treatment)
    {
        $this->credentialTreatment = $treatment;
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
        // build credential expression
        if (empty($this->credentialTreatment) || (strpos($this->credentialTreatment, '?') === false)) {
            $this->credentialTreatment = '?';
        }

        $credentialExpression = new SqlExpr(
            '(CASE WHEN ? = ' . $this->credentialTreatment . ' THEN 1 ELSE 0 END) AS ?',
            [$this->credentialColumn, $this->credential, 'laminas_auth_credential_match'],
            [SqlExpr::TYPE_IDENTIFIER, SqlExpr::TYPE_VALUE, SqlExpr::TYPE_IDENTIFIER]
        );

        // get select
        $dbSelect = clone $this->getDbSelect();
        $dbSelect->from($this->tableName)
            ->columns(['*', $credentialExpression])
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
        /**
         * Starting with PHP 8.1.0 integers and floats in result sets will be returned using native PHP types instead
         * of strings when using emulated prepared statements. To keep the behavior consistent with older versions of
         * PHP, strict comparison of both types is used.
         *
         * @link https://www.php.net/manual/migration81.incompatible.php#migration81.incompatible.pdo.mysql
         */
        if (
            $resultIdentity['laminas_auth_credential_match'] !== '1'
            && $resultIdentity['laminas_auth_credential_match'] !== 1
        ) {
            $this->authenticateResultInfo['code']       = AuthenticationResult::FAILURE_CREDENTIAL_INVALID;
            $this->authenticateResultInfo['messages'][] = 'Supplied credential is invalid.';
            return $this->authenticateCreateAuthResult();
        }

        unset($resultIdentity['laminas_auth_credential_match']);
        $this->resultRow = $resultIdentity;

        $this->authenticateResultInfo['code']       = AuthenticationResult::SUCCESS;
        $this->authenticateResultInfo['messages'][] = 'Authentication successful.';
        return $this->authenticateCreateAuthResult();
    }
}
