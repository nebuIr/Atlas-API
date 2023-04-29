<?php

namespace AtlasAPI\Transformer;

use League\Fractal\TransformerAbstract;

class ReleaseTransformer extends TransformerAbstract
{
    public function transform(array $row): array
    {
        return array_merge([
            'id'        => (int) $row['id'],
            'url'       => $row['url'],
            'title'     => $row['title'],
            'timestamp' => (int) $row['timestamp'],
            'platforms' => [
                'pc'                => (bool) $row['platform_pc'],
                'ps4'               => (bool) $row['platform_ps4'],
                'ps5'               => (bool) $row['platform_ps5'],
                'xbox-one'          => (bool) $row['platform_xbox_one'],
                'xbox-series'       => (bool) $row['platform_xbox_series'],
                'xbox-game-pass'    => (bool) $row['platform_xbox_game_pass'],
                'nintendo-switch'   => (bool) $row['platform_nintendo_switch'],
                'ms-store'          => (bool) $row['platform_ms_store'],
            ],
            'images' => [
                'image_large'   => $row['image'],
            ],
            'excerpt'   => $row['excerpt'],
            'body'      => $row['body'],
        ]);
    }
}
