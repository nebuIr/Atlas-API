<?php

function getRelease($url, $category)
{
    $releases = new Releases();
    $html = file_get_html($url . $category);
    $posts = $html->find('div.grid__cell');
    $items = [];

    foreach ($posts as $post) {
        $item = templateReleases($post);

        if ($releases->getFieldByURL('url', $item['url'])) {
            if (!count($items)) {
                echo "No new releases found.\n";
            }
            break;
        }

        echo 'Item added to array: ' . $item['title'] . "\n";
        $items[] = $item;
    }

    $db_count = $releases->getItemCount();
    $count = count($items);
    $result = [];
    foreach ($items as $item) {
        $item['id'] = $count + $db_count;
        $result[] = $item;
        --$count;
    }

    return $result;
}

function templateReleases($post): array
{
    // URL
    $item['url'] = $post->find('a', 0)->href;
    $baseUri = 'www.nomanssky.com';
    $baseUriSSL = 'https://www.nomanssky.com';
    if (strpos($item['url'], $baseUri) === false) {
        $item['url'] = $baseUriSSL . $item['url'];
    }

    // Post
    $post_html = file_get_html($item['url']);

    // Title
    $search = ['&#8217;', '&#8211;', ' View Article', '&nbsp;', '’', '–', '\u00a0'];
    $replace = ['\'', '–', '', '', '\'', '-', ''];
    $item['title'] = $post->find('h2', 0)->plaintext;
    $item['title'] = str_replace($search, $replace, $item['title']);

    // Timestamp
    $item['timestamp'] = $post_html->find('meta[property=article:published_time]', 0)->content ?? 0;
    $item['timestamp'] = strtotime($item['timestamp']);

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

    //MICROSOFT STORE
    if ($post->find('div[style=margin-left:0;background-color:grey;]')) {
        $item['platforms']['ms-store'] = (int) true;
    } else {
        $item['platforms']['ms-store'] = (int) false;
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
    $item['image'] = $post_html->find('meta[property=og:image]', 0)->content;
    $item['image'] = str_replace($search, $replace, $item['image']);

    // Body
    $search = ['src=\"/wp-content', '&#8217;', '&#8211;', '&nbsp;', '’', '–', '\u00a0', "'", "\t", '     ', 'href=\"/', "\\\\'", "\t"];
    $replace = ['src=\"https://www.nomanssky.com/wp-content', '\'', '–', '', '\'', '-', '', '\'', '', '', 'href=\"https://www.nomanssky.com/', '\'', ''];
    $item['body'] = $post_html->find('//div[@class="box box--fill-height"]', 0)->innertext;
    $item['body'] = str_replace($search, $replace, $item['body']);
    $search = ['/<h1 class=\"text--heading-centered .*\">.*<\/h1>/', '/<div class=\"post-meta text--centered.*\">[\s\S].*\s<\/div>/'];
    $replace = ['', ''];
    $item['body'] = preg_replace($search, $replace, $item['body']);

    return $item;
}