# DbTable Credential Treatment

`Laminas\Authentication\Adapter\DbTable\CredentialTreatmentAdapter` will execute a
SQL query containing the provided identity and credentials, passing the
credentials to a *credential treatment* function defined on the RDBMS server;
if an identity is returned, authentication succeeds. Credential
treatments depends on your RDBMS, and while simple hashing function such as
`md5` and `sha1` are generally available, it is recommended not to use them and
rather use the RDBMS specific function such as
[`PASSWORD(?)` for MySQL](http://dev.mysql.com/doc/refman/5.7/en/password-hashing.html) or
[`crypt()` for PostgreSQL](https://www.postgresql.org/docs/11/pgcrypto.html#id-1.11.7.34.6).
More details are available in the next section.

## Security considerations

Passing passwords to database in plaintext for insert or verification is
generally not recommended.  
Sql statements can and usually are logged by the database, and passwords in them
become visible to anyone with access to the logs or monitoring tools that
consume those logs.

The safer approach is to hash passwords, and to verify them against a stored
hash in your application code. This way the password never leaves the
application, and only the hashed value is exchanged with the database.

As such, this adapter is not recommended for new applications, and existing
applications should consider migrating to using PHP-provided password handling
functions such as `password_hash()` and `password_verify()`. See
[CallbackCheckAdapter](callback-check.md) for more info.

## Configuration Options

The available configuration options include:

- `tableName`: This is the name of the database table that contains the
  authentication credentials, and against which the database authentication
  query is performed.
- `identityColumn`: This is the name of the database table column used to
  represent the identity.  The identity column must contain unique values, such
  as a username or e-mail address.
- `credentialColumn`: This is the name of the database table column used to
  represent the credential. Under a simple identity and password authentication
  scheme, the credential value corresponds to the password. See also the
  `credentialTreatment` option.
- `credentialTreatment`: In many cases, passwords and other sensitive data
  are encrypted, hashed, encoded, obscured, salted or otherwise treated through
  some function or algorithm. By specifying a parameterized treatment string
  with this method, such as '`PASSWORD(?)`', a developer may
  apply such arbitrary SQL upon input credential data. Since these functions
  are specific to the underlying RDBMS, check the database manual for the
  availability of such functions for your database system.

## Basic Usage

As explained above, the
`Laminas\Authentication\Adapter\DbTable\CredentialTreatmentAdapter` constructor
requires an instance of `Laminas\Db\Adapter\Adapter` that serves as the database
connection to which the authentication adapter instance is bound. First, the
database connection should be created.

The following code creates an adapter for an in-memory database, creates a
simple table schema, and inserts a row against which we can perform an
authentication query later. This example requires the PDO SQLite extension to
be available:

```php
use Laminas\Db\Adapter\Adapter as DbAdapter;

// Create a SQLite database connection
$dbAdapter = new DbAdapter([
    'driver'   => 'Pdo_Sqlite',
    'database' => 'data/users.db',
]);

// Build a simple table creation query
$sqlCreate = 'CREATE TABLE [users] ('
    . '[id] INTEGER  NOT NULL PRIMARY KEY, '
    . '[username] VARCHAR(50) UNIQUE NOT NULL, '
    . '[password] VARCHAR(32) NULL, '
    . '[real_name] VARCHAR(150) NULL)';

// Create the authentication credentials table
$dbAdapter->query($sqlCreate);

// Build a query to insert a row for which authentication may succeed
$sqlInsert = "INSERT INTO users (username, password, real_name) "
    . "VALUES ('my_username', 'my_password', 'My Real Name')";

// Insert the data
$dbAdapter->query($sqlInsert);
```

With the database connection and table data available, an instance of
`Laminas\Authentication\Adapter\DbTable\CredentialTreatmentAdapter` may be
created. Configuration option values may be passed to the constructor or
deferred as parameters to setter methods after instantiation:

```php
use Laminas\Authentication\Adapter\DbTable\CredentialTreatmentAdapter as AuthAdapter;

// Configure the instance with constructor parameters:
$authAdapter = new AuthAdapter(
    $dbAdapter,
    'users',
    'username',
    'password'
);

// Or configure the instance with setter methods:
$authAdapter = new AuthAdapter($dbAdapter);

$authAdapter
    ->setTableName('users')
    ->setIdentityColumn('username')
    ->setCredentialColumn('password');
```

At this point, the authentication adapter instance is ready to accept
authentication queries. In order to formulate an authentication query, the
input credential values are passed to the adapter prior to calling the
`authenticate()` method:

```php
// Set the input credential values (e.g., from a login form):
$authAdapter
    ->setIdentity('my_username')
    ->setCredential('my_password');

// Perform the authentication query, saving the result
$result = $authAdapter->authenticate();
```

In addition to the availability of the `getIdentity()` method upon the
authentication result object, `Laminas\Authentication\Adapter\DbTable\CredentialTreatmentAdapter`
also supports retrieving the table row upon authentication success:

```php
// Print the identity:
echo $result->getIdentity() . "\n\n";

// Print the result row:
print_r($authAdapter->getResultRowObject());

/* Output:
my_username

Array
(
    [id] => 1
    [username] => my_username
    [password] => my_password
    [real_name] => My Real Name
)
*/
```

Since the table row contains the credential value, it is important to secure
the values against unintended access.

When retrieving the result object, we can either specify what columns to
return, or what columns to omit:

```php
// Specify the columns to return:
$columnsToReturn = [
    'id',
    'username',
    'real_name',
];
print_r($authAdapter->getResultRowObject($columnsToReturn));

/* Output:

Array
(
   [id] => 1
   [username] => my_username
   [real_name] => My Real Name
)
*/

// Or specify the columns to omit; when using this approach,
// pass a null value as the first argument to getResultRowObject():
$columnsToOmit = ['password'];
print_r($authAdapter->getResultRowObject(null, $columnsToOmit);

/* Output:

Array
(
   [id] => 1
   [username] => my_username
   [real_name] => My Real Name
)
*/
```

## Advanced Usage

While the primary purpose of laminas-authentication is **authentication** and not
**authorization**, there are a few instances and problems that toe the line
between which domain they fit.  Depending on how you've decided to explain your
problem, it sometimes makes sense to solve what could look like an
authorization problem within the authentication adapter.

Below are a few examples showing how you can provide compound criteria to the
credential treatment to solve more complex problems.

### Check for compromised user

In this scenario, we use the credential treatment `PASSWORD()`, but also check to see
that the user has not been flagged as "compromised", which is a potential value
of the `status` field for the user record.

```php
use Laminas\Authentication\Adapter\DbTable\CredentialTreatmentAdapter as AuthAdapter;

// The status field value of an account is not equal to "compromised"
$adapter = new AuthAdapter(
    $db,
    'users',
    'username',
    'password',
    'PASSWORD(?) AND status != "compromised"'
);
```

### Check for active user

In this example, we check to see if a user is active; this may be necessary
if we require a user to login once over X days, or if we need to ensure that
they have followed a verification process.

```php
use Laminas\Authentication\Adapter\DbTable\CredentialTreatmentAdapter as AuthAdapter;

// The active field value of an account is equal to the sql boolean literal TRUE
$adapter = new AuthAdapter(
    $db,
    'users',
    'username',
    'password',
    'PASSWORD(?) AND active = TRUE'
);
```

### Salting

Another scenario can be the implementation of a salting mechanism. Salting
refers to a technique for improving application security; it's based on the
idea that concatenating a random string to every password makes it impossible
to accomplish a successful brute force attack on the database using
pre-computed hash values from a dictionary.

Let's modify our table to store our salt string:

```php
$sqlAlter = "ALTER TABLE [users] "
    . "ADD COLUMN [password_salt] "
    . "AFTER [password]";
```

Salts should be created *for each user* using a cryptographically sound pseudo-random number generator (CSPRNG).
PHP 7 provides an implementation via `random_bytes()` (and
the [random_compat package provides them for older, supported versions of PHP](https://github.com/paragonie/random_compat)):

```php
$salt = random_bytes(32);
```

For earlier versions of PHP, use [laminas-math](https://github.com/laminas/laminas-math)'s `Laminas\Math\Rand`:

```php
use Laminas\Math\Rand;

$salt = Rand::getBytes(32, true);
```

(As of laminas-math 2.7.0, `Rand::getBytes()` will proxy to `random_bytes()` when
running under PHP 7, making it a good, forwards-compatible solution for your
application.)

Do this each time you create a user or update their password, and store it in the
`password_salt` column you created.

Now let's build the adapter:

```php
$adapter = new AuthAdapter(
$db,
    'users',
    'username',
    'password',
    "PASSWORD(CONCAT('staticSalt', ?, password_salt))"
);
```

> #### Salt security
>
> You can improve security even more by using a static salt value hard coded
> into your application. In the case that your database is compromised (e.g.
> by an SQL injection attack) but your web server is intact, your data is still
> unusable for the attacker.
>
> Define the salt as an environment variable on your web server, and then
> either pull it from the environment, or assign it to a constant during
> bootstrap; pass the value to the credential treatment when creating your
> adapter.
>
> The above example uses the value "staticSalt"; you should create a better
> salt using one of the methods outlined above.

### Alter the SQL select directly

Another alternative is to use the `getDbSelect()` method to retrieve the
`Laminas\Db\Sql\Select` instance associated with the adapter and modify it. (The
method is common to all `Laminas\Authentication\Adapter\DbTable` adapters.) The
`Select` instance is consumed by the `authenticate()` routine when building the
SQL to execute on the RDBMS server.  It is important to note that this method
will always return the same `Select` instance regardless if `authenticate()`
has been called or not; identity and credential values are passed to the
instance as placeholders.

This approach allows you to define a generic credential treatment, and then add
criteria later, potentially based on specific paths through the application.

The following uses the second example in this section, adding another `WHERE`
clause to determine if the user is active in the system.

```php
// Create a basic adapter, with only an PASSWORD() credential treatment:
$adapter = new AuthAdapter(
    $db,
    'users',
    'username',
    'password',
    'PASSWORD(?)'
);

// Now retrieve the Select instance and modify it:
$select = $adapter->getDbSelect();
$select->where('active = TRUE');

// Authenticate; this will include "users.active = TRUE" in the WHERE clause:
$adapter->authenticate();
```
