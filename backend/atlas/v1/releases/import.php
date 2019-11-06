<?php
if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

require __DIR__ . "/../../../lib/simple_html_dom.php";
header('Content-Type: application/json');
$latest_post_json = file_get_contents("latest.json");
$latest_post = json_decode($latest_post_json, true);

$items = array();
$url = 'https://www.nomanssky.com/';
$category = 'release-log';
$post_count = 1;
$error_string = "error";

$latest_post_title = checkLatestPost($url, $category);

if (trim($latest_post_title) != trim($latest_post['title'])) {
    if (filesize("posts.json") == 0) {
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
        $titlePattern = array("&#8217;", "&#8211;", " View Article", "&nbsp;", "’", "–", '\u00a0');
        $titleReplace = array("\'", "–", "", "", "\'", "-", "");
        $title = str_replace($titlePattern, $titleReplace, $item['title']);
        $item['title'] = $title;
        if ($post->find('div.platform--pc') != null) {
            $pc = $post->find('div.platform--pc', 0)->plaintext;
            if ($pc == 'PC') {
                $item['platforms']['pc'] = true;
            } else {
                $item['platforms']['pc'] = false;
            }
        } else {
            $item['platforms']['pc'] = false;
        }
        if ($post->find('div.platform--ps4') != null) {
            $item['platforms']['ps4'] = true;
        } else {
            $item['platforms']['ps4'] = false;
        }
        if ($post->find('div[style=margin-left:0;background-color:green;]') != null) {
            $item['platforms']['xbox'] = true;
        } else {
            $item['platforms']['xbox'] = false;
        }
        $item['teaser'] = $post->find('p', 0)->plaintext;
        $teaserPattern = array("&#8217;", "&#8211;", "\r\n             Read more", "\r\n          Read more", "&nbsp;", "’", "–", "\xE2\x80\xA6", "&#8230;", "            ", "           ");
        $teaserReplace = array("’", "-", "", "", "", "\'", "-", "...", "...", "", "");
        $teaser_replace = str_replace($teaserPattern, $teaserReplace, $item['teaser']);
        $teaser = preg_replace('/\xc2\xa0/', ' ', $teaser_replace);
        $item['teaser'] = $teaser;
        $item['image'] = $article_html->find('meta[property=og:image]', 0)->content;
        $item['content'] = $article_html->find('//div[@class="box box--fill-height"]', 0)->innertext;
        $contentPattern = array("src=\"/wp-content");
        $contentReplace = array("src=\"https://www.nomanssky.com/wp-content");
        $content = str_replace($contentPattern, $contentReplace, $item['content']);
        $contentPattern = array("&#8217;", "&#8211;", "&nbsp;", "’", "–", '\u00a0', "'", "\t", "     ", "href=\"/");
        $contentReplace = array("\'", "–", "", "\'", "-", "", "\'", "", "", "href=\"https://www.nomanssky.com/");
        $content = str_replace($contentPattern, $contentReplace, $content);
        $contentPattern = array("\\\\'");
        $contentReplace = array("\'");
        $content = str_replace($contentPattern, $contentReplace, $content);
        $item['content'] = $content;
        $items[] = $item;

        echo "Added post: $title\n";
        $post_count++;
    }
    echo "Completed import of releases\n\n";
    $export = fopen("posts.json", "w") or die("Unable to open file!");
    fwrite($export, json_encode(array_reverse($items)));

    $latest_post = fopen("latest.json", "w") or die("Unable to open file!");
    fwrite($latest_post, json_encode($items[0]));

    handler();

    $post_count = $post_count - 1;
    echo "\n----- Import successful! With a total of $post_count posts -----\n\n";
}

function updatePosts($url, $category, $post_count, $error_string) {
    echo "\n\n----- Import started! -----\n\n\n";
    $html = file_get_html($url . $category);
    $posts = $html->find('div.grid__cell', 0);

    $item['url'] = $posts->find('a', 0)->href;
    $baseUri = 'www.nomanssky.com';
    $baseUriSsl = 'https://www.nomanssky.com';
    if (strpos($item['url'], $baseUri) === false) {
        $url = $baseUriSsl . $item['url'];
        $item['url'] = $url;
    }
    $article_html = file_get_html($item['url']);
    $item['title'] = $posts->find('h2', 0)->plaintext;
    $titlePattern = array("&#8217;", "&#8211;", " View Article", "&nbsp;", "’", "–", '\u00a0');
    $titleReplace = array("\'", "–", "", "", "\'", "-", "");
    $title = str_replace($titlePattern, $titleReplace, $item['title']);
    $item['title'] = $title;
    if ($posts->find('div.platform--pc') != null) {
        $pc = $posts->find('div.platform--pc', 0)->plaintext;
        if ($pc == 'PC') {
            $item['platforms']['pc'] = true;
        } else {
            $item['platforms']['pc'] = false;
        }
    } else {
        $item['platforms']['pc'] = false;
    }
    if ($posts->find('div.platform--ps4') != null) {
        $item['platforms']['ps4'] = true;
    } else {
        $item['platforms']['ps4'] = false;
    }
    if ($posts->find('div[style=margin-left:0;background-color:green;]') != null) {
        $item['platforms']['xbox'] = true;
    } else {
        $item['platforms']['xbox'] = false;
    }
    $item['teaser'] = $posts->find('p', 0)->plaintext;
    $teaserPattern = array("&#8217;", "&#8211;", "\r\n             Read more", "\r\n          Read more", "&nbsp;", "’", "–", "\xE2\x80\xA6", "&#8230;", "            ", "           ");
    $teaserReplace = array("’", "-", "", "", "", "\'", "-", "...", "...", "", "");
    $teaser_replace = str_replace($teaserPattern, $teaserReplace, $item['teaser']);
    $teaser = preg_replace('/\xc2\xa0/', ' ', $teaser_replace);
    $item['teaser'] = $teaser;
    $item['image'] = $article_html->find('meta[property=og:image]', 0)->content;
    $item['content'] = $article_html->find('//div[@class="box box--fill-height"]', 0)->innertext;
    $contentPattern = array("src=\"/wp-content");
    $contentReplace = array("src=\"https://www.nomanssky.com/wp-content");
    $content = str_replace($contentPattern, $contentReplace, $item['content']);
    $contentPattern = array("&#8217;", "&#8211;", "&nbsp;", "’", "–", '\u00a0', "'", "\t", "     ", "href=\"/");
    $contentReplace = array("\'", "–", "", "\'", "-", "", "\'", "", "", "href=\"https://www.nomanssky.com/");
    $content = str_replace($contentPattern, $contentReplace, $content);
    $contentPattern = array("\\\\'");
    $contentReplace = array("\'");
    $content = str_replace($contentPattern, $contentReplace, $content);
    $item['content'] = $content;
    $items[] = $item;

    echo "Added post: $title\n";

    $export_content = file_get_contents('posts.json');
    $export = fopen("posts.json", "w") or die("Unable to open file!");
    $tempArray = json_decode($export_content, true);
    array_unshift($tempArray, $item);
    fwrite($export, json_encode($tempArray));

    $latest_post = fopen("latest.json", "w") or die("Unable to open file!");
    fwrite($latest_post, json_encode($item)) . ',';

    handler();
    sendNotification();

    echo "\n\n----- Import successful! -----\n\n";
}

function handler() {
    include_once(__DIR__ . "/../../../../public/atlas/v1/releases/main.php");
    $Releases = new Releases();
    $Releases->mainSql();
}

function sendNotification() {
    $output = shell_exec('/usr/bin/nodejs '.__DIR__.'/../../notifications/send_notification_releases.js');
    echo $output;
}