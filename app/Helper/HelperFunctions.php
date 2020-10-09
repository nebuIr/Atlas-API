<?php

namespace AtlasAPI\Helper;

use AtlasAPI\Transformer\NewsTransformer;
use AtlasAPI\Transformer\ReleaseTransformer;
use AtlasAPI\Transformer\VersionTransformer;

class HelperFunctions
{
    public function getTransformer($category)
    {
        switch ($category) {
            case 'news':
                return new NewsTransformer();
            case 'releases':
                return new ReleaseTransformer();
            case 'version':
                return new VersionTransformer();
        }

        return null;
    }

    public function checkQueryParams($queryParams, $latest): array
    {
        $order = strtoupper($queryParams['order'] ?? 'DESC');
        $limit = $queryParams['limit'] ?? 99999;
        $offset = $queryParams['offset'] ?? 0;
        if ('DESC' !== $order && 'ASC' !== $order) {
            $order = 'DESC';
        }
        if (!(int) $limit) {
            $limit = 99999;
        }
        if (!(int) $offset) {
            $offset = 0;
        }
        if ('latest' === $latest) {
            $order = 'DESC';
            $limit = 1;
            $offset = 0;
        }

        return ['order' => $order, 'limit' => $limit, 'offset' => $offset];
    }
}