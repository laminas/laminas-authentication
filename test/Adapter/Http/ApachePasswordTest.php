<?php

declare(strict_types=1);

namespace LaminasTest\Authentication\Adapter\Http;

use Laminas\Authentication\Adapter\Http\ApachePassword;
use Laminas\Authentication\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function password_hash;

use const PASSWORD_BCRYPT;

/**
 * @psalm-suppress InternalMethod, InternalClass
 */
class ApachePasswordTest extends TestCase
{
    /**
     * Test vectors generated using openssl and htpasswd
     *
     * @see http://httpd.apache.org/docs/2.2/misc/password_encryptions.html
     *
     * @psalm-return array<string, array{0: string, 1: string, 2: string|null, 3: string|null}>
     */
    public static function provideTestVectors(): array
    {
        return [
            // openssl passwd -apr1 -salt z0Hhe5Lq myPassword
            'APR1 Format' => ['myPassword', '$apr1$z0Hhe5Lq$6YdJKbkrJg77Dvw2gpuSA1', null, null],
            // openssl passwd -crypt -salt z0Hhe5Lq myPassword
            'BCrypt with Fixed Salt' => ['myPassword', 'z0yXKQm465G4o', null, null],
            // htpasswd -nbs myName myPassword
            'SHA1' => ['myPassword', '{SHA}VBPuJHI7uixaa6LQGWx4s+5GKNE=', null, null],
            // md5 -s 'user:realm:myPassword'
            'Digest' => ['myPassword', 'e6104f236279e38b969bc450fa3922ca', 'user', 'realm'],
            // htpasswd -bnBC 10 '' myPassword
            'BCrypt' => ['myPassword', '$2y$10$B8bevVOqC4HychIyI/otCOdruYYw612nEX2u64XkjKgiEP4SmTNHu', null, null],
        ];
    }

    /**
     * @dataProvider provideTestVectors
     */
    public function testVerify(string $password, string $hash, string|null $username, string|null $realm): void
    {
        self::assertTrue(ApachePassword::verify($password, $hash, $username, $realm));
    }

    public function testApr1Md5InvalidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The APR1 password format is not valid');
        ApachePassword::verify('myPassword', '$apr1$z0Hhe5Lq', null, null);
    }

    public function testApr1Md5SaltTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The salt value for APR1 algorithm must be 8 characters long');
        ApachePassword::verify('myPassword', '$apr1$z0Hhe5Lq3$6YdJKbkrJg77Dvw2gpuSA1', null, null);
    }

    public function testApr1Md5SaltInvalidAlphabet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The salt value must be a string in the alphabet "./0-9A-Za-z"');
        ApachePassword::verify('myPassword', '$apr1$z0Hhe5L&$6YdJKbkrJg77Dvw2gpuSA1', null, null);
    }

    public function testCanVerifyBcryptHashes(): void
    {
        $hash = password_hash('myPassword', PASSWORD_BCRYPT);
        self::assertTrue(ApachePassword::verify('myPassword', $hash, null, null));
    }

    public function testDigestWithoutRealmIsExceptional(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You must specify UserName and AuthName (realm) to verify the digest');
        ApachePassword::verify('myPassword', 'e6104f236279e38b969bc450fa3922ca', 'user', null);
    }

    public function testDigestWithoutUsernameIsExceptional(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You must specify UserName and AuthName (realm) to verify the digest');
        ApachePassword::verify('myPassword', 'e6104f236279e38b969bc450fa3922ca', null, 'realm');
    }
}
