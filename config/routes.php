<?php

use AtlasAPI\Controller;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app) {
    $app->get('/', Controller\HomeController::class . ':index');

    $app->group('/v1', function (RouteCollectorProxy $group) {
        $group->get('/news', Controller\APIController::class . ':getItems')
            ->setArguments(['category' => 'news', 'type' => 'all']);
        $group->get('/news/{id:[0-9]+}', Controller\APIController::class . ':getItems')
            ->setArguments(['category' => 'news', 'type' => 'single']);
        $group->get('/news/latest', Controller\APIController::class . ':getItems')
            ->setArguments(['category' => 'news', 'type' => 'latest']);

        $group->get('/releases', Controller\APIController::class . ':getItems')
            ->setArguments(['category' => 'releases', 'type' => 'all']);
        $group->get('/releases/{id:[0-9]+}', Controller\APIController::class . ':getItems')
            ->setArguments(['category' => 'releases', 'type' => 'single']);
        $group->get('/releases/latest', Controller\APIController::class . ':getItems')
            ->setArguments(['category' => 'releases', 'type' => 'latest']);

        $group->get('/version', Controller\APIController::class . ':getItems')
            ->setArguments(['category' => 'version', 'type' => 'single']);
    });

    $app->get('/docs/v1', Controller\DocsController::class . ':docs');

    $app->any('/{slug:.*}', Controller\ErrorController::class . ':page404');
};
