<?php
if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

require __DIR__ . "/../../../lib/simple_html_dom.php";
header('Content-Type: application/json');

$items = array();
$url = 'https://nomanssky.gamepedia.com/';

updatePosts($url);

function updatePosts($url) {
    echo "\n\n----- Import started! -----\n\n\n";
    $html = file_get_html($url);
    $posts = $html->find('div.fplink', 0);

    $item['url'] = $posts->find('a', 0)->href;
    $baseUri = 'nomanssky.gamepedia.com';
    $baseUriSsl = 'https://nomanssky.gamepedia.com';
    if (strpos($item['url'], $baseUri) === false) {
        $url = $baseUriSsl . $item['url'];
        $item['url'] = $url;
    }
    $item['version'] = $posts->find('a', 0)->plaintext;
    $item['timestamp'] = $posts->plaintext;
    $timestamp = explode("(",$item['timestamp']);
    $item['timestamp'] = $timestamp[1];
    $datePattern = array(")");
    $dateReplace = array("");
    $timestamp = str_replace($datePattern, $dateReplace, $item['timestamp']);
    $timestamp = strtotime($timestamp);
    $item['timestamp'] = $timestamp;
    $items[] = $item;

    echo 'Updated version: ' . $item['version'] . "\n";

    $export = fopen("posts.json", "w") or die("Unable to open file!");
    fwrite($export, json_encode($items));

    handler();

    echo "\n\n----- Import successful! -----\n\n";
}

function handler() {
    include_once(__DIR__ . "/../../../../public/atlas/v1/version/main.php");
    $Version = new Version();
    $Version->mainSqlUpdate();
}