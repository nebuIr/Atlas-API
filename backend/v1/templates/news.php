<?php

function getNews($url, $category): array
{
    $news = new News();
    $page = 1;
    $items = [];
    $result = [];
    $end_reached = false;
    do {
        set_error_handler(
            static function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
                if (error_reporting() === 0) {
                    return false;
                }
                throw new ErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
            },
            E_WARNING
        );
        try {
            $html = file_get_html($url . $category . '/page/' . $page);
            $posts = $html->find('article');

            foreach ($posts as $post) {
                $item = templateNews($post);

                if ($news->getFieldByTimestamp('timestamp', $item['timestamp'])) {
                    if (!count($items)) {
                        echo "No new news found.\n";
                    }
                    throw new Exception('Item already in DB');
                }

                echo 'Item added to array: ' . $item['title'] . "\n";
                $items[] = $item;
            }

            $page++;
        } catch (Exception $e) {
            $db_count = $news->getItemCount();
            $count = count($items);
            foreach ($items as $item) {
                $item['id'] = $count + $db_count;
                $result[] = $item;
                --$count;
            }

            $end_reached = true;
            break;
        }

        restore_error_handler();
    } while (!$end_reached);

    return $result;
}

function templateNews($post): array
{
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

    return $item;
}