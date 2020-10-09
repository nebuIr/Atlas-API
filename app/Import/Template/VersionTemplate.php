<?php

namespace AtlasAPI\Import\Template;

use simplehtmldom\HtmlWeb;

class VersionTemplate
{
    public function getVersion($url): array
    {
        $html = (new HtmlWeb())->load($url);
        $post = ($html) ? $html->find('div.fplink', 0) : [];

        return $this->templateVersion($post);
    }

    public function templateVersion($post): array
    {
        // ID
        $item['id'] = 0;

        // URL
        $item['url'] = $post->find('a', 0)->href;
        $baseUri = 'nomanssky.gamepedia.com';
        $baseUriSSL = 'https://nomanssky.gamepedia.com';
        if (strpos($item['url'], $baseUri) === false) {
            $item['url'] = $baseUriSSL . $item['url'];
        }

        // Version
        $item['version'] = $post->find('a', 0)->plaintext;

        // Timestamp
        $search = [')'];
        $replace = [''];
        $item['timestamp'] = $post->plaintext;
        $item['timestamp'] = explode('(', $item['timestamp'])[1];
        $item['timestamp'] = strtotime(str_replace($search, $replace, $item['timestamp']));

        echo '[VERSION] Version found: ' . $item['version'] . "\n";

        return $item;
    }
}