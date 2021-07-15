<?php

use AtlasAPI\Import\Import;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Psr\Container\ContainerInterface;

if (PHP_SAPI !== 'cli') {
    throw new RuntimeException("This application must be run on the command line\n");
}

if (!isset($argv[1])) {
    echo "Please provide a command [import, reimport, clear]\n";
    exit();
}

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

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
            'mysql:host=' . $settings['dbhost'] . ';port=' . $settings['dbport'] . ';dbname=' . $settings['dbname'] . ';charset=UTF8',
            $settings['dbuser'],
            $settings['dbpass']
        );
    },
]);

$container = $containerBuilder->build();

$obj = new Import();
$obj->init($container, $argv);