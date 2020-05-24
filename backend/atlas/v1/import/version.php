<?php
if (PHP_SAPI !== 'cli') {
    throw new \RuntimeException('This application must be run on the command line.');
}

require_once __DIR__ . '/../templates/version.php';
require_once __DIR__ . '/../classes/Version.php';

$version = new Version();
$items = [];

// Configuration
$url = 'https://nomanssky.gamepedia.com/';

$version->SQLImport(getVersion($url));