<?php
if (PHP_SAPI !== 'cli') {
    throw new \RuntimeException('This application must be run on the command line.');
}

require __DIR__ . '/../../../lib/simple_html_dom.php';
header('Content-Type: application/json');
$latest_post_json = file_get_contents('latest.json');
$latest_post = json_decode($latest_post_json, true);

$items = array();
$url = 'https://www.nomanssky.com/';
$category = 'release-log';
$post_count = 1;
$error_string = 'error';

$latest_post_title = checkLatestPost($url, $category);

if (trim($latest_post_title) !== trim($latest_post['title'])) {
    if (!file_exists('posts.json')) {
        initializePosts($url, $category, $post_count, $error_string);
    } else {
        updatePosts($url, $category, $post_count, $error_string);
    }
} else {
    echo "\n\n----- No new posts found. -----\n\nLatest post found: " . trim($latest_post_title) . "\nLatest post saved: " . trim($latest_post['title']) . "\n\n";
}

function checkLatestPost($url, $category) {
    $html = file_get_html($url . $category);
    $posts = $html->find('div.grid__cell', 0);
    $title = $posts->find('h2', 0)->plaintext;
    return $title;
}

function initializePosts($url, $category, $post_count, $error_string) {
    echo "\n\n----- Import started! -----\n\n\n";

    $html = file_get_html($url . $category);
    $posts = $html->find('div.grid__cell');

    echo "Started import of releases\n";

    foreach ($posts as $post) {
        $item['url'] = $post->find('a', 0)->href;
        $baseUri = 'www.nomanssky.com';
        $baseUriSsl = 'https://www.nomanssky.com';
        if (strpos($item['url'], $baseUri) === false) {
            $url = $baseUriSsl . $item['url'];
            $item['url'] = $url;
        }
        $article_html = file_get_html($item['url']);
        $item['title'] = $post->find('h2', 0)->plaintext;
        $titlePattern = array('&#8217;', '&#8211;', ' View Article', '&nbsp;', '’', '–', '\u00a0');
        $titleReplace = array('\'', '–', '', '', '\'', '-', '');
        $title = str_replace($titlePattern, $titleReplace, $item['title']);
        $item['title'] = $title;
        if ($post->find('div.platform--pc') !== null) {
            $pc = $post->find('div.platform--pc', 0)->plaintext;
            if ($pc === 'PC') {
                $item['platforms']['pc'] = true;
            } else {
                $item['platforms']['pc'] = false;
            }
        } else {
            $item['platforms']['pc'] = false;
        }
        if ($post->find('div.platform--ps4') !== null) {
            $item['platforms']['ps4'] = true;
        } else {
            $item['platforms']['ps4'] = false;
        }
        if ($post->find('div[style=margin-left:0;background-color:green;]') !== null) {
            $item['platforms']['xbox'] = true;
        } else {
            $item['platforms']['xbox'] = false;
        }
        $item['excerpt'] = $post->find('p', 0)->plaintext;
        $excerptPattern = array('&#8217;', '&#8211;', "\r\n             Read more", "\r\n          Read more", "&nbsp;", "’", "–", "\xE2\x80\xA6", "&#8230;", "            ", "           ");
        $excerptReplace = array('’', '-', '', '', '', '\'', '-', '...', '...', '', '');
        $excerpt_replace = str_replace($excerptPattern, $excerptReplace, $item['excerpt']);
        $excerpt = preg_replace('/\xc2\xa0/', ' ', $excerpt_replace);
        $item['excerpt'] = $excerpt;
        $item['image'] = $article_html->find('meta[property=og:image]', 0)->content;
		$image_large_pattern = array('http://');
		$image_large_replace = array('https://');
		$image_large = str_replace($image_large_pattern, $image_large_replace, $item['image']);
		$item['image'] = $image_large;
        $item['body'] = $article_html->find('//div[@class="box box--fill-height"]', 0)->innertext;
        $bodyPattern = array('src=\"/wp-content');
        $bodyReplace = array('src=\"https://www.nomanssky.com/wp-content');
        $body = str_replace($bodyPattern, $bodyReplace, $item['body']);
        $bodyPattern = array('&#8217;', '&#8211;', '&nbsp;', '’', '–', '\u00a0', "'", "\t", '     ', 'href=\"/');
        $bodyReplace = array('\'', '–', '', '\'', '-', '', '\'', '', '', 'href=\"https://www.nomanssky.com/');
        $body = str_replace($bodyPattern, $bodyReplace, $body);
        $bodyPattern = array("\\\\'");
        $bodyReplace = array('\'');
        $body = str_replace($bodyPattern, $bodyReplace, $body);
        $item['body'] = $body;
        $items[] = $item;

        echo "Added post: $title\n";
        $post_count++;
    }

    foreach ($items as $item) {
		--$post_count;
    	$item = ['id' => $post_count] + $item;
    	$output_items[] = $item;
	}

    echo "Completed import of releases\n\n";
    $export = fopen('posts.json', 'wb') or die('Unable to open file!');
    fwrite($export, json_encode($output_items));

    $latest_post = fopen('latest.json', 'wb') or die('Unable to open file!');
    fwrite($latest_post, json_encode($output_items[0]));

    handler();

    --$post_count;
    echo "\n----- Import successful! With a total of $post_count posts -----\n\n";
}

function updatePosts($url, $category, $post_count, $error_string) {
    echo "\n\n----- Import started! -----\n\n\n";
    $html = file_get_html($url . $category);
    $posts = $html->find('div.grid__cell', 0);

	$posts_file = file_get_contents('posts.json');
	$posts_json = json_decode($posts_file, true);
	$latest_id = $posts_json[0]['id'];

    $item['id'] = $latest_id + 1;
    $item['url'] = $posts->find('a', 0)->href;
    $baseUri = 'www.nomanssky.com';
    $baseUriSsl = 'https://www.nomanssky.com';
    if (strpos($item['url'], $baseUri) === false) {
        $url = $baseUriSsl . $item['url'];
        $item['url'] = $url;
    }
    $article_html = file_get_html($item['url']);
    $item['title'] = $posts->find('h2', 0)->plaintext;
    $titlePattern = array('&#8217;', '&#8211;', ' View Article', '&nbsp;', '’', '–', '\u00a0');
    $titleReplace = array('\'', '–', '', '', '\'', '-', '');
    $title = str_replace($titlePattern, $titleReplace, $item['title']);
    $item['title'] = $title;
    if ($posts->find('div.platform--pc') !== null) {
        $pc = $posts->find('div.platform--pc', 0)->plaintext;
        if ($pc === 'PC') {
            $item['platforms']['pc'] = true;
        } else {
            $item['platforms']['pc'] = false;
        }
    } else {
        $item['platforms']['pc'] = false;
    }
    if ($posts->find('div.platform--ps4') !== null) {
        $item['platforms']['ps4'] = true;
    } else {
        $item['platforms']['ps4'] = false;
    }
    if ($posts->find('div[style=margin-left:0;background-color:green;]') !== null) {
        $item['platforms']['xbox'] = true;
    } else {
        $item['platforms']['xbox'] = false;
    }
    $item['excerpt'] = $posts->find('p', 0)->plaintext;
    $excerptPattern = array('&#8217;', '&#8211;', "\r\n             Read more", "\r\n          Read more", '&nbsp;', '’', '–', "\xE2\x80\xA6", '&#8230;', '            ', '           ');
    $excerptReplace = array('’', '-', '', '', '', '\'', '-', '...', '...', '', '');
    $excerpt_replace = str_replace($excerptPattern, $excerptReplace, $item['excerpt']);
    $excerpt = preg_replace('/\xc2\xa0/', ' ', $excerpt_replace);
    $item['excerpt'] = $excerpt;
    $item['image'] = $article_html->find('meta[property=og:image]', 0)->content;
	$image_large_pattern = array('http://');
	$image_large_replace = array('https://');
	$image_large = str_replace($image_large_pattern, $image_large_replace, $item['image']);
	$item['image'] = $image_large;
    $item['body'] = $article_html->find('//div[@class="box box--fill-height"]', 0)->innertext;
    $bodyPattern = array('src=\"/wp-content');
    $bodyReplace = array('src=\"https://www.nomanssky.com/wp-content');
    $body = str_replace($bodyPattern, $bodyReplace, $item['body']);
    $bodyPattern = array('&#8217;', '&#8211;', '&nbsp;', '’', '–', '\u00a0', "'", "\t", '     ', 'href=\"/');
    $bodyReplace = array('\'', '–', '', "\'", '-', '', "\'", '', '', 'href=\"https://www.nomanssky.com/');
    $body = str_replace($bodyPattern, $bodyReplace, $body);
    $bodyPattern = array("\\\\'");
    $bodyReplace = array('\'');
    $body = str_replace($bodyPattern, $bodyReplace, $body);
    $item['body'] = $body;
    $items[] = $item;

    echo "Added post: $title\n";

    $export_content = file_get_contents('posts.json');
    $export = fopen('posts.json', 'wb') or die('Unable to open file!');
    $tempArray = json_decode($export_content, true);
    array_unshift($tempArray, $item);
    fwrite($export, json_encode($tempArray));

    $latest_post = fopen('latest.json', 'wb') or die('Unable to open file!');
    fwrite($latest_post, json_encode($item)) . ',';

    handler();
    sendNotification();

    echo "\n\n----- Import successful! -----\n\n";
}

function handler() {
    include_once(__DIR__ . '/../../../../public/atlas/v1/releases/main.php');
    $Releases = new Releases();
    $Releases->mainSql();
}

function sendNotification() {
    $output = shell_exec('/usr/bin/nodejs '.__DIR__.'/../../notifications/send_notification_releases.js');
    echo $output;
}