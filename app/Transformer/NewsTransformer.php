<?php

namespace AtlasAPI\Transformer;

use League\Fractal\TransformerAbstract;

class NewsTransformer extends TransformerAbstract
{
    public function transform(array $row): array
    {
        return array_merge([
            'id'        => (int) $row['id'],
            'url'       => $row['url'],
            'title'     => $row['title'],
            'timestamp' => (int) $row['timestamp'],
            'images' => [
                'image_large' => $row['image'],
                'image_small' => $row['image_small'],
            ],
            'excerpt'   => $row['excerpt'],
            'body'      => $row['body'],
        ]);
    }
}
