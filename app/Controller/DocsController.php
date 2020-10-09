<?php

namespace AtlasAPI\Controller;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Psr\Container\ContainerInterface;

class DocsController
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function docs(ServerRequest $request, Response $response, $args): Response
    {
        require_once __DIR__ . '/../../views/docs.php';

        return $response;
    }
}
