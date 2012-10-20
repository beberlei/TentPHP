<?php

require_once __DIR__ . "/../vendor/autoload.php";

use TentPHP\Application;
use TentPHP\Client;
use TentPHP\DBAL\DoctrineDBALState;
use Doctrine\DBAL\DriverManager;
use Guzzle\Http\Client as HttpClient;

$tentEntityUrl = 'https://beberlei.tent.is';
$currentUrl    = "http://".$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

$application = new Application(array(
    "name"        => "SimpleStatus",
    "description" => "Very Simple Status App ",
    "url"         => $currentUrl,
    "icon"        => $currentUrl . "/icon.png",
    "redirect_uris" => array(
        $currentUrl ."?action=callback",
    ),
    "scopes" => array(
        "write_posts" => "Writes simple status updates"
    )
));

$file = __DIR__ . '/tentclient.db';
$conn = DriverManager::getConnection(array(
    'driver'   => 'pdo_sqlite',
    'path'     => $file,
));

$state = new DoctrineDBALState($conn);

if ( ! file_exists($file)) {
    $schema = $state->createSchema();
    foreach ($schema->toSQL($conn->getDatabasePlatform()) as $sql) {
        $conn->exec($sql);
    }
}

$httpClient = new HttpClient();
$client     = new Client($application, $httpClient, $state);


$action = isset($_GET['action']) ? $_GET['action'] : 'status';

switch ($action) {
    case 'login':
        $loginUrl = $client->getLoginUrl($tentEntityUrl);
        header("Location: " . $loginUrl);

        break;
    case 'callback':
        $client->authorize($_GET['state'], $_GET['code']);
        header("Location: " . $currentUrl);
        break;
    case 'post':
        $post = \TentPHP\Post::create('https://tent.io/types/post/status/v0.1.0');
        $post->setContent(array(
            'text' => strip_tags($_POST['message'])
        ));

        $userClient = $client->getUserClient($tentEntityUrl);
        $userClient->createPost($post);

        header("Location: " . $currentUrl);
        break;
}

?>

<a href="<?= $currentUrl; ?>?action=login">Authorize</a>

<form method="post" action="<?= $currentUrl; ?>?action=post">
    <input type="text" name="message" value="" />
    <input type="submit" value="Send" />
</form>


