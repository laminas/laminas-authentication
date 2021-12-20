<?php

declare(strict_types=1);

namespace LaminasTest\Authentication\Adapter\Ldap;

use Laminas\Authentication;
use Laminas\Authentication\Adapter;
use Laminas\Authentication\Result;
use Laminas\Ldap;
use PHPUnit\Framework\TestCase;

use function getenv;

/**
 * @group      Laminas_Auth
 */
class OnlineTest extends TestCase
{
    /**
     * LDAP connection options
     *
     * @var array
     */
    protected $options = [];

    /** @var array */
    protected $names = [];

    public function setUp(): void
    {
        if (! getenv('TESTS_LAMINAS_AUTH_ADAPTER_LDAP_ONLINE_ENABLED')) {
            $this->markTestSkipped('LDAP online tests are not enabled');
        }
        $this->options = [
            'host'     => getenv('TESTS_LAMINAS_LDAP_HOST'),
            'username' => getenv('TESTS_LAMINAS_LDAP_USERNAME'),
            'password' => getenv('TESTS_LAMINAS_LDAP_PASSWORD'),
            'baseDn'   => getenv('TESTS_LAMINAS_LDAP_BASE_DN'),
        ];
        if (getenv('TESTS_LAMINAS_LDAP_PORT')) {
            $this->options['port'] = getenv('TESTS_LAMINAS_LDAP_PORT');
        }
        if (getenv('TESTS_LAMINAS_LDAP_USE_START_TLS')) {
            $this->options['useStartTls'] = getenv('TESTS_LAMINAS_LDAP_USE_START_TLS');
        }
        if (getenv('TESTS_LAMINAS_LDAP_USE_SSL')) {
            $this->options['useSsl'] = getenv('TESTS_LAMINAS_LDAP_USE_SSL');
        }
        if (getenv('TESTS_LAMINAS_LDAP_BIND_REQUIRES_DN')) {
            $this->options['bindRequiresDn'] = getenv('TESTS_LAMINAS_LDAP_BIND_REQUIRES_DN');
        }
        if (getenv('TESTS_LAMINAS_LDAP_ACCOUNT_FILTER_FORMAT')) {
            $this->options['accountFilterFormat'] = getenv('TESTS_LAMINAS_LDAP_ACCOUNT_FILTER_FORMAT');
        }
        if (getenv('TESTS_LAMINAS_LDAP_ACCOUNT_DOMAIN_NAME')) {
            $this->options['accountDomainName'] = getenv('TESTS_LAMINAS_LDAP_ACCOUNT_DOMAIN_NAME');
        }
        if (getenv('TESTS_LAMINAS_LDAP_ACCOUNT_DOMAIN_NAME_SHORT')) {
            $this->options['accountDomainNameShort'] = getenv('TESTS_LAMINAS_LDAP_ACCOUNT_DOMAIN_NAME_SHORT');
        }

        if (getenv('TESTS_LAMINAS_LDAP_ALT_USERNAME')) {
            $this->names[Ldap\Ldap::ACCTNAME_FORM_USERNAME] = getenv('TESTS_LAMINAS_LDAP_ALT_USERNAME');
            if (getenv('TESTS_LAMINAS_LDAP_ACCOUNT_DOMAIN_NAME')) {
                $this->names[Ldap\Ldap::ACCTNAME_FORM_PRINCIPAL] =
                    getenv('TESTS_LAMINAS_LDAP_ALT_USERNAME') . '@' . getenv('TESTS_LAMINAS_LDAP_ACCOUNT_DOMAIN_NAME');
            }
            if (getenv('TESTS_LAMINAS_LDAP_ACCOUNT_DOMAIN_NAME_SHORT')) {
                $this->names[Ldap\Ldap::ACCTNAME_FORM_BACKSLASH] =
                    getenv('TESTS_LAMINAS_LDAP_ACCOUNT_DOMAIN_NAME_SHORT')
                    . '\\' . getenv('TESTS_LAMINAS_LDAP_ALT_USERNAME');
            }
        }
    }

    public function testSimpleAuth(): void
    {
        $adapter = new Adapter\Ldap(
            [$this->options],
            getenv('TESTS_LAMINAS_LDAP_ALT_USERNAME'),
            getenv('TESTS_LAMINAS_LDAP_ALT_PASSWORD')
        );

        $result = $adapter->authenticate();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isValid());
        $this->assertEquals(Authentication\Result::SUCCESS, $result->getCode());
    }

    public function testCanonAuth(): void
    {
        /* This test authenticates with each of the account name forms
         * (uname, uname@example.com, EXAMPLE\uname) AND it does so with
         * the accountCanonicalForm set to each of the account name forms
         * (e.g. authenticate with uname@example.com but getIdentity() returns
         * EXAMPLE\uname). A total of 9 authentications are performed.
         */
        foreach ($this->names as $form => $formName) {
            $options                         = $this->options;
            $options['accountCanonicalForm'] = $form;
            $adapter                         = new Adapter\Ldap([$options]);
            $adapter->setPassword(getenv('TESTS_LAMINAS_LDAP_ALT_PASSWORD'));
            foreach ($this->names as $username) {
                $adapter->setUsername($username);
                $result = $adapter->authenticate();
                $this->assertInstanceOf(Result::class, $result);
                $this->assertTrue($result->isValid());
                $this->assertEquals(Authentication\Result::SUCCESS, $result->getCode());
                $this->assertEquals($formName, $result->getIdentity());
            }
        }
    }

    public function testInvalidPassAuth(): void
    {
        $adapter = new Adapter\Ldap(
            [$this->options],
            getenv('TESTS_LAMINAS_LDAP_ALT_USERNAME'),
            'invalid'
        );

        $result = $adapter->authenticate();
        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isValid());
        $this->assertEquals(Authentication\Result::FAILURE_CREDENTIAL_INVALID, $result->getCode());
    }

    public function testInvalidUserAuth(): void
    {
        $adapter = new Adapter\Ldap(
            [$this->options],
            'invalid',
            'doesntmatter'
        );

        $result = $adapter->authenticate();
        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isValid());
        $this->assertTrue(
            $result->getCode() === Authentication\Result::FAILURE_IDENTITY_NOT_FOUND ||
            $result->getCode() === Authentication\Result::FAILURE_CREDENTIAL_INVALID
        );
    }

    public function testMismatchDomainAuth(): void
    {
        $adapter = new Adapter\Ldap(
            [$this->options],
            'EXAMPLE\\doesntmatter',
            'doesntmatter'
        );

        $result = $adapter->authenticate();
        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isValid());
        $this->assertThat($result->getCode(), $this->lessThanOrEqual(Authentication\Result::FAILURE));
        $messages = $result->getMessages();
        $this->assertStringContainsString('not found', $messages[0]);
    }

    public function testAccountObjectRetrieval(): void
    {
        $adapter = new Adapter\Ldap(
            [$this->options],
            getenv('TESTS_LAMINAS_LDAP_ALT_USERNAME'),
            getenv('TESTS_LAMINAS_LDAP_ALT_PASSWORD')
        );

        $adapter->authenticate();
        $account = $adapter->getAccountObject();

        //$this->assertTrue($result->isValid());
        $this->assertIsObject($account);
        $this->assertEquals(getenv('TESTS_LAMINAS_LDAP_ALT_DN'), $account->dn);
    }

    public function testAccountObjectRetrievalWithOmittedAttributes(): void
    {
        $adapter = new Adapter\Ldap(
            [$this->options],
            getenv('TESTS_LAMINAS_LDAP_ALT_USERNAME'),
            getenv('TESTS_LAMINAS_LDAP_ALT_PASSWORD')
        );

        $adapter->authenticate();
        $account = $adapter->getAccountObject([], ['userPassword']);

        $this->assertIsObject($account);
        $this->assertFalse(isset($account->userpassword));
    }
}
