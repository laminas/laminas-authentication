<?php

namespace LaminasTest\Authentication\Validator;

use Laminas\Authentication\Adapter\ValidatableAdapterInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Exception;
use Laminas\Authentication\Result as AuthenticationResult;
use Laminas\Authentication\Validator\Authentication as AuthenticationValidator;
use LaminasTest\Authentication as AuthTest;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class AuthenticationTest extends TestCase
{
    /**
     * @var AuthenticationValidator
     */
    protected $validator;

    /**
     * @var AuthenticationService
     */
    protected $authService;

    /**
     * @var ValidatableAdapterInterface
     */
    protected $authAdapter;

    public function setUp(): void
    {
        $this->validator = new AuthenticationValidator();
        $this->authService = new AuthenticationService();
        $this->authAdapter = new AuthTest\TestAsset\ValidatableAdapter();
    }

    public function testOptions(): void
    {
        $auth = new AuthenticationValidator([
            'adapter' => $this->authAdapter,
            'service' => $this->authService,
            'identity' => 'username',
            'credential' => 'password',
        ]);
        $this->assertSame($auth->getAdapter(), $this->authAdapter);
        $this->assertSame($auth->getService(), $this->authService);
        $this->assertSame($auth->getIdentity(), 'username');
        $this->assertSame($auth->getCredential(), 'password');
    }

    public function testConstructorOptionCodeMapOverridesDefaultMap(): void
    {
        $authAdapter = new AuthTest\TestAsset\ValidatableAdapter(AuthenticationResult::FAILURE_UNCATEGORIZED);
        $auth = new AuthenticationValidator([
            'adapter' => $authAdapter,
            'service' => $this->authService,
            'identity' => 'username',
            'credential' => 'password',
            'code_map' => [
                AuthenticationResult::FAILURE_UNCATEGORIZED => AuthenticationValidator::IDENTITY_NOT_FOUND,
            ]
        ]);
        $this->assertFalse($auth->isValid());
        $this->assertArrayHasKey(
            AuthenticationValidator::IDENTITY_NOT_FOUND,
            $auth->getMessages(),
            print_r($auth->getMessages(), true)
        );
    }

    public function testConstructorOptionCodeMapUsesDefaultMapForOmittedCodes(): void
    {
        $authAdapter = new AuthTest\TestAsset\ValidatableAdapter(AuthenticationResult::FAILURE_IDENTITY_AMBIGUOUS);
        $auth = new AuthenticationValidator([
            'adapter' => $authAdapter,
            'service' => $this->authService,
            'identity' => 'username',
            'credential' => 'password',
            'code_map' => [
                AuthenticationResult::FAILURE_UNCATEGORIZED => AuthenticationValidator::IDENTITY_NOT_FOUND,
            ]
        ]);
        $this->assertFalse($auth->isValid());
        $this->assertArrayHasKey(
            AuthenticationValidator::IDENTITY_AMBIGUOUS,
            $auth->getMessages(),
            print_r($auth->getMessages(), true)
        );
    }

    public function testCodeMapAllowsToSpecifyCustomCodes(): void
    {
        $authAdapter = new AuthTest\TestAsset\ValidatableAdapter(-999);
        $auth = new AuthenticationValidator([
            'adapter' => $authAdapter,
            'service' => $this->authService,
            'identity' => 'username',
            'credential' => 'password',
            'code_map' => [
                -999 => AuthenticationValidator::IDENTITY_NOT_FOUND,
            ]
        ]);
        $this->assertFalse($auth->isValid());
        $this->assertArrayHasKey(
            AuthenticationValidator::IDENTITY_NOT_FOUND,
            $auth->getMessages(),
            print_r($auth->getMessages(), true)
        );
    }

    public function testCodeMapAllowsToAddCustomMessageTemplates(): void
    {
        $auth = new AuthenticationValidator([
            'code_map' => [
                -999 => 'custom_error',
            ]
        ]);
        $templates = $auth->getMessageTemplates();
        $this->assertArrayHasKey(
            'custom_error',
            $templates,
            print_r($templates, true)
        );
    }

    /**
     * @depends testCodeMapAllowsToAddCustomMessageTemplates
     *
     * @return void
     */
    public function testCodeMapCustomMessageTemplateValueDefaultsToGeneralMessageTemplate(): void
    {
        $auth = new AuthenticationValidator([
            'code_map' => [
                -999 => 'custom_error',
            ]
        ]);
        $templates = $auth->getMessageTemplates();
        $this->assertEquals($templates['general'], $templates['custom_error']);
    }

    /**
     * @depends testCodeMapAllowsToAddCustomMessageTemplates
     *
     * @return void
     */
    public function testCustomMessageTemplateValueCanBeProvidedAsOption(): void
    {
        $auth = new AuthenticationValidator([
            'code_map' => [
                -999 => 'custom_error',
            ],
            'messages' => [
                'custom_error' => 'Custom Error'
            ]

        ]);
        $templates = $auth->getMessageTemplates();
        $this->assertEquals('Custom Error', $templates['custom_error']);
    }

    public function testCodeMapOptionRequiresMessageKeyToBeString(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Message key in code_map option must be a non-empty string');
        new AuthenticationValidator([
            'code_map' => [
                -999 => [],
            ]
        ]);
    }

    public function testSetters(): void
    {
        $this->validator->setAdapter($this->authAdapter);
        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->setCredential('credential');
        $this->assertSame($this->validator->getAdapter(), $this->authAdapter);
        $this->assertSame($this->validator->getService(), $this->authService);
        $this->assertSame($this->validator->getIdentity(), 'username');
        $this->assertSame($this->validator->getCredential(), 'credential');
    }

    public function testNoIdentityThrowsRuntimeException(): void
    {
        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('Identity must be set prior to validation');
        $this->validator->isValid('password');
    }

    public function testNoAdapterThrowsRuntimeException(): void
    {
        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('Adapter must be set prior to validation');
        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->isValid('password');
    }

    public function testNoServiceThrowsRuntimeException(): void
    {
        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('AuthenticationService must be set prior to validation');
        $this->validator->setAdapter($this->authAdapter);
        $this->validator->setIdentity('username');
        $this->validator->isValid('password');
    }

    public function testEqualsMessageTemplates(): void
    {
        $r = new ReflectionProperty($this->validator, 'messageTemplates');
        $r->setAccessible(true);
        $this->assertEquals($this->validator->getOption('messageTemplates'), $r->getValue($this->validator));
    }

    public function testWithoutContext(): void
    {
        $this->validator->setAdapter($this->authAdapter);
        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->setCredential('credential');

        $this->assertEquals('username', $this->validator->getIdentity());
        $this->assertEquals('credential', $this->validator->getCredential());
        $this->assertTrue($this->validator->isValid());
    }

    public function testWithContext(): void
    {
        $this->validator->setAdapter($this->authAdapter);
        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->isValid('password', [
            'username' => 'myusername',
            'password' => 'mypassword',
        ]);
        $adapter = $this->validator->getAdapter();
        $this->assertInstanceOf(ValidatableAdapterInterface::class, $adapter);
        $this->assertEquals('myusername', $adapter->getIdentity());
        $this->assertEquals('mypassword', $adapter->getCredential());
    }

    /**
     * @return (bool|int|string[])[][]
     *
     * @psalm-return array<string, array{
     *     0: int,
     *     1: bool,
     *     2: array<string, string>
     * }>
     */
    public function errorMessagesProvider(): array
    {
        return [
            'failure' => [
                AuthenticationResult::FAILURE,
                false,
                [AuthenticationValidator::GENERAL => 'Authentication failed'],
            ],
            'identity-not-found' => [
                AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND,
                false,
                [AuthenticationValidator::IDENTITY_NOT_FOUND => 'Invalid identity'],
            ],
            'identity-ambiguous' => [
                AuthenticationResult::FAILURE_IDENTITY_AMBIGUOUS,
                false,
                [AuthenticationValidator::IDENTITY_AMBIGUOUS => 'Identity is ambiguous'],
            ],
            'credential-invalid' => [
                AuthenticationResult::FAILURE_CREDENTIAL_INVALID,
                false,
                [AuthenticationValidator::CREDENTIAL_INVALID => 'Invalid password'],
            ],
            'uncategorized' => [
                AuthenticationResult::FAILURE_UNCATEGORIZED,
                false,
                [AuthenticationValidator::UNCATEGORIZED => 'Authentication failed'],
            ],
            'success' => [
                AuthenticationResult::SUCCESS,
                true,
                [],
            ],
        ];
    }

    /**
     * @dataProvider errorMessagesProvider
     *
     * @param int   $code
     * @param bool  $valid
     * @param array $messages
     *
     * @return void
     */
    public function testErrorMessages($code, $valid, $messages): void
    {
        $adapter = new AuthTest\TestAsset\ValidatableAdapter($code);

        $this->validator->setAdapter($adapter);
        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->setCredential('credential');

        $this->assertEquals($valid, $this->validator->isValid());
        $this->assertEquals($messages, $this->validator->getMessages());
    }

    /**
     * Test using Authentication Service's adapter
     *
     * @return void
     */
    public function testUsingAdapterFromService(): void
    {
        $this->authService->setAdapter($this->authAdapter);

        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->isValid('password');

        $this->assertEquals('username', $this->validator->getIdentity());
        $this->assertEquals('password', $this->validator->getCredential());
        $this->assertEquals('username', $this->authAdapter->getIdentity());
        $this->assertEquals('password', $this->authAdapter->getCredential());
        $this->assertNull($this->validator->getAdapter());
        $this->assertTrue($this->validator->isValid());
    }

    /**
     * Ensures that isValid() throws an exception when Authentication Service's
     * adapter is not an instance of ValidatableAdapterInterface
     *
     * @return void
     */
    public function testUsingNonValidatableAdapterFromServiceThrowsRuntimeException(): void
    {
        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            '%s; %s given',
            ValidatableAdapterInterface::class,
            AuthTest\TestAsset\SuccessAdapter::class
        ));

        $adapter = new AuthTest\TestAsset\SuccessAdapter();
        $this->authService->setAdapter($adapter);

        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->isValid('password');
    }
}
