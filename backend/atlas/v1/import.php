<?php
if (PHP_SAPI !== 'cli') {
    throw new RuntimeException('This application must be run on the command line.');
}

if (!isset($argv[1])) {
    throw new RuntimeException('Please provide a category [news, releases, version, all]');
}

require_once __DIR__ . '/../../lib/simple_html_dom.php';

// Configuration
$url = 'https://www.nomanssky.com/';

switch ($argv[1]) {
    case 'news':
        importNews($url);
        break;
    case 'releases':
        importReleases($url);
        break;
    case 'version':
        importVersion();
        break;
    case 'all':
        importNews($url);
        importReleases($url);
        importVersion();
        break;
}

function importNews($url) {
    require_once __DIR__ . '/classes/News.php';
    require_once __DIR__ . '/templates/news.php';

    $news = new News();
    $items = getNews($url, 'news');

    foreach ($items as $item) {
        $news->SQLImport($item, 'add');
    }
}

function importReleases($url) {
    require_once __DIR__ . '/classes/Releases.php';
    require_once __DIR__ . '/templates/releases.php';

    $releases = new Releases();
    $items = getRelease($url, 'release-log');

    foreach ($items as $item) {
        $releases->SQLImport($item, 'add');
    }
}

function importVersion() {
    require_once __DIR__ . '/classes/Version.php';
    require_once __DIR__ . '/templates/version.php';

    $version = new Version();
    $url = 'https://nomanssky.gamepedia.com/';

    $version->SQLImport(getVersion($url));
}