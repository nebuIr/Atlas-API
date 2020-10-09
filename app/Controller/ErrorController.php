<?php

namespace AtlasAPI\Controller;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Psr\Container\ContainerInterface;

class ErrorController
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function page404(ServerRequest $request, Response $response, $args): Response
    {
        $response = $response->withHeader('Content-Type', 'application/json');
        $response
            ->withStatus(404)
            ->getBody()
            ->write('Not Found');

        return $response;
    }
}
