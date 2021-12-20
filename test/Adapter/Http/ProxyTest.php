<?php

namespace LaminasTest\Authentication\Adapter\Http;

use Laminas\Authentication\Adapter\Http;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use Laminas\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * @group      Laminas_Auth
 */
class ProxyTest extends TestCase
{
    // @codingStandardsIgnoreStart
    /**
     * Path to test files
     *
     * @var string
     */
    protected $_filesPath;

    /**
     * HTTP Basic configuration
     *
     * @var array
     */
    protected $_basicConfig;

    /**
     * HTTP Digest configuration
     *
     * @var array
     */
    protected $_digestConfig;

    /**
     * HTTP Basic Digest configuration
     *
     * @var array
     */
    protected $_bothConfig;

    /**
     * File resolver setup against with HTTP Basic auth file
     *
     * @var Http\FileResolver
     */
    protected $_basicResolver;

    /**
     * File resolver setup against with HTTP Digest auth file
     *
     * @var Http\FileResolver
     */
    protected $_digestResolver;
    // @codingStandardsIgnoreEnd

    /**
     * Sets up test configuration
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->_filesPath      = __DIR__ . '/TestAsset';
        $this->_basicResolver  = new Http\FileResolver("{$this->_filesPath}/htbasic.1");
        $this->_digestResolver = new Http\FileResolver("{$this->_filesPath}/htdigest.3");
        $this->_basicConfig    = [
            'accept_schemes' => 'basic',
            'realm'          => 'Test Realm',
            'proxy_auth'     => true
        ];
        $this->_digestConfig   = [
            'accept_schemes' => 'digest',
            'realm'          => 'Test Realm',
            'digest_domains' => '/ http://localhost/',
            'nonce_timeout'  => 300,
            'proxy_auth'     => true
        ];
        $this->_bothConfig     = [
            'accept_schemes' => 'basic digest',
            'realm'          => 'Test Realm',
            'digest_domains' => '/ http://localhost/',
            'nonce_timeout'  => 300,
            'proxy_auth'     => true
        ];
    }

    public function testBasicChallenge(): void
    {
        // Trying to authenticate without sending a Proxy-Authorization header
        // should result in a 407 reply with a Proxy-Authenticate header, and a
        // false result.

        // The expected Basic Proxy-Authenticate header value
        $basic = [
            'type'   => 'Basic ',
            'realm'  => 'realm="' . $this->_bothConfig['realm'] . '"',
        ];

        $data = $this->_doAuth('', 'basic');
        $this->checkUnauthorized($data, $basic);
    }

    public function testDigestChallenge(): void
    {
        // Trying to authenticate without sending a Proxy-Authorization header
        // should result in a 407 reply with a Proxy-Authenticate header, and a
        // false result.

        // The expected Digest Proxy-Authenticate header value
        $digest = $this->digestChallenge();

        $data = $this->_doAuth('', 'digest');
        $this->checkUnauthorized($data, $digest);
    }

    public function testBothChallenges(): void
    {
        // Trying to authenticate without sending a Proxy-Authorization header
        // should result in a 407 reply with at least one Proxy-Authenticate
        // header, and a false result.

        $data = $this->_doAuth('', 'both');
        extract($data); // $result, $status, $headers

        // The expected Proxy-Authenticate header values
        $basic  = 'Basic realm="' . $this->_bothConfig['realm'] . '"';
        $digest = $this->digestChallenge();

        // Make sure the result is false
        $this->assertInstanceOf('Laminas\\Authentication\\Result', $result);
        $this->assertFalse($result->isValid());

        // Verify the status code and the presence of both challenges
        $this->assertEquals(407, $status);
        $this->assertTrue($headers->has('Proxy-Authenticate'));
        $authHeader = $headers->get('Proxy-Authenticate');
        $this->assertEquals(2, count($authHeader), var_export($authHeader, 1));

        // Check to see if the expected challenges match the actual
        $basicFound = $digestFound = false;
        foreach ($authHeader as $header) {
            $value = $header->getFieldValue();
            if (preg_match('/^Basic/', $value)) {
                $basicFound = true;
            }
            if (preg_match('/^Digest/', $value)) {
                $digestFound = true;
            }
        }
        $this->assertTrue($basicFound);
        $this->assertTrue($digestFound);
    }

    public function testBasicAuthValidCreds(): void
    {
        // Attempt Basic Authentication with a valid username and password

        $data = $this->_doAuth('Basic ' . base64_encode('Bryce:ThisIsNotMyPassword'), 'basic');
        $this->checkOK($data);
    }

    public function testBasicAuthBadCreds(): void
    {
        // Ensure that credentials containing invalid characters are treated as
        // a bad username or password.

        // The expected Basic WWW-Authenticate header value
        $basic = [
            'type'   => 'Basic ',
            'realm'  => 'realm="' . $this->_basicConfig['realm'] . '"',
        ];

        $data = $this->_doAuth('Basic ' . base64_encode("Bad\tChars:In:Creds"), 'basic');
        $this->checkUnauthorized($data, $basic);
    }

    public function testBasicAuthBadUser(): void
    {
        // Attempt Basic Authentication with a bad username and password

        // The expected Basic Proxy-Authenticate header value
        $basic = [
            'type'   => 'Basic ',
            'realm'  => 'realm="' . $this->_basicConfig['realm'] . '"',
        ];

        $data = $this->_doAuth('Basic ' . base64_encode('Nobody:NotValid'), 'basic');
        $this->checkUnauthorized($data, $basic);
    }

    public function testBasicAuthBadPassword(): void
    {
        // Attempt Basic Authentication with a valid username, but invalid
        // password

        // The expected Basic WWW-Authenticate header value
        $basic = [
            'type'   => 'Basic ',
            'realm'  => 'realm="' . $this->_basicConfig['realm'] . '"',
        ];

        $data = $this->_doAuth('Basic ' . base64_encode('Bryce:Invalid'), 'basic');
        $this->checkUnauthorized($data, $basic);
    }

    public function testDigestAuthValidCreds(): void
    {
        // Attempt Digest Authentication with a valid username and password

        $data = $this->_doAuth($this->digestReply('Bryce', 'ThisIsNotMyPassword'), 'digest');
        $this->checkOK($data);
    }

    public function testDigestAuthDefaultAlgo(): void
    {
        // If the client omits the aglorithm argument, it should default to MD5,
        // and work just as above

        $cauth = $this->digestReply('Bryce', 'ThisIsNotMyPassword');
        $cauth = preg_replace('/algorithm="MD5", /', '', $cauth);

        $data = $this->_doAuth($cauth, 'digest');
        $this->checkOK($data);
    }

    public function testDigestAuthQuotedNC(): void
    {
        // The nonce count isn't supposed to be quoted, but apparently some
        // clients do anyway.

        $cauth = $this->digestReply('Bryce', 'ThisIsNotMyPassword');
        $cauth = preg_replace('/nc=00000001/', 'nc="00000001"', $cauth);

        $data = $this->_doAuth($cauth, 'digest');
        $this->checkOK($data);
    }

    public function testDigestAuthBadCreds(): void
    {
        // Attempt Digest Authentication with a bad username and password

        // The expected Digest Proxy-Authenticate header value
        $digest = $this->digestChallenge();

        $data = $this->_doAuth($this->digestReply('Nobody', 'NotValid'), 'digest');
        $this->checkUnauthorized($data, $digest);
    }

    public function testDigestTampered(): void
    {
        // Create the tampered header value
        $tampered = $this->digestReply('Bryce', 'ThisIsNotMyPassword');
        $tampered = preg_replace(
            '/ nonce="[a-fA-F0-9]{32}", /',
            ' nonce="' . str_repeat('0', 32).'", ',
            $tampered
        );

        // The expected Digest Proxy-Authenticate header value
        $digest = $this->digestChallenge();

        $data = $this->_doAuth($tampered, 'digest');
        $this->checkUnauthorized($data, $digest);
    }

    public function testBadSchemeRequest(): void
    {
        // Sending a request for an invalid authentication scheme should result
        // in a 400 Bad Request response.

        $data = $this->_doAuth('Invalid ' . base64_encode('Nobody:NotValid'), 'basic');
        $this->checkBadRequest($data);
    }

    public function testBadDigestRequest(): void
    {
        // If any of the individual parts of the Digest Proxy-Authorization header
        // are bad, it results in a 400 Bad Request. But that's a lot of
        // possibilities, so we're just going to pick one for now.
        $bad = $this->digestReply('Bryce', 'ThisIsNotMyPassword');
        $bad = preg_replace(
            '/realm="([^"]+)"/',  // cut out the realm
            '',
            $bad
        );

        $data = $this->_doAuth($bad, 'digest');
        $this->checkBadRequest($data);
    }

    /**
     * Acts like a client sending the given Authenticate header value.
     *
     * @param  string $clientHeader Authenticate header value
     * @param  string $scheme       Which authentication scheme to use
     * @return array Containing the result, the response headers, and the status
     */
    // @codingStandardsIgnoreStart
    public function _doAuth($clientHeader, $scheme)
    {
        // @codingStandardsIgnoreEnd
        // Set up stub request and response objects
        $response = new Response;
        $response->setStatusCode(200);

        $headers  = new Headers();
        $headers->addHeaderLine('Proxy-Authorization', $clientHeader);
        $headers->addHeaderLine('User-Agent', 'PHPUnit');

        $request  = new Request();
        $request->setUri('http://localhost/');
        $request->setMethod('GET');
        $request->setHeaders($headers);

        // Select an Authentication scheme
        switch ($scheme) {
            case 'basic':
                $use = $this->_basicConfig;
                break;
            case 'digest':
                $use = $this->_digestConfig;
                break;
            case 'both':
            default:
                $use = $this->_bothConfig;
        }

        // Create the HTTP Auth adapter
        $a = new \Laminas\Authentication\Adapter\Http($use);
        $a->setBasicResolver($this->_basicResolver);
        $a->setDigestResolver($this->_digestResolver);

        // Send the authentication request
        $a->setRequest($request);
        $a->setResponse($response);
        $result = $a->authenticate();

        $return = [
            'result'  => $result,
            'status'  => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
        ];
        return $return;
    }

    /**
     * Constructs a local version of the digest challenge we expect to receive
     *
     * @return string[]
     *
     * @psalm-return array{type: string, realm: string, domain: string}
     */
    protected function digestChallenge(): array
    {
        return [
            'type'   => 'Digest ',
            'realm'  => 'realm="' . $this->_digestConfig['realm'] . '"',
            'domain' => 'domain="' . $this->_bothConfig['digest_domains'] . '"',
        ];
    }

    /**
     * Constructs a client digest Proxy-Authorization header
     *
     * @param  string $user
     * @param  string $pass
     * @return string
     */
    protected function digestReply($user, $pass)
    {
        $nc       = '00000001';
        $timeout  = ceil(time() / 300) * 300;
        $nonce    = md5($timeout . ':PHPUnit:Laminas\\Authentication\\Adapter\\Http');
        $opaque   = md5('Opaque Data:Laminas\\Authentication\\Adapter\\Http');
        $cnonce   = md5('cnonce');
        $response = md5(md5($user . ':' . $this->_digestConfig['realm'] . ':' . $pass) . ":$nonce:$nc:$cnonce:auth:"
                  . md5('GET:/'));
        $cauth = 'Digest '
               . 'username="Bryce", '
               . 'realm="' . $this->_digestConfig['realm'] . '", '
               . 'nonce="' . $nonce . '", '
               . 'uri="/", '
               . 'response="' . $response . '", '
               . 'algorithm="MD5", '
               . 'cnonce="' . $cnonce . '", '
               . 'opaque="' . $opaque . '", '
               . 'qop="auth", '
               . 'nc=' . $nc;

        return $cauth;
    }

    /**
     * Checks for an expected 407 Proxy-Unauthorized response
     *
     * @param  array  $data     Authentication results
     * @param  array  $expected Expected Proxy-Authenticate header value
     * @return void
     *
     * @psalm-param array<string, string> $expected
     */
    protected function checkUnauthorized($data, $expected)
    {
        extract($data); // $result, $status, $headers

        // Make sure the result is false
        $this->assertInstanceOf('Laminas\\Authentication\\Result', $result);
        $this->assertFalse($result->isValid());

        // Verify the status code and the presence of the challenge
        $this->assertEquals(407, $status);
        $this->assertTrue($headers->has('Proxy-Authenticate'));

        // Check to see if the expected challenge matches the actual
        $headers = $headers->get('Proxy-Authenticate');
        $this->assertInstanceOf('ArrayIterator', $headers);
        $this->assertEquals(1, count($headers));
        $header = $headers[0]->getFieldValue();
        $this->assertStringContainsString($expected['type'], $header, $header);
        $this->assertStringContainsString($expected['realm'], $header, $header);
        if (isset($expected['domain'])) {
            $this->assertStringContainsString($expected['domain'], $header, $header);
            $this->assertStringContainsString('algorithm="MD5"', $header, $header);
            $this->assertStringContainsString('qop="auth"', $header, $header);
            $this->assertMatchesRegularExpression('/nonce="[a-fA-F0-9]{32}"/', $header, $header);
            $this->assertMatchesRegularExpression('/opaque="[a-fA-F0-9]{32}"/', $header, $header);
        }
    }

    /**
     * Checks for an expected 200 OK response
     *
     * @param  array $data Authentication results
     * @return void
     */
    protected function checkOK($data)
    {
        extract($data); // $result, $status, $headers

        // Make sure the result is true
        $this->assertInstanceOf('Laminas\\Authentication\\Result', $result);
        $this->assertTrue($result->isValid(), var_export($result->getMessages(), 1));

        // Verify we got a 200 response
        $this->assertEquals(200, $status);
    }

    /**
     * Checks for an expected 400 Bad Request response
     *
     * @param  array $data Authentication results
     * @return void
     */
    protected function checkBadRequest($data)
    {
        extract($data); // $result, $status, $headers

        // Make sure the result is false
        $this->assertInstanceOf('Laminas\\Authentication\\Result', $result);
        $this->assertFalse($result->isValid());

        // Make sure it set the right HTTP code
        $this->assertEquals(400, $status);
    }
}
