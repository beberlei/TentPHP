# PHP Tent Client

Tent Client API for PHP

Tent.io is a distributed protocol for social networking. There are tent servers
and tent applications.  Users host their data on tent servers. Tent
applications can modify this data. To implement a tent application you need a
client. This library provides a client written in PHP.

The client has to act as an application to be able to access user details on
any tent server. to work applications are always required to maintain some level of
state about Tent Servers Url and their OAuth Client Ids and Mac Keys.

## Features

* Support for the Tent.io Application API
* Automatic registration of Applications on any tent-server
* Persistence of application and user authorizations (MAC Auth)

## State & Persistence

This application state is hidden behind a persistence interface
``TentPHP\Persistence\ApplicationState``. We are shipping a Doctrine DBAL
based implementation.

Other clients (PHP + other languages) put the burdon of persistence on you,
returning all the data that is stateful from their methods. With the ``ApplicationState``
interface you can implement this yourself, or use our Doctrine backend. In any
case this simplifies the usage of the client considerably.

## Install

Use Composer to install TentPHP and all its dependencies:

```javascript
{
    "require": {
        "beberlei/tent-php": "*",
        "doctrine/dbal": "*"
    }
}
```

## API (With Doctrine DBAL)

### Setup & Configuration

```php
<?php
use TentPHP\Application;
use TentPHP\Client;
use TentPHP\PhpSessionState;
use TentPHP\DBAL\DoctrineUserStorage;
use Doctrine\DBAL\DriverManager;
use Guzzle\Http\Client as HttpClient;

$application = new Application(array(
  "name" => "FooApp",
  "description" => "Does amazing foos with your data",
  "url" => "http =>//example.com",
  "icon" => "http =>//example.com/icon.png",
  "redirect_uris" => array(
    "https =>//app.example.com/tent/callback"
  ),
  "scopes" => array(
    "write_profile" => "Uses an app profile section to describe foos",
    "read_followings" => "Calculates foos based on your followings"
  )
));

$conn = DriverManager::getConnection(array(
    'driver'   => 'pdo_mysql',
    'host'     => 'localhost',
    'dbname'   => 'tentclient',
    'username' => 'user',
    'password' => 'pw',
));
$userStorage = new DoctrineUserStorage($conn);
$state = new PhpSessionState();
$client = new Client($application, $httpClient, $userStorage, $state);
```

### Request Login URL for User

```php
$loginUrl = $client->getLoginUrl('https://beberlei.tent.is');
header("Location: " . $loginUrl);
```

### Authorize after OAuth grant

```php
$client->authorize($_GET['state'], $_GET['code']);
```

### Get UserClient

```php
$user = $client->getUserClient('https://beberlei.tent.is');
```


