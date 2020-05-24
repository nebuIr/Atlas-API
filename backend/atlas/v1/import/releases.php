<?php
if (PHP_SAPI !== 'cli') {
    throw new RuntimeException('This application must be run on the command line.');
}

require_once __DIR__ . '/../templates/releases.php';
require_once __DIR__ . '/../classes/Releases.php';

$releases = new Releases();
$items = [];

// Configuration
$url = 'https://www.nomanssky.com/';
$category = 'release-log';

$latest_post_title = getTitle($url, $category);
$latest_post_title_db = $releases->getFieldFromLatestResult('title');
$latest_post_id = $releases->getFieldFromLatestResult('id');

if ($latest_post_title !== $latest_post_title_db) {
    if ($releases->getItemCount() === 0) {
        $items = getRelease($url, $category, null, false);
        foreach ($items as $item) {
            $releases->SQLImport($item, 'add');
        }
    } else {
        $releases->SQLImport(getRelease($url, $category, $latest_post_id, true), 'add');
    }
} else {
    echo "\n\n----- No new posts found. -----\n\nLatest post found : " . $latest_post_title . "\nLatest post in DB : " . $latest_post_title_db . "\n\n";
}