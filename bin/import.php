<?php
if (PHP_SAPI !== 'cli') {
    throw new RuntimeException('This application must be run on the command line.');
}

if (!isset($argv[1])) {
    throw new RuntimeException('Please provide a category [news, releases, version, all]');
}

require_once __DIR__ . '/../src/lib/simple_html_dom.php';

// Configuration
$url = 'https://www.nomanssky.com/';

switch ($argv[2] ?? '') {
    case '':
    case 'import':
        import($argv[1], $url);
        break;
    case 'reimport':
        clear($argv[1]);
        import($argv[1], $url);
        break;
    case 'clear':
        clear($argv[1]);
        break;
}

function import($category, $url) {
    switch ($category) {
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
}

function clear($category) {
    switch ($category) {
        case 'news':
            clearNews();
            break;
        case 'releases':
            clearReleases();
            break;
        case 'version':
            clearVersion();
            break;
        case 'all':
            clearNews();
            clearReleases();
            clearVersion();
            break;
    }
}

function importNews($url) {
    require_once __DIR__ . '/../src/v1/classes/News.php';
    require_once __DIR__ . '/../src/v1/templates/news.php';

    $news = new News();
    $items = getNews($url, 'news');

    foreach ($items as $item) {
        $news->SQLImport($item, 'add');
    }
}

function importReleases($url) {
    require_once __DIR__ . '/../src/v1/classes/Releases.php';
    require_once __DIR__ . '/../src/v1/templates/releases.php';

    $releases = new Releases();
    $items = getRelease($url, 'release-log');

    foreach ($items as $item) {
        $releases->SQLImport($item, 'add');
    }
}

function importVersion() {
    require_once __DIR__ . '/../src/v1/classes/Version.php';
    require_once __DIR__ . '/../src/v1/templates/version.php';

    $version = new Version();
    $url = 'https://nomanssky.gamepedia.com/';

    $version->SQLImport(getVersion($url));
}

function clearNews() {
    require_once __DIR__ . '/../src/v1/classes/News.php';

    $news = new News();
    $news->clearTable();
}

function clearReleases() {
    require_once __DIR__ . '/../src/v1/classes/Releases.php';

    $news = new Releases();
    $news->clearTable();
}

function clearVersion() {
    require_once __DIR__ . '/../src/v1/classes/Version.php';

    $news = new Version();
    $news->clearTable();
}