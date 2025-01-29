<?php

namespace AtlasAPI\Import\Template;

use simplehtmldom\HtmlWeb;

class VersionTemplate
{
    public function getVersion($url): array
    {
        $html = (new HtmlWeb())->load($url);
        $post = ($html) ? $html->find('div.fplink', 0) : [];

        return $this->templateVersion($post, $url);
    }

    public function templateVersion($post, $url): array
    {
        // ID
        $item['id'] = 0;

        // URL
        $item['url'] = $post->find('a', 0)->href;

        // Check and add the missing URL part if necessary
        $parsed_url = parse_url($item['url']);
        if (!isset($parsed_url['scheme'])) {
            $item['url'] = rtrim($url, '/') . '/' . ltrim($item['url'], '/');
        }
        $item['url'] = str_replace('http://', 'https://', $item['url']);

        // Version
        $item['version'] = $post->find('a', 0)->plaintext;

        // Timestamp
        $search = [')'];
        $replace = [''];
        $item['timestamp'] = $post->plaintext;
        $item['timestamp'] = explode('(', $item['timestamp'])[1];
        $item['timestamp'] = strtotime(str_replace($search, $replace, $item['timestamp']));

        if (!$item['timestamp']) {
            $item['timestamp'] = 0;
        }

        echo '[VERSION] Version found: ' . $item['version'] . "\n";

        return $item;
    }
}