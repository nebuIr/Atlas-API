<?php
require_once __DIR__ . '/../../../lib/simple_html_dom.php';

function getNews($url, $category, $latest_id = null, $single = true): array
{
    $page = 1;
    $items = [];
    $result = [];
    do {
        set_error_handler(
            function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
                // do not throw an exception if the @-operator is used (suppress)
                if (error_reporting() === 0) return false;
                throw new ErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
            },
            E_WARNING
        );
        try {
            $html = file_get_html($url . $category . '/page/' . $page);
            $posts = $html->find('article');

            if ($single) {
                return template($posts[0], $latest_id);
            }

            foreach ($posts as $post) {
                $items[] = template($post, null);
            }

            $page++;
        } catch (Exception $e) {
            $count = count($items);
            foreach ($items as $item) {
                $item['id'] = $count;
                $result[] = $item;
                --$count;
            }

            $page = false;
            break;
        }

        restore_error_handler();
    } while ($page);

    return $result;
}

function getTimestamp($url, $category): int
{
    $html = file_get_html($url . $category);
    $posts = $html->find('article', 0);
    $article_url = $posts->find('a', 0)->href;
    $article_html = file_get_html($article_url);

    return strtotime($article_html->find('meta[property=article:published_time]', 0)->content);
}

function getTitle($url, $category): string
{
    $html = file_get_html($url . $category);
    $posts = $html->find('article', 0);

    return trim($posts->find('h3', 0)->plaintext);
}

function template($post, $latest_id = null): array
{
    // ID
    if ($latest_id !== null) {
        $item['id'] = $latest_id + 1;
    }

    // URL
    $item['url'] = $post->find('a', 0)->href;

    // Post
    $post_html = file_get_html($item['url']);

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
    $item['body'] = $post_html->find('//div[@class="box box--fill-height"]', 0)->innertext;
    $item['body'] = str_replace($search, $replace, $item['body']);

    echo $item['title'] . "\n";
    return $item;
}