<?php

namespace AtlasAPI\Controller;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Psr\Container\ContainerInterface;

class HomeController
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function index(ServerRequest $request, Response $response, $args): Response
    {
        require_once __DIR__ . '/../../views/home.php';

        return $response;
    }
}
