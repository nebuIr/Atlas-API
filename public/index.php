<?php
use DI\ContainerBuilder;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Container\ContainerInterface;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();
$containerBuilder->enableCompilation(__DIR__ . '/../var/cache');

// Setup Settings
$settings = require __DIR__ . '/../config/settings.php';
$settings($containerBuilder);

// Setup DB
$containerBuilder->addDefinitions([
    PDO::class => static function (ContainerInterface $container) {
        $settings = $container->get('settings')['database'];

        return new PDO(
            'mysql:dbhost=' . $settings['dbhost'] . ';dbname=' . $settings['dbname'] . ';charset=UTF8',
            $settings['dbuser'],
            $settings['dbpass']
        );
    },
]);

$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

$middleware = require __DIR__ . '/../config/middleware.php';
$middleware($app);

$routes = require __DIR__ . '/../config/routes.php';
$routes($app);

$app->addRoutingMiddleware();

$request = ServerRequestFactory::fromGlobals();
$app->run($request);
