<?php
/**
 * TentPHP
 *
 * LICENSE
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace TentPHP\Silex;

use Silex\Application;
use Silex\ServiceProviderInterface;
use TentPHP\Application as TentApplication;
use TentPHP\DBAL\DoctrineUserStorage;
use TentPHP\Silex\SymfonySessionState;

class TentServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['tent.application'] = $app->share(function($app) {
            return new TentApplication($app['tent.application.options']);
        });

        $app['tent.user_storage'] = $app->share(function ($app) {
            return new DoctrineUserStorage($app['db']);
        });

        $app['tent.client'] = $app->share(function($app) {
            $httpClient = new Guzzle\Http\Client();

            return new TentPHP\Client(
                $app['tent.application'],
                $httpClient,
                $app['tent.user_storage'],
                $app['tent.session_state']
            );
        });

        $app['tent.session_state'] = $app->share(function($app) {
            return new SymfonySessionState($app['session']);
        });
    }
}

