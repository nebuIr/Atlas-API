<?php

use \Dotenv\Dotenv;

class News
{
    public function __construct() {
        require_once __DIR__ . '/../../../../vendor/autoload.php';

        $dotenv = Dotenv::create(__DIR__ . '/../../../');
        $dotenv->load();

        $this->db_host = getenv('DB_HOST');
        $this->db_name = getenv('DB_NAME');
        $this->db_user = getenv('DB_USER');
        $this->db_pass = getenv('DB_PASS');
    }

    public function generateJson()
    {
        $connect = mysqli_connect("$this->db_host", "$this->db_user", "$this->db_pass", "$this->db_name");
        if (!$connect) {
            die("Connection failed: " . mysqli_connect_error());
        }

        $query = "SELECT id, url, title, timestamp, date, teaser, image, image_small, content FROM news ORDER BY id DESC";
        $data = mysqli_query($connect, $query);
        $output = array();

        if (mysqli_num_rows($data) > 0) {
            while ($row = mysqli_fetch_assoc($data)) {
                $output[]=$row;
            }
        }

        mysqli_close($connect);

        header('Content-Type: application/json');
        echo json_encode($output);
    }

    public function mainSql()
    {
        $this->getJson();
        echo 'News updated!';
    }

    public function getJson()
    {
        $url = __DIR__ . "/../../../../backend/atlas/v1/news/posts.json";
        $json = file_get_contents($url);
        $data = json_decode($json, true);
        foreach ($data as $item) {
            $this->querySql($item);
        }
    }

    public function querySql($item)
    {
        $this->querySqlSet($item);
        $this->querySqlUpdate($item);
    }

    public function querySqlSet($item)
    {
        $timestamp = date('F d\, Y \a\t h:iA', $item['timestamp']);
        $connect = mysqli_connect("$this->db_host", "$this->db_user", "$this->db_pass", "$this->db_name");
        if (!$connect) {
            die("Connection failed: " . mysqli_connect_error());
        }

        $query = "INSERT INTO news (url, title, timestamp, date, teaser, image, image_small, content) 
					SELECT d.*
					FROM (SELECT
							'" . $item["url"] . "', '" . $item["title"] . "', '" . $item["timestamp"] . "', '" . $timestamp . "', '" . $item["teaser"] . "', '" . $item["image"] . "', '" . $item["image_small"] . "' AS img_small, '" . $item["content"] . "') AS d
					WHERE 0 IN (SELECT COUNT(*)
					FROM news WHERE url='" . $item["url"] . "' AND title='" . $item["title"] . "')";
        mysqli_query($connect, $query);
        var_dump(mysqli_error_list($connect));
        mysqli_close($connect);
    }

    public function querySqlUpdate($item)
    {
        $timestamp = date('F d\, Y \a\t h:iA', $item['timestamp']);
        $connect = mysqli_connect("$this->db_host", "$this->db_user", "$this->db_pass", "$this->db_name");
        if (!$connect) {
            die("Connection failed: " . mysqli_connect_error());
        }

        $query = "UPDATE news SET url='" . $item["url"] . "', title='" . $item["title"] . "', date='" . $timestamp . "', teaser='" . $item["teaser"] . "', image='" . $item["image"] . "', image_small='" . $item["image_small"] . "', content='" . $item["content"] . "' WHERE timestamp='" . $item["timestamp"] . "'";
        mysqli_query($connect, $query);
        var_dump(mysqli_error_list($connect));
        mysqli_close($connect);
    }
}