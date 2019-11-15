<?php

use \Dotenv\Dotenv;

class News
{
    public function __construct() {
        require_once __DIR__ . '/../../../../vendor/autoload.php';

        $dotenv = Dotenv::create(__DIR__ . '/../../../../');
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

        $query = "SELECT id, url, title, timestamp, excerpt, image, image_small, body FROM news ORDER BY id DESC";
        $data = mysqli_query($connect, $query);
        $output = array();
        $return_arr = array();

        if (mysqli_num_rows($data) > 0) {
            while ($row = mysqli_fetch_assoc($data)) {
                $output['id'] = (int) $row['id'];
                $output['url'] = $row['url'];
                $output['title'] = $row['title'];
                $output['date'] = $timestamp = date('Y-m-d h:m:s A', $row['timestamp']);
                $output['images']['image_large'] = $row['image'];
                $output['images']['image_small'] = $row['image_small'];
                $output['excerpt'] = $row['excerpt'];
                $output['body'] = $row['body'];

                array_push($return_arr, $output);
            }
        }

        mysqli_close($connect);

        $output_file = fopen(__DIR__ . "/output.json", "w") or die("Unable to open file!");
        fwrite($output_file, json_encode($return_arr));
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
        $connect = mysqli_connect("$this->db_host", "$this->db_user", "$this->db_pass", "$this->db_name");
        if (!$connect) {
            die("Connection failed: " . mysqli_connect_error());
        }

        $query = "INSERT INTO news (url, title, timestamp, excerpt, image, image_small, body) 
					SELECT d.*
					FROM (SELECT
							'" . $item["url"] . "', '" . $item["title"] . "', '" . $item["timestamp"] . "', '" . $item["excerpt"] . "', '" . $item["image"] . "', '" . $item["image_small"] . "' AS img_small, '" . $item["body"] . "') AS d
					WHERE 0 IN (SELECT COUNT(*)
					FROM news WHERE url='" . $item["url"] . "' AND title='" . $item["title"] . "')";
        mysqli_query($connect, $query);
        var_dump(mysqli_error_list($connect));
        mysqli_close($connect);
    }

    public function querySqlUpdate($item)
    {
        $connect = mysqli_connect("$this->db_host", "$this->db_user", "$this->db_pass", "$this->db_name");
        if (!$connect) {
            die("Connection failed: " . mysqli_connect_error());
        }

        $query = "UPDATE news SET url='" . $item["url"] . "', title='" . $item["title"] . "', excerpt='" . $item["excerpt"] . "', image='" . $item["image"] . "', image_small='" . $item["image_small"] . "', body='" . $item["body"] . "' WHERE timestamp='" . $item["timestamp"] . "'";
        mysqli_query($connect, $query);
        var_dump(mysqli_error_list($connect));
        mysqli_close($connect);

        $this->generateJson();
    }
}