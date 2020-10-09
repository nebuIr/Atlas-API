<?php

namespace AtlasAPI\Import\Template;

use AtlasAPI\Import\NewsImport;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\Container\ContainerInterface;
use RuntimeException;
use simplehtmldom\HtmlWeb;

class NewsTemplate
{
    private ContainerInterface $container;
    private Psr16Adapter $Psr16Adapter;

    public function __construct(ContainerInterface $container, Psr16Adapter $Psr16Adapter)
    {
        $this->container = $container;
        $this->Psr16Adapter = $Psr16Adapter;
    }

    public function getNews($url, $category): array
    {
        $news = new NewsImport($this->container);
        $html = (new HtmlWeb())->load($url . $category);

        if (!$html) {
            throw new RuntimeException('An error occurred trying to load ' . $url . $category);
        }

        $pages = $html->find('span.page-numbers.dots', 0)->next_sibling()->innertext;
        $items = [];
        $result = [];

        for ($page = 1; $page <= $pages; $page++) {
            $postsUrl = $url . $category . '/page/' . $page;
            $postsKey = hash('sha256', $postsUrl);

            if (!$this->Psr16Adapter->has($postsKey)) {
                $html = (new HtmlWeb())->load($postsUrl);
                $posts = ($html) ? $html->find('article') : [];
                $this->Psr16Adapter->set($postsKey, $posts, 600);
            } else {
                $posts = $this->Psr16Adapter->get($postsKey);
            }

            foreach ($posts as $post) {
                $postKey = hash('sha256',
                    $category . $page . $post->find('a', 0)->title . $post->find('a', 0)->href);

                if (!$this->Psr16Adapter->has($postKey)) {
                    $item = $this->templateNews($post);

                    if ($news->getFieldByTimestamp('timestamp', $item['timestamp'])) {
                        if (!count($items)) {
                            echo "[NEWS] Nothing new found\n";
                        }
                        break 2;
                    }

                    $items[] = $item;
                    $this->Psr16Adapter->set($postKey, $item, 600);

                    echo '[NEWS] New entry found: ' . $item['title'] . "\n";
                } else {
                    $item = $this->Psr16Adapter->get($postKey);

                    if ($news->getFieldByTimestamp('timestamp', $item['timestamp'])) {
                        if (!count($items)) {
                            echo "[NEWS] Nothing new found\n";
                        }
                        break 2;
                    }

                    $items[] = $item;
                    echo '[NEWS] Loading from cache: ' . $item['title'] . "\n";
                }
            }
        }

        $db_count = $news->getItemCount();
        $count = count($items);
        foreach ($items as $item) {
            $item['id'] = $count + $db_count;
            $result[] = $item;
            --$count;
        }

        return $result;
    }

    public function templateNews($post): array
    {
        // URL
        $item['url'] = $post->find('a', 0)->href;

        // Post
        $post_html = (new HtmlWeb())->load($item['url']);

        if (!$post_html) {
            throw new RuntimeException('An error occurred trying to load ' . $item['url']);
        }

        // Title
        $search = ['&#8217;', '&#8211;', ' View Article', '&nbsp;', '’', '–', '\u00a0'];
        $replace = ['\'', '–', '', '', '\'', '-', ''];
        $item['title'] = $post_html->find('h1', 0)->plaintext;
        $item['title'] = str_replace($search, $replace, $item['title']);

        // Timestamp
        $item['timestamp'] = $post_html->find('meta[property=article:published_time]', 0)->content;
        $item['timestamp'] = strtotime($item['timestamp']);

        // Excerpt
        $item['excerpt'] = $post_html->find('meta[property=og:description]', 0)->content;
        $search = ['&#8217;', '&#8211;', ' View Article', '&nbsp;', '’', '–', "\xE2\x80\xA6", '&#8230;'];
        $replace = ['\'', '-', '', '', '\'', '-', '...', '...'];
        $excerpt = str_replace($search, $replace, $item['excerpt']);
        $item['excerpt'] = preg_replace('/\xc2\xa0/', ' ', $excerpt);

        // Image Large
        $search = ['http://'];
        $replace = ['https://'];
        $item['image'] = $post_html->find('meta[property=og:image]', 0)->content;
        $item['image'] = str_replace($search, $replace, $item['image']);

        // Image Small
        $search = ["'", 'background-image: url(', ');', 'http://'];
        $replace = ['', '', '', 'https://'];
        $item['image_small'] = $post->find('.background--cover', 0)->style;
        $item['image_small'] = str_replace($search, $replace, $item['image_small']);

        // Body
        $search = ['src=\"/wp-content', '&#8217;', '&#8211;', '&nbsp;', '’', '–', '\u00a0', "'", "\t", 'href=\"/', "\\\\'", "\t"];
        $replace = ['src=\"https://www.nomanssky.com/wp-content', '\'', '–', '', '\'', '-', '', '\'', '', 'href=\"https://www.nomanssky.com/', '\'', ''];
        $item['body'] = $post_html->find('//div[@class="box box--fill-height"]', 0)->innertext ?? '';
        $item['body'] = str_replace($search, $replace, $item['body']);
        $search = ['/<h1 class=\"text--heading-centered .*\">.*<\/h1>/', '/<div class=\"post-meta text--centered.*\">[\s\S].*\s<\/div>/'];
        $replace = ['', ''];
        $item['body'] = preg_replace($search, $replace, $item['body']);

        return $item;
    }
}