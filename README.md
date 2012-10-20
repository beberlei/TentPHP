# PHP Tent Client

Tent Client API for PHP

Tent.io is a distributed protocol for social networking. There are tent servers
and tent applications.  Users host their data on tent servers. Tent
applications can modify this data. To implement a tent application you need a
client. This library provides a client written in PHP.

The client has to act as an application to be able to access user details on
any tent server. to work applications are always required to maintain some level of
state about Tent Servers Url and their OAuth Client Ids and Mac Keys.

This application state is hidden behind a persistence interface
``TentPHP\Persistence\ApplicationState``. We are shipping a Doctrine DBAL
based implementation.

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
use TentPHP\DBAL\DoctrineDBALState;
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
$state = new DoctrineDBALState($conn);
$client = new Client($application, $httpClient, $state);
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


