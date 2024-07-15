<?php

declare(strict_types=1);

namespace Laminas\Authentication\Adapter\Http;

use function base64_decode;
use function base64_encode;
use function chr;
use function count;
use function explode;
use function hash_equals;
use function mb_strlen;
use function mb_substr;
use function md5;
use function min;
use function pack;
use function password_verify;
use function preg_match;
use function sha1;
use function str_starts_with;
use function strpos;
use function strrev;
use function strtr;
use function substr;

/** @internal */
final class ApachePassword
{
    private const BASE64  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
    private const ALPHA64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * Verify if a clear-text password is correct given a hash value
     *
     * The username and realm are required in order to check credentials in digest format.
     */
    public static function verify(string $password, string $hash, string|null $username, string|null $realm): bool
    {
        if (str_starts_with($hash, '{SHA}')) {
            return hash_equals(
                base64_decode(substr($hash, 5), true),
                sha1($password, true)
            );
        }

        if (str_starts_with($hash, '$apr1$')) {
            $token = explode('$', $hash);
            if (count($token) !== 4) {
                throw new Exception\InvalidArgumentException(
                    'The APR1 password format is not valid'
                );
            }
            $hash2 = self::apr1Md5($password, $token[2]);

            return hash_equals($hash, $hash2);
        }

        if (preg_match('/^[a-f0-9]{32}$/i', $hash)) { // digest
            if (! $username || ! $realm) {
                throw new Exception\InvalidArgumentException(
                    'You must specify UserName and AuthName (realm) to verify the digest'
                );
            }
            $hash2 = md5($username . ':' . $realm . ':' . $password);

            return hash_equals($hash, $hash2);
        }

        return password_verify($password, $hash);
    }

    /**
     * Convert a binary string using the alphabet "./0-9A-Za-z"
     */
    private static function toAlphabet64(string $value): string
    {
        return strtr(strrev(mb_substr(base64_encode($value), 2, null, '8bit')), self::BASE64, self::ALPHA64);
    }

    /**
     * APR1 MD5 algorithm
     */
    private static function apr1Md5(string $password, string $salt): string
    {
        if (mb_strlen($salt, '8bit') !== 8) {
            throw new Exception\InvalidArgumentException(
                'The salt value for APR1 algorithm must be 8 characters long'
            );
        }
        for ($i = 0; $i < 8; $i++) {
            if (strpos(self::ALPHA64, $salt[$i]) === false) {
                throw new Exception\InvalidArgumentException(
                    'The salt value must be a string in the alphabet "./0-9A-Za-z"'
                );
            }
        }
        $len  = mb_strlen($password, '8bit');
        $text = $password . '$apr1$' . $salt;
        $bin  = pack('H32', md5($password . $salt . $password));
        for ($i = $len; $i > 0; $i -= 16) {
            $text .= mb_substr($bin, 0, min(16, $i), '8bit');
        }
        for ($i = $len; $i > 0; $i >>= 1) {
            $text .= $i & 1 ? chr(0) : $password[0];
        }
        $bin = pack('H32', md5($text));
        for ($i = 0; $i < 1000; $i++) {
            $new = $i & 1 ? $password : $bin;
            if ($i % 3) {
                $new .= $salt;
            }
            if ($i % 7) {
                $new .= $password;
            }
            $new .= $i & 1 ? $bin : $password;
            $bin  = pack('H32', md5($new));
        }
        $tmp = '';
        for ($i = 0; $i < 5; $i++) {
            $k = $i + 6;
            $j = $i + 12;
            if ($j === 16) {
                $j = 5;
            }
            $tmp = $bin[$i] . $bin[$k] . $bin[$j] . $tmp;
        }
        $tmp = chr(0) . chr(0) . $bin[11] . $tmp;

        return '$apr1$' . $salt . '$' . self::toAlphabet64($tmp);
    }
}
