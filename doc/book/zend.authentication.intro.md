# Introduction to Laminas\\Authentication

The `Laminas\Authentication` component provides an *API* for authentication and includes concrete
authentication adapters for common use case scenarios.

`Laminas\Authentication` is concerned only with **authentication** and not with **authorization**.
Authentication is loosely defined as determining whether an entity actually is what it purports to
be (i.e., identification), based on some set of credentials. Authorization, the process of deciding
whether to allow an entity access to, or to perform operations upon, other entities is outside the
scope of `Laminas\Authentication`. For more information about authorization and access control with
Laminas, please see the [Laminas\\Permissions\\Acl](laminas.permissions.acl) or
[Laminas\\Permissions\\Rbac](laminas.permissions.rbac) component.

> ## Note
There is no `Laminas\Authentication\Authentication` class, instead the class
`Laminas\Authentication\AuthenticationService` is provided. This class uses underlying authentication
adapters and persistent storage backends.

## Adapters

`Laminas\Authentication` adapters are used to authenticate against a particular type of authentication
service, such as *LDAP*, *RDBMS*, or file-based storage. Different adapters are likely to have
vastly different options and behaviors, but some basic things are common among authentication
adapters. For example, accepting authentication credentials (including a purported identity),
performing queries against the authentication service, and returning results are common to
`Laminas\Authentication` adapters.

Each `Laminas\Authentication` adapter class implements `Laminas\Authentication\Adapter\AdapterInterface`.
This interface defines one method, `authenticate()`, that an adapter class must implement for
performing an authentication query. Each adapter class must be prepared prior to calling
`authenticate()`. Such adapter preparation includes setting up credentials (e.g., username and
password) and defining values for adapter-specific configuration options, such as database
connection settings for a database table adapter.

The following is an example authentication adapter that requires a username and password to be set
for authentication. Other details, such as how the authentication service is queried, have been
omitted for brevity:

```php
<?php
use Laminas\Authentication\Adapter\AdapterInterface;

class My\Auth\Adapter implements AdapterInterface
{
    /**
     * Sets username and password for authentication
     *
     * @return void
     */
    public function __construct($username, $password)
    {
        // ...
    }

    /**
     * Performs an authentication attempt
     *
     * @return \Laminas\Authentication\Result
     * @throws \Laminas\Authentication\Adapter\Exception\ExceptionInterface
     *               If authentication cannot be performed
     */
    public function authenticate()
    {
        // ...
    }
}
```

As indicated in its docblock, `authenticate()` must return an instance of
`Laminas\Authentication\Result` (or of a class derived from `Laminas\Authentication\Result`). If for some
reason performing an authentication query is impossible, `authenticate()` should throw an exception
that derives from `Laminas\Authentication\Adapter\Exception\ExceptionInterface`.

## Results

`Laminas\Authentication` adapters return an instance of `Laminas\Authentication\Result` with
`authenticate()` in order to represent the results of an authentication attempt. Adapters populate
the `Laminas\Authentication\Result` object upon construction, so that the following four methods
provide a basic set of user-facing operations that are common to the results of
`Laminas\Authentication` adapters:

- `isValid()`- returns `TRUE` if and only if the result represents a successful authentication
attempt
- `getCode()`- returns a `Laminas\Authentication\Result` constant identifier for determining the type
of authentication failure or whether success has occurred. This may be used in situations where the
developer wishes to distinguish among several authentication result types. This allows developers to
maintain detailed authentication result statistics, for example. Another use of this feature is to
provide specific, customized messages to users for usability reasons, though developers are
encouraged to consider the risks of providing such detailed reasons to users, instead of a general
authentication failure message. For more information, see the notes below.
- `getIdentity()`- returns the identity of the authentication attempt
- `getMessages()`- returns an array of messages regarding a failed authentication attempt

A developer may wish to branch based on the type of authentication result in order to perform more
specific operations. Some operations developers might find useful are locking accounts after too
many unsuccessful password attempts, flagging an IP address after too many nonexistent identities
are attempted, and providing specific, customized authentication result messages to the user. The
following result codes are available:

```php
<?php
use Laminas\Authentication\Result;

Result::SUCCESS
Result::FAILURE
Result::FAILURE_IDENTITY_NOT_FOUND
Result::FAILURE_IDENTITY_AMBIGUOUS
Result::FAILURE_CREDENTIAL_INVALID
Result::FAILURE_UNCATEGORIZED
```

The following example illustrates how a developer may branch on the result code:

```php
<?php
// inside of AuthController / loginAction
$result = $this->auth->authenticate($adapter);

switch ($result->getCode()) {

    case Result::FAILURE_IDENTITY_NOT_FOUND:
        /** do stuff for nonexistent identity **/
        break;

    case Result::FAILURE_CREDENTIAL_INVALID:
        /** do stuff for invalid credential **/
        break;

    case Result::SUCCESS:
        /** do stuff for successful authentication **/
        break;

    default:
        /** do stuff for other failure **/
        break;
}

```

## Identity Persistence

Authenticating a request that includes authentication credentials is useful per se, but it is also
important to support maintaining the authenticated identity without having to present the
authentication credentials with each request.

*HTTP* is a stateless protocol, however, and techniques such as cookies and sessions have been
developed in order to facilitate maintaining state across multiple requests in server-side web
applications.

### Default Persistence in the PHP Session

By default, `Laminas\Authentication` provides persistent storage of the identity from a successful
authentication attempt using the *PHP* session. Upon a successful authentication attempt,
`Laminas\Authentication\AuthenticationService::authenticate()` stores the identity from the
authentication result into persistent storage. Unless specified otherwise,
`Laminas\Authentication\AuthenticationService` uses a storage class named
`Laminas\Authentication\Storage\Session`, which, in turn, uses [Laminas\\Session](laminas.session). A custom
class may instead be used by providing an object that implements
`Laminas\Authentication\Storage\StorageInterface` to
`Laminas\Authentication\AuthenticationService::setStorage()`.

> ## Note
If automatic persistent storage of the identity is not appropriate for a particular use case, then
developers may forget using the `Laminas\Authentication\AuthenticationService` class altogether,
instead using an adapter class directly.

**Modifying the Session Namespace**

`Laminas\Authentication\Storage\Session` uses a session namespace of '`Laminas_Auth`'. This namespace may
be overridden by passing a different value to the constructor of
`Laminas\Authentication\Storage\Session`, and this value is internally passed along to the constructor
of [Laminas\\Session\\Container](laminas.session). This should occur before authentication is attempted,
since `Laminas\Authentication\AuthenticationService::authenticate()` performs the automatic storage of
the identity.

```php
<?php
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Storage\Session as SessionStorage;

$auth = new AuthenticationService();

// Use 'someNamespace' instead of 'Laminas_Auth'
$auth->setStorage(new SessionStorage('someNamespace'));

/**
 * @todo Set up the auth adapter, $authAdapter
 */

// Authenticate, saving the result, and persisting the identity on
// success
$result = $auth->authenticate($authAdapter);

```

### Chain Storage

A website may have multiple storage in place. The `Chain` Storage can be used to glue these
together.

The `Chain` can for example be configured to first use a `Session` Storage and then use a `OAuth` as
a secondary Storage. One could configure this in the following way:

```php
<?php
$storage = new Chain;
$storage->add(new Session);
$storage->add(new OAuth); // Note: imaginary storage, not part of Laminas

```

Now if the `Chain` Storage is accessed its underlying Storage will get accessed in the order in
which they were added to the chain. Thus first the `Session` Storage is used. Now either:

- The `Session` Storage is non-empty and the `Chain` will use its contents.
- The `Session` Storage is empty. Next the `OAuth` Storage is accessed.
- If this one is also empty the Chain will act as empty.
- If this one is non-empty the `Chain` will use its contents. However it will also populate all
Storage with higher priority. Thus the `Session` Storage will be populated with the contents of the
`Oauth` Storage.

The priority of Storage in the Chain can be made explicit via the `Chain::add` method.

```php
<?php
$chain->add(new A, 2);
$chain->add(new B, 10); // First use B

```

### Implementing Customized Storage

Sometimes developers may need to use a different identity storage mechanism than that provided by
`Laminas\Authentication\Storage\Session`. For such cases developers may simply implement
`Laminas\Authentication\Storage\StorageInterface` and supply an instance of the class to
`Laminas\Authentication\AuthenticationService::setStorage()`.

**Using a Custom Storage Class**

In order to use an identity persistence storage class other than
`Laminas\Authentication\Storage\Session`, a developer implements
`Laminas\Authentication\Storage\StorageInterface`:

```php
<?php
use Laminas\Authentication\Storage\StorageInterface;

class My\Storage implements StorageInterface
{
    /**
     * Returns true if and only if storage is empty
     *
     * @throws \Laminas\Authentication\Exception\ExceptionInterface
     *               If it is impossible to
     *               determine whether storage is empty
     * @return boolean
     */
    public function isEmpty()
    {
        /**
         * @todo implementation
         */
    }

    /**
     * Returns the contents of storage
     *
     * Behavior is undefined when storage is empty.
     *
     * @throws \Laminas\Authentication\Exception\ExceptionInterface
     *               If reading contents from storage is impossible
     * @return mixed
     */

    public function read()
    {
        /**
         * @todo implementation
         */
    }

    /**
     * Writes $contents to storage
     *
     * @param  mixed $contents
     * @throws \Laminas\Authentication\Exception\ExceptionInterface
     *               If writing $contents to storage is impossible
     * @return void
     */

    public function write($contents)
    {
        /**
         * @todo implementation
         */
    }

    /**
     * Clears contents from storage
     *
     * @throws \Laminas\Authentication\Exception\ExceptionInterface
     *               If clearing contents from storage is impossible
     * @return void
     */

    public function clear()
    {
        /**
         * @todo implementation
         */
    }
}
```

In order to use this custom storage class, `Laminas\Authentication\AuthenticationService::setStorage()`
is invoked before an authentication query is attempted:

```php
<?php
use Laminas\Authentication\AuthenticationService;

// Instruct AuthenticationService to use the custom storage class
$auth = new AuthenticationService();

$auth->setStorage(new My\Storage());

/**
 * @todo Set up the auth adapter, $authAdapter
 */

// Authenticate, saving the result, and persisting the identity on
// success
$result = $auth->authenticate($authAdapter);

```

## Usage

There are two provided ways to use `Laminas\Authentication` adapters:

- indirectly, through `Laminas\Authentication\AuthenticationService::authenticate()`
- directly, through the adapter's `authenticate()` method

The following example illustrates how to use a `Laminas\Authentication` adapter indirectly, through the
use of the `Laminas\Authentication\AuthenticationService` class:

```php
<?php
use Laminas\Authentication\AuthenticationService;

// instantiate the authentication service
$auth = new AuthenticationService();

// Set up the authentication adapter
$authAdapter = new My\Auth\Adapter($username, $password);

// Attempt authentication, saving the result
$result = $auth->authenticate($authAdapter);

if (!$result->isValid()) {
    // Authentication failed; print the reasons why
    foreach ($result->getMessages() as $message) {
        echo "$message\n";
    }
} else {
    // Authentication succeeded; the identity ($username) is stored
    // in the session
    // $result->getIdentity() === $auth->getIdentity()
    // $result->getIdentity() === $username
}

```

Once authentication has been attempted in a request, as in the above example, it is a simple matter
to check whether a successfully authenticated identity exists:

```php
<?php
use Laminas\Authentication\AuthenticationService;

$auth = new AuthenticationService();

/**
 * @todo Set up the auth adapter, $authAdapter
 */

if ($auth->hasIdentity()) {
    // Identity exists; get it
    $identity = $auth->getIdentity();
}

```

To remove an identity from persistent storage, simply use the `clearIdentity()` method. This
typically would be used for implementing an application "logout" operation:

```php
<?php
$auth->clearIdentity();

```

When the automatic use of persistent storage is inappropriate for a particular use case, a developer
may simply bypass the use of the `Laminas\Authentication\AuthenticationService` class, using an adapter
class directly. Direct use of an adapter class involves configuring and preparing an adapter object
and then calling its `authenticate()` method. Adapter-specific details are discussed in the
documentation for each adapter. The following example directly utilizes `My\Auth\Adapter`:

```php
<?php
// Set up the authentication adapter
$authAdapter = new My\Auth\Adapter($username, $password);

// Attempt authentication, saving the result
$result = $authAdapter->authenticate();

if (!$result->isValid()) {
    // Authentication failed; print the reasons why
    foreach ($result->getMessages() as $message) {
        echo "$message\n";
    }
} else {
    // Authentication succeeded
    // $result->getIdentity() === $username
}

```
