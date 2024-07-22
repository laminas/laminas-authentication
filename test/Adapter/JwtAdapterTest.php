<?php
/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */
namespace LaminasTest\Authentication\Adapter;

use Laminas\Authentication\Adapter\JwtAdapter;
use PHPUnit\Framework\TestCase;
use Laminas\Authentication\AuthenticationService;

/**
 * test case.
 */
class JwtAdapterTest extends TestCase
{

    public function testAdapter(): void
    {
        // Data from https://jwt.io/
        $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';
        $subject = '1234567890';
        $adapter = new JwtAdapter($token, $subject);
        $authentication = new AuthenticationService();
        $authentication->setAdapter($adapter);
        $result = $authentication->authenticate();
        $this->assertTrue($result->isValid());
    }
}

