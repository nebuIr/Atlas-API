<?php
require_once __DIR__ . '/../../../lib/simple_html_dom.php';

function getRelease($url, $category, $latest_id = null, $single = true)
{
    $html = file_get_html($url . $category);
    $posts = $html->find('div.grid__cell');

    if ($single) {
        return template($posts[0], $latest_id);
    }

    $items = [];
    foreach ($posts as $post) {
        $items[] = template($post, null);
    }

    $count = count($items);
    $result = [];
    foreach ($items as $item) {
        $item['id'] = $count;
        $result[] = $item;
        --$count;
    }

    return $result;
}

function getTitle($url, $category): string
{
    $html = file_get_html($url . $category);
    $posts = $html->find('div.grid__cell', 0);

    return trim($posts->find('h2', 0)->plaintext);
}

function template($post, $latest_id = null): array
{
    // ID
    if ($latest_id !== null) {
        $item['id'] = $latest_id + 1;
    }

    // URL
    $item['url'] = $post->find('a', 0)->href;
    $baseUri = 'www.nomanssky.com';
    $baseUriSSL = 'https://www.nomanssky.com';
    if (strpos($item['url'], $baseUri) === false) {
        $item['url'] = $baseUriSSL . $item['url'];
    }

    // Post
    $article_html = file_get_html($item['url']);

    // Title
    $search = ['&#8217;', '&#8211;', ' View Article', '&nbsp;', '’', '–', '\u00a0'];
    $replace = ['\'', '–', '', '', '\'', '-', ''];
    $item['title'] = $post->find('h2', 0)->plaintext;
    $item['title'] = str_replace($search, $replace, $item['title']);

    // Platforms
    //PC
    if ($post->find('div.platform--pc')) {
        $pc = $post->find('div.platform--pc', 0)->plaintext;
        if ($pc === 'PC') {
            $item['platforms']['pc'] = (int) true;
        } else {
            $item['platforms']['pc'] = (int) false;
        }
    } else {
        $item['platforms']['pc'] = (int) false;
    }

    //PS4
    if ($post->find('div.platform--ps4')) {
        $item['platforms']['ps4'] = (int) true;
    } else {
        $item['platforms']['ps4'] = (int) false;
    }

    //XBOX
    if ($post->find('div[style=margin-left:0;background-color:green;]')) {
        $item['platforms']['xbox'] = (int) true;
    } else {
        $item['platforms']['xbox'] = (int) false;
    }

    // Excerpt
    $search = ['&#8217;', '&#8211;', "\r\n             Read more", "\r\n          Read more", "&nbsp;", "’", "–", "\xE2\x80\xA6", "&#8230;", "            ", "           "];
    $replace = ['’', '-', '', '', '', '\'', '-', '...', '...', '', ''];
    $item['excerpt'] = $post->find('p', 0)->plaintext;
    $excerpt = str_replace($search, $replace, $item['excerpt']);
    $item['excerpt'] = preg_replace('/\xc2\xa0/', ' ', $excerpt);

    // Image
    $search = ['http://'];
    $replace = ['https://'];
    $item['image'] = $article_html->find('meta[property=og:image]', 0)->content;
    $item['image'] = str_replace($search, $replace, $item['image']);

    // Body
    $search = ['src=\"/wp-content', '&#8217;', '&#8211;', '&nbsp;', '’', '–', '\u00a0', "'", "\t", '     ', 'href=\"/', "\\\\'", "\t"];
    $replace = ['src=\"https://www.nomanssky.com/wp-content', '\'', '–', '', '\'', '-', '', '\'', '', '', 'href=\"https://www.nomanssky.com/', '\'', ''];
    $item['body'] = $article_html->find('//div[@class="box box--fill-height"]', 0)->innertext;
    $item['body'] = str_replace($search, $replace, $item['body']);

    echo $item['title'] . "\n";
    return $item;
}