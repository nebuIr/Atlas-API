<?php

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;

return static function (ContainerBuilder $builder) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    $builder->addDefinitions([
        'settings' => [
            'database' => [
                'dbhost' => $_ENV['DB_HOST'],
                'dbport' => $_ENV['DB_PORT'] ?? '3306',
                'dbname' => $_ENV['DB_NAME'],
                'dbuser' => $_ENV['DB_USER'],
                'dbpass' => $_ENV['DB_PASS']
            ],
        ],
    ]);

    CacheManager::setDefaultConfig(new ConfigurationOption([
        'path' => __DIR__ . '/../var/cache/psr16',
    ]));
};
