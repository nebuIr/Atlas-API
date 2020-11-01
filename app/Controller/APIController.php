<?php

namespace AtlasAPI\Controller;

use AtlasAPI\Helper\HelperFunctions;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use PDO;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\Container\ContainerInterface;

class APIController
{
    private ContainerInterface $container;
    private HelperFunctions $helper;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->helper = new HelperFunctions();
    }

    public function getItems(ServerRequest $request, Response $response, $args): Response
    {
        $category = $request->getAttributes()['category'];
        $type = $request->getAttributes()['type'];
        $id = $args['id'] ?? 0;
        $params = $this->helper->checkQueryParams($request->getQueryParams(), $type);
        $itemsKey = hash('sha256', "$category-$type-$id" . implode('-', $params));
        $items = [];

        $Psr16Adapter = new Psr16Adapter('Files');
        if (!$Psr16Adapter->has($itemsKey)){
            $pdo = $this->container->get(PDO::class);

            if ('single' === $type) {
                $stmt = $pdo->prepare("SELECT * FROM $category WHERE id = ?");
                $stmt->execute([$id]);
            } else {
                $stmt = $pdo->query("SELECT * FROM $category ORDER BY id {$params['order']} LIMIT {$params['limit']} OFFSET {$params['offset']}");
            }

            if ($sql_result = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
                foreach ($sql_result as $result) {
                    $resource = new Item($result, $this->helper->getTransformer($category), $category);
                    $manager = $this->container->get(Manager::class);
                    $items[] = $manager->createData($resource)->toArray()['data'];
                }
            }

            if ($type === 'latest' || $type === 'single') {
                $items = $items[0];
            }

            $Psr16Adapter->set($itemsKey, $items, 600);
        } else {
            $items = $Psr16Adapter->get($itemsKey);
        }

        if (empty($items)) {
            $response = $response->withHeader('Content-Type', 'application/json');
            $response
                ->withStatus(404)
                ->getBody()
                ->write("Not Found");

            return $response;
        }

        $response = $response->withHeader('Content-Type', 'application/json');
        $response
            ->getBody()
            ->write(json_encode($items, JSON_THROW_ON_ERROR));

        return $response;
    }
}
