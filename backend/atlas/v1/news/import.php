<?php
if (PHP_SAPI !== 'cli') {
    throw new \RuntimeException('This application must be run on the command line.');
}

require __DIR__ . '/../../../lib/simple_html_dom.php';
header('Content-Type: application/json');
$posts_file = file_get_contents('posts.json');
$posts_json = json_decode($posts_file, true);
$latest_post = $posts_json[0];

$items = array();
$url = 'https://www.nomanssky.com/';
$category = 'news';
$page = 1;
$post_count = 1;
$error_string = 'error';

$latest_post_timestamp = strtotime(checkLatestPostTimestamp($url, $category));
$latest_post_timestamp_saved = trim($latest_post['timestamp']);
$latest_post_title = trim(checkLatestPostTitle($url, $category));
$latest_post_title_saved = trim($latest_post['title']);

if ((int)$latest_post_timestamp !== (int)$latest_post_timestamp_saved) {
    if (filesize('posts.json') === 0 || !file_exists('posts.json')) {
        echo "Starting initial import ...\n";
        fetchInitialPosts($url, $category, $page, $post_count, $error_string);
    } else {
        fetchNewPost($url, $category, $page, $post_count, $error_string);
    }
} else {
    echo "\n\n----- No new posts found. -----\n\nLatest post found (" . $latest_post_timestamp . '): ' . $latest_post_title . "\nLatest post saved (" . $latest_post_timestamp_saved . '): ' . $latest_post_title_saved . "\n\n";
}

function checkLatestPostTimestamp($url, $category)
{
    $html = file_get_html($url . $category);
    $posts = $html->find('article', 0);
    $article_url = $posts->find('a', 0)->href;
    $article_html = file_get_html($article_url);
    $timestamp = $article_html->find('meta[property=article:published_time]', 0)->content;
    return $timestamp;
}

function checkLatestPostTitle($url, $category)
{
    $html = file_get_html($url . $category);
    $posts = $html->find('article', 0);
    $title = $posts->find('h3', 0)->plaintext;
    return $title;
}

function fetchInitialPosts($url, $category, $page, $post_count, $error_string)
{
    echo "\n\n----- Import started! -----\n\n\n";
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

            echo "Starting import of page $page ...\n";

            foreach ($posts as $post) {
                $item['url'] = $post->find('a', 0)->href;
                $article_html = file_get_html($item['url']);
                $item['title'] = $article_html->find('h1', 0)->plaintext;
                $title_pattern = array('&#8217;', '&#8211;', ' View Article', '&nbsp;', '’', '–', '\u00a0');
                $title_replace = array('\'', '–', '', '', '\'', '-', '');
                $title = str_replace($title_pattern, $title_replace, $item['title']);
                $item['title'] = $title;
                $item['timestamp'] = $article_html->find('meta[property=article:published_time]', 0)->content;
                $timestamp = strtotime($item['timestamp']);
                $item['timestamp'] = $timestamp;
                $item['excerpt'] = $article_html->find('meta[property=og:description]', 0)->content;
                $body_pattern = array('&#8217;', '&#8211;', ' View Article', '&nbsp;', '’', '–', "\xE2\x80\xA6", '&#8230;');
                $body_replace = array('\'', '-', '', '', '\'', '-', '...', '...');
                $body_replace = str_replace($body_pattern, $body_replace, $item['excerpt']);
                $excerpt = preg_replace('/\xc2\xa0/', ' ', $body_replace);
                $item['excerpt'] = $excerpt;
                $item['image'] = $article_html->find('meta[property=og:image]', 0)->content;
                $image_large_pattern = array('http://');
                $image_large_replace = array('https://');
                $image_large = str_replace($image_large_pattern, $image_large_replace, $item['image']);
                $item['image'] = $image_large;
                $item['image_small'] = $post->find('.background--cover', 0)->style;
                $image_small_pattern = array("'", 'background-image: url(', ');', 'http://');
                $image_small_replace = array('', '', '', 'https://');
                $image_small = str_replace($image_small_pattern, $image_small_replace, $item['image_small']);
                $item['image_small'] = $image_small;
                $item['body'] = $article_html->find('//div[@class="box box--fill-height"]', 0)->innertext;
                $body_pattern = array('src=\"/wp-content');
                $body_replace = array('src=\"https://www.nomanssky.com/wp-content');
                $body = str_replace($body_pattern, $body_replace, $item['body']);
                $body_pattern = array('&#8217;', '&#8211;', '&nbsp;', '’', '–', '\u00a0', "'", "\t", 'href=\"/');
                $body_replace = array('\'', '–', '', '\'', '-', '', '\'', '', 'href=\"https://www.nomanssky.com/');
                $body = str_replace($body_pattern, $body_replace, $body);
                $body_pattern = array("\\\\'");
                $body_replace = array('\'');
                $body = str_replace($body_pattern, $body_replace, $body);
                $body_pattern = array("\t");
                $body_replace = array('');
                $body = str_replace($body_pattern, $body_replace, $body);
                $item['body'] = $body;
                $items[] = $item;

                echo "Post added: $title\n";
                $post_count++;
            }
            echo "Completed import of page $page!\n\n";
            $page++;
        } catch (Exception $e) {

            $output_items = [];
            $item_count = $post_count;
            foreach ($items as $item) {
                --$item_count;
                $item = ['id' => $item_count] + $item;
                $output_items[] = $item;
            }

            $export = fopen('posts.json', 'wb') or die('Unable to open file!');
            fwrite($export, json_encode($output_items));
            fclose($export);

            importPosts();

            $page_count = $page - 1;
            --$post_count;
            echo "\n----- Import successful! With a total of $page_count pages containing $post_count posts -----\n\n";
            echo $e->getMessage();
            break;
        }

        restore_error_handler();
    } while (!(strpos($item['title'], $error_string)));
}

function fetchNewPost($url, $category, $page, $post_count, $error_string)
{
    echo "\n\n----- Import started! -----\n\n\n";
    echo "New post found ...\n";
    $html = file_get_html($url . $category . '/page/' . $page);
    $posts = $html->find('article', 0);

    $posts_file = file_get_contents('posts.json');
    $posts_json = json_decode($posts_file, true);
    $latest_id = $posts_json[0]['id'];

    $item['id'] = $latest_id + 1;
    $item['url'] = $posts->find('a', 0)->href;
    $article_html = file_get_html($item['url']);
    $item['title'] = $article_html->find('h1', 0)->plaintext;
    $title_pattern = array('&#8217;', '&#8211;', ' View Article', '&nbsp;', '’', '–', '\u00a0');
    $title_replace = array('\'', '–', '', '', '\'', '-', '');
    $title = str_replace($title_pattern, $title_replace, $item['title']);
    $item['title'] = $title;
    $item['timestamp'] = $article_html->find('meta[property=article:published_time]', 0)->content;
    $timestamp = strtotime($item['timestamp']);
    $item['timestamp'] = $timestamp;
    $item['excerpt'] = $article_html->find('meta[property=og:description]', 0)->content;
    $body_pattern = array('&#8217;', '&#8211;', ' View Article', '&nbsp;', '’', '–', "\xE2\x80\xA6", '&#8230;');
    $body_replace = array('\'', '-', '', '', '\'', '-', '...', '...');
    $body_replace = str_replace($body_pattern, $body_replace, $item['excerpt']);
    $excerpt = preg_replace('/\xc2\xa0/', ' ', $body_replace);
    $item['excerpt'] = $excerpt;
    $item['image'] = $article_html->find('meta[property=og:image]', 0)->content;
    $image_large_pattern = array('http://');
    $image_large_replace = array('https://');
    $image_large = str_replace($image_large_pattern, $image_large_replace, $item['image']);
    $item['image'] = $image_large;
    $item['image_small'] = $posts->find('.background--cover', 0)->style;
    $image_small_pattern = array("'", "background-image: url(", ");", "http://");
    $image_small_replace = array("", "", "", "https://");
    $image_small = str_replace($image_small_pattern, $image_small_replace, $item['image_small']);
    $item['image_small'] = $image_small;
    $item['body'] = $article_html->find('//div[@class="box box--fill-height"]', 0)->innertext;
    $body_pattern = array('src=\"/wp-content');
    $body_replace = array('src=\"https://www.nomanssky.com/wp-content');
    $body = str_replace($body_pattern, $body_replace, $item['body']);
    $body_pattern = array('&#8217;', '&#8211;', '&nbsp;', '’', '–', '\u00a0', "'", '\t', 'href=\"/');
    $body_replace = array('\'', '–', '', '\'', '-', '', '\'', '', 'href=\"https://www.nomanssky.com/');
    $body = str_replace($body_pattern, $body_replace, $body);
    $body_pattern = array("\\\\'");
    $body_replace = array('\'');
    $body = str_replace($body_pattern, $body_replace, $body);
    $body_pattern = array("\t");
    $body_replace = array('');
    $body = str_replace($body_pattern, $body_replace, $body);
    $item['body'] = $body;
    $items[] = $item;

    echo "Post added: $title\n";

    $export_content = file_get_contents('posts.json');
    $export = fopen('posts.json', 'wb') or die('Unable to open file!');
    $tempArray = json_decode($export_content, true);
    array_unshift($tempArray, $item);
    fwrite($export, json_encode($tempArray));
    fclose($export);

    importPosts();

    echo "\n\n----- Import successful! -----\n\n";
}

function importPosts()
{
    include_once(__DIR__ . '/News.php');
    $News = new News;
    $News->SQLImport();
}