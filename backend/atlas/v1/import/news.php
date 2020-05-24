<?php
if (PHP_SAPI !== 'cli') {
    throw new RuntimeException('This application must be run on the command line.');
}

require_once __DIR__ . '/../templates/news.php';
require_once __DIR__ . '/../classes/News.php';

$news = new News();

// Configuration
$url = 'https://www.nomanssky.com/';
$category = 'news';

$latest_post_timestamp = getTimestamp($url, $category);
$latest_post_timestamp_db = $news->getFieldFromLatestResult('timestamp');
$latest_post_title = getTitle($url, $category);
$latest_post_title_db = $news->getFieldFromLatestResult('title');
$latest_post_id = $news->getFieldFromLatestResult('id');

if ($latest_post_timestamp !== (int)$latest_post_timestamp_db) {
    if ($news->getItemCount() === 0) {
        $items = getNews($url, $category, null, false);
        foreach ($items as $item) {
            $news->SQLImport($item, 'add');
        }
    } else {
        $news->SQLImport(getNews($url, $category, $latest_post_id, true), 'add');
    }
} else {
    echo "\n\n----- No new posts found. -----\n\nLatest post found (" . $latest_post_timestamp . '): ' . $latest_post_title . "\nLatest post in DB (" . $latest_post_timestamp_db . '): ' . $latest_post_title_db . "\n\n";
}