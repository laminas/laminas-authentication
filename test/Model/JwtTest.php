<?php
/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */
use PHPUnit\Framework\TestCase;
use Laminas\Authentication\Jwt;

/**
 * test case.
 */
class JwtTest extends TestCase
{

    public function testBearerToken(): void
    {
        $jwt = new Jwt([
            'RS256',
            'sha256'
        ], 'JWT', 'newbraveworld.com', 'PT2H');

        $bearerToken = $jwt->getBearerToken('foouser', 'baapassword');

        $this->assertTrue(is_string($bearerToken));
        $this->assertNotEmpty($bearerToken);

        $payload = Jwt::getPayload($bearerToken);

        $this->assertTrue(is_object($payload));

        $this->assertFalse(Jwt::expired($bearerToken));
    }
}