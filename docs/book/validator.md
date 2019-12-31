# Authentication Validator

`Laminas\Authentication\Validator\Authentication` provides a [laminas-validator](https://github.com/laminas/laminas-validator)
`ValidatorInterface` implementation, which can be used within an
[input filter](https://github.com/laminas/laminas-inputfilter) or
[form](https://github.com/laminas/laminas-form), or anywhere you
you simply want a true/false value to determine whether or not authentication
credentials were provided.

The available configuration options include:

- `adapter`: an instance of `Laminas\Authentication\Adapter\AdapterInterface`.
- `identity`: the identity or name of the identity field in the provided context.
- `credential`: credential or the name of the credential field in the provided context.
- `service`: an instance of `Laminas\Authentication\AuthenticationService`.
- `code_map`: map of `Laminas\Authentication\Result` codes to validator message identifiers.

## Usage

```php
use My\Authentication\Adapter;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Validator\Authentication as AuthenticationValidator;

$service   = new AuthenticationService();
$adapter   = new Adapter();
$validator = new AuthenticationValidator([
    'service' => $service,
    'adapter' => $adapter,
]);

$validator->setCredential('myCredentialContext');
$validator->isValid('myIdentity', [
     'myCredentialContext' => 'myCredential',
]);
```

## Validation messages

The authentication validator defines five failure message types; identifiers
for them are available as constants for convenience.
Common authentication failure codes, defined as constants in
`Laminas\Authentication\Result`, are mapped to validation messages
using a map in `CODE_MAP` constant. Other authentication codes default to the
`general` message type.

```php
namespace Laminas\Authentication\Validator;

use Laminas\Authentication\Result;

class Authentication
{
    const IDENTITY_NOT_FOUND = 'identityNotFound';
    const IDENTITY_AMBIGUOUS = 'identityAmbiguous';
    const CREDENTIAL_INVALID = 'credentialInvalid';
    const UNCATEGORIZED      = 'uncategorized';
    const GENERAL            = 'general';

    const CODE_MAP = [
        Result::FAILURE_IDENTITY_NOT_FOUND => self::IDENTITY_NOT_FOUND,
        Result::FAILURE_CREDENTIAL_INVALID => self::CREDENTIAL_INVALID,
        Result::FAILURE_IDENTITY_AMBIGUOUS => self::IDENTITY_AMBIGUOUS,
        Result::FAILURE_UNCATEGORIZED      => self::UNCATEGORIZED,
    ];
}
```

The authentication validator extends `Laminas\Validator\AbstractValidator`, providing
a way common for all framework validators to access, change or translate message templates.  
More information is available in the
[laminas-validator documentation](https://docs.laminas.dev/laminas-validator/messages/)

## Configure validation messages for custom authentication result codes

The constructor configuration option `code_map` allows mapping custom codes
from `Laminas\Authentication\Result` to validation message identifiers.  
`code_map` is an array of integer code => string message identifier pairs

A new custom message identifier can be specified in `code_map` which will then
be registered as a new message type with the template value set to the `general` message.
Once registered, the message template for the new identifier can be changed
as described in the [laminas-validator documentation](https://docs.laminas.dev/laminas-validator/messages/).

```php
use Laminas\Authentication\Validator\Authentication as AuthenticationValidator;

$validator = new AuthenticationValidator([
    'code_map' => [
        // map custom result code to existing message
        -990 => AuthenticationValidator::IDENTITY_NOT_FOUND,
        // map custom result code to a new message type
        -991 => 'custom_failure_identifier',
    ],
]);

$validator->setMessage('Custom Error Happened', 'custom_failure_identifier');
```
