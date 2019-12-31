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
