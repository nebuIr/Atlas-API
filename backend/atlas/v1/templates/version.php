<?php
require_once __DIR__ . '/../../../lib/simple_html_dom.php';

function getVersion($url): array
{
    $html = file_get_html($url);
    $post = $html->find('div.fplink', 0);

    return template($post);
}

function template($post)
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

    return $item;
}