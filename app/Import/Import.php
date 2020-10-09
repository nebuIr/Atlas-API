<?php

namespace AtlasAPI\Import;

use AtlasAPI\Import\Template\NewsTemplate;
use AtlasAPI\Import\Template\ReleasesTemplate;
use AtlasAPI\Import\Template\VersionTemplate;
use Phpfastcache\Helper\Psr16Adapter;

class Import
{
    public function init($container, $argv): void
    {
        // Configuration
        $url = 'https://www.nomanssky.com/';

        if (isset($argv[2])) {
            switch ($argv[1]) {
                case 'import':
                    $this->import($container, $argv[2], $url);
                    break;
                case 'reimport':
                    $this->clear($container, $argv[2], $argv[3] ?? 0);
                    $this->import($container, $argv[2], $url);
                    break;
                case 'clear':
                    $this->clear($container, $argv[2], $argv[3] ?? 0);
                    break;
                default:
                    echo "Please provide a valid command [import, reimport, clear]\n";
                    exit();
            }
        } else {
            echo "Please provide a valid category [news, releases, version, all]\n";
        }
    }

    public function import($container, $category, $url): void
    {
        $Psr16Adapter = new Psr16Adapter('Files');

        switch ($category) {
            case 'news':
                $this->importNews($container, $url, $Psr16Adapter);
                break;
            case 'releases':
                $this->importReleases($container, $url, $Psr16Adapter);
                break;
            case 'version':
                $this->importVersion($container);
                break;
            case 'all':
                $this->importNews($container, $url, $Psr16Adapter);
                $this->importReleases($container, $url, $Psr16Adapter);
                $this->importVersion($container);
                break;
            default:
                echo "Please provide a valid category [news, releases, version, all]\n";
                exit();
        }
    }

    public function clear($container, $category, $limit = 0): void
    {
        $news = new NewsImport($container);
        $releases = new NewsImport($container);
        if ($limit === 0) {
            $newsLimit = $news->getItemCount();
            $releasesLimit = $releases->getItemCount();
        } else {
            $newsLimit = $limit;
            $releasesLimit = $limit;
        }

        switch ($category) {
            case 'news':
                $this->clearNews($container, $newsLimit);
                break;
            case 'releases':
                $this->clearReleases($container, $releasesLimit);
                break;
            case 'version':
                $this->clearVersion($container);
                break;
            case 'all':
                $this->clearNews($container, $newsLimit);
                $this->clearReleases($container, $releasesLimit);
                $this->clearVersion($container);
                break;
            default:
                echo "Please provide a valid category [news, releases, version, all]\n";
                exit();
        }
    }

    public function importNews($container, $url, $Psr16Adapter): void
    {
        $newsTemplate = new NewsTemplate($container, $Psr16Adapter);
        $news = new NewsImport($container);
        $items = $newsTemplate->getNews($url, 'news');
        $items_done = 0;

        foreach ($items as $item) {
            if ($news->SQLImport($item, 'add')) {
                $items_done++;
            }
        }

        if ($items_done > 0) {
            echo "[NEWS] New items imported to DB: $items_done\n";
        }
        echo "===============\n";
    }

    public function importReleases($container, $url, $Psr16Adapter): void
    {
        $releasesTemplate = new ReleasesTemplate($container, $Psr16Adapter);
        $releases = new ReleasesImport($container);
        $items = $releasesTemplate->getRelease($url, 'release-log');
        $items_done = 0;

        foreach ($items as $item) {
            if ($releases->SQLImport($item, 'add')) {
                $items_done++;
            }
        }

        if ($items_done > 0) {
            echo "[RELEASES] New items imported to DB: $items_done\n";
        }
        echo "===============\n";
    }

    public function importVersion($container): void
    {
        $url = 'https://nomanssky.gamepedia.com/';
        $releasesTemplate = new VersionTemplate();
        $version = new VersionImport($container);

        if ($version->SQLImport($releasesTemplate->getVersion($url))) {
            echo "[VERSION] DB updated\n";
        } else {
            echo "[VERSION] Import failed\n";
        }
        echo "===============\n";
    }

    public function clearNews($container, $limit): void
    {
        $news = new NewsImport($container);
        $news->clearTable($limit);
    }

    public function clearReleases($container, $limit): void
    {
        $news = new ReleasesImport($container);
        $news->clearTable($limit);
    }

    public function clearVersion($container): void
    {
        $news = new VersionImport($container);
        $news->clearTable();
    }
}