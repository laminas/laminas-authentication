<?php

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Authentication\Adapter\Ldap;

use Laminas\Authentication;
use Laminas\Authentication\Adapter;
use Laminas\Ldap;

/**
 * @group      Laminas_Auth
 */
class OnlineTest extends \PHPUnit_Framework_TestCase
{
    /**
     * LDAP connection options
     *
     * @var array
     */
    protected $options = array();

    /**
     * @var array
     */
    protected $names = array();

    public function setUp()
    {
        if (!constant('TESTS_LAMINAS_AUTH_ADAPTER_LDAP_ONLINE_ENABLED')) {
            $this->markTestSkipped('LDAP online tests are not enabled');
        }
        $this->options = array(
            'host'     => TESTS_LAMINAS_LDAP_HOST,
            'username' => TESTS_LAMINAS_LDAP_USERNAME,
            'password' => TESTS_LAMINAS_LDAP_PASSWORD,
            'baseDn'   => TESTS_LAMINAS_LDAP_BASE_DN,
        );
        if (defined('TESTS_LAMINAS_LDAP_PORT')) {
            $this->options['port'] = TESTS_LAMINAS_LDAP_PORT;
        }
        if (defined('TESTS_LAMINAS_LDAP_USE_START_TLS')) {
            $this->options['useStartTls'] = TESTS_LAMINAS_LDAP_USE_START_TLS;
        }
        if (defined('TESTS_LAMINAS_LDAP_USE_SSL')) {
            $this->options['useSsl'] = TESTS_LAMINAS_LDAP_USE_SSL;
        }
        if (defined('TESTS_LAMINAS_LDAP_BIND_REQUIRES_DN')) {
            $this->options['bindRequiresDn'] = TESTS_LAMINAS_LDAP_BIND_REQUIRES_DN;
        }
        if (defined('TESTS_LAMINAS_LDAP_ACCOUNT_FILTER_FORMAT')) {
            $this->options['accountFilterFormat'] = TESTS_LAMINAS_LDAP_ACCOUNT_FILTER_FORMAT;
        }
        if (defined('TESTS_LAMINAS_LDAP_ACCOUNT_DOMAIN_NAME')) {
            $this->options['accountDomainName'] = TESTS_LAMINAS_LDAP_ACCOUNT_DOMAIN_NAME;
        }
        if (defined('TESTS_LAMINAS_LDAP_ACCOUNT_DOMAIN_NAME_SHORT')) {
            $this->options['accountDomainNameShort'] = TESTS_LAMINAS_LDAP_ACCOUNT_DOMAIN_NAME_SHORT;
        }

        if (defined('TESTS_LAMINAS_LDAP_ALT_USERNAME')) {
            $this->names[Ldap\Ldap::ACCTNAME_FORM_USERNAME] = TESTS_LAMINAS_LDAP_ALT_USERNAME;
            if (defined('TESTS_LAMINAS_LDAP_ACCOUNT_DOMAIN_NAME')) {
                $this->names[Ldap\Ldap::ACCTNAME_FORM_PRINCIPAL] =
                    TESTS_LAMINAS_LDAP_ALT_USERNAME . '@' . TESTS_LAMINAS_LDAP_ACCOUNT_DOMAIN_NAME;
            }
            if (defined('TESTS_LAMINAS_LDAP_ACCOUNT_DOMAIN_NAME_SHORT')) {
                $this->names[Ldap\Ldap::ACCTNAME_FORM_BACKSLASH] =
                    TESTS_LAMINAS_LDAP_ACCOUNT_DOMAIN_NAME_SHORT . '\\' . TESTS_LAMINAS_LDAP_ALT_USERNAME;
            }
        }
    }

    public function testSimpleAuth()
    {
        $adapter = new Adapter\Ldap(
            array($this->options),
            TESTS_LAMINAS_LDAP_ALT_USERNAME,
            TESTS_LAMINAS_LDAP_ALT_PASSWORD
        );

        $result = $adapter->authenticate();

        $this->assertInstanceOf('Laminas\Authentication\Result', $result);
        $this->assertTrue($result->isValid());
        $this->assertEquals(Authentication\Result::SUCCESS, $result->getCode());
    }

    public function testCanonAuth()
    {
        /* This test authenticates with each of the account name forms
         * (uname, uname@example.com, EXAMPLE\uname) AND it does so with
         * the accountCanonicalForm set to each of the account name forms
         * (e.g. authenticate with uname@example.com but getIdentity() returns
         * EXAMPLE\uname). A total of 9 authentications are performed.
         */
        foreach ($this->names as $form => $formName) {
            $options = $this->options;
            $options['accountCanonicalForm'] = $form;
            $adapter = new Adapter\Ldap(array($options));
            $adapter->setPassword(TESTS_LAMINAS_LDAP_ALT_PASSWORD);
            foreach ($this->names as $username) {
                $adapter->setUsername($username);
                $result = $adapter->authenticate();
                $this->assertInstanceOf('Laminas\Authentication\Result', $result);
                $this->assertTrue($result->isValid());
                $this->assertEquals(Authentication\Result::SUCCESS, $result->getCode());
                $this->assertEquals($formName, $result->getIdentity());
            }
        }
    }

    public function testInvalidPassAuth()
    {
        $adapter = new Adapter\Ldap(
            array($this->options),
            TESTS_LAMINAS_LDAP_ALT_USERNAME,
            'invalid'
        );

        $result = $adapter->authenticate();
        $this->assertInstanceOf('Laminas\Authentication\Result', $result);
        $this->assertFalse($result->isValid());
        $this->assertEquals(Authentication\Result::FAILURE_CREDENTIAL_INVALID, $result->getCode());
    }

    public function testInvalidUserAuth()
    {
        $adapter = new Adapter\Ldap(
            array($this->options),
            'invalid',
            'doesntmatter'
        );

        $result = $adapter->authenticate();
        $this->assertInstanceOf('Laminas\Authentication\Result', $result);
        $this->assertFalse($result->isValid());
        $this->assertTrue(
            $result->getCode() == Authentication\Result::FAILURE_IDENTITY_NOT_FOUND ||
            $result->getCode() == Authentication\Result::FAILURE_CREDENTIAL_INVALID
        );
    }

    public function testMismatchDomainAuth()
    {
        $adapter = new Adapter\Ldap(
            array($this->options),
            'EXAMPLE\\doesntmatter',
            'doesntmatter'
        );

        $result = $adapter->authenticate();
        $this->assertInstanceOf('Laminas\Authentication\Result', $result);
        $this->assertFalse($result->isValid());
        $this->assertThat($result->getCode(), $this->lessThanOrEqual(Authentication\Result::FAILURE));
        $messages = $result->getMessages();
        $this->assertContains('not found', $messages[0]);
    }

    public function testAccountObjectRetrieval()
    {
        $adapter = new Adapter\Ldap(
            array($this->options),
            TESTS_LAMINAS_LDAP_ALT_USERNAME,
            TESTS_LAMINAS_LDAP_ALT_PASSWORD
        );

        $result = $adapter->authenticate();
        $account = $adapter->getAccountObject();

        //$this->assertTrue($result->isValid());
        $this->assertInternalType('object', $account);
        $this->assertEquals(TESTS_LAMINAS_LDAP_ALT_DN, $account->dn);
    }

    public function testAccountObjectRetrievalWithOmittedAttributes()
    {
        $adapter = new Adapter\Ldap(
            array($this->options),
            TESTS_LAMINAS_LDAP_ALT_USERNAME,
            TESTS_LAMINAS_LDAP_ALT_PASSWORD
        );

        $result = $adapter->authenticate();
        $account = $adapter->getAccountObject(array(), array('userPassword'));

        $this->assertInternalType('object', $account);
        $this->assertFalse(isset($account->userpassword));
    }
}
