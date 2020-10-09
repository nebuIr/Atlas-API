<?php

namespace AtlasAPI\Transformer;

use League\Fractal\TransformerAbstract;

class VersionTransformer extends TransformerAbstract
{
    public function transform(array $row): array
    {
        return array_merge([
            'url' => $row['url'],
            'version' => $row['version'],
            'timestamp' => (int) $row['timestamp'],
        ]);
    }
}
