# Digest Authentication

[Digest authentication](http://en.wikipedia.org/wiki/Digest_access_authentication)
is a method of HTTP authentication that improves upon
[Basic authentication](http://en.wikipedia.org/wiki/Basic_authentication_scheme)
by providing a way to authenticate without having to transmit the password in
clear text across the network.

This adapter allows authentication against text files containing lines having
the basic elements of Digest authentication:

- `username`, such as "joe.user";
- `realm`, such as "Administrative Area";
- an MD5 hash of the username, realm, and password, separated by colons.

The above elements are separated by colons, as in the following example (in
which the password is "somePassword"):

```text
someUser:Some Realm:fde17b91c3a510ecbaf7dbd37f59d4f8
```

> CAUTION: **Digest Authentication Security Issues**
>
> Digest authentication utilizes `md5()` for hash creation and hash comparisons by default.
> While the [HTDigest specification](https://datatracker.ietf.org/doc/html/rfc7616) has been expanded to allow SHA-256 and SHA-512 hashing algorithms, they require a different tool for the digest password file, as well as for the server-side to emit a header indicating what algorithm is in use.
> We plan to add new adapters to support SHA-256 and/or SHA-512 in version 3, but continue to provide the original Digest implementation here to ensure compatibility with existing tooling.
>
> However, we **strongly urge** users to use our Basic authentication, LDAP, DB table, or custom authentication adapters (preferably utilizing `password_hash()`/`password_verify()`) to prevent attack vectors common to the Digest algorithm.
>
> This adapter is deprecated as of version 2.10.0, and will be removed in version 3.0.0.

## Specifics

The digest authentication adapter, `Laminas\Authentication\Adapter\Digest`,
requires several input parameters:

- `filename`: Filename against which authentication queries are performed.
- `realm`: Digest authentication realm.
- `username`: Digest authentication user.
- `password`: Password for the user of the realm.

These parameters must be set prior to calling `authenticate()`.

## Identity

The digest authentication adapter returns a `Laminas\Authentication\Result` object
populated with the identity as an array containing the keys `realm` and
`username`. The respective array values associated with these keys correspond
to the values set before `authenticate()` is called.

```php
use Laminas\Authentication\Adapter\Digest as AuthAdapter;

$adapter = new AuthAdapter(
    $filename,
    $realm,
    $username,
    $password
);

$result = $adapter->authenticate();
$identity = $result->getIdentity();
print_r($identity);

/*
Array
(
    [realm] => Some Realm
    [username] => someUser
)
*/
```
