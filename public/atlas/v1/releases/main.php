<?php

use \Dotenv\Dotenv;

class Releases
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

        $query = "SELECT id, url, title, platform_pc, platform_ps4, platform_xbox, teaser, image, content FROM releases ORDER BY id DESC";
        $data = mysqli_query($connect, $query);
        $output = array();
        $return_arr = array();

        if (mysqli_num_rows($data) > 0) {
            while ($row = mysqli_fetch_assoc($data)) {
                $output['id'] = $row['id'];
                $output['url'] = $row['url'];
                $output['title'] = $row['title'];
                $output['platforms']['pc'] = $row['platform_pc'];
                $output['platforms']['ps4'] = $row['platform_ps4'];
                $output['platforms']['xbox'] = $row['platform_xbox'];
                $output['teaser'] = $row['teaser'];
                $output['image'] = $row['image'];
                $output['content'] = $row['content'];

                array_push($return_arr, $output);
            }
        }

        mysqli_close($connect);

        header('Content-Type: application/json');
        echo json_encode($return_arr);
    }

    public function mainSql()
    {
        $this->getJson();
        echo 'Releases updated!';
    }

    public function getJson()
    {
        $url = __DIR__ . "/../../../../backend/atlas/v1/releases/posts.json";
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

        $query = "INSERT INTO releases (url, title, platform_pc, platform_ps4, platform_xbox, teaser, image, content) 
					SELECT d.*
					FROM (SELECT
							'" . $item["url"] . "', '" . $item["title"] . "', '" . $item["platforms"]["pc"] . "' AS pc, '" . $item["platforms"]["ps4"] . "' AS ps4, '" . $item["platforms"]["xbox"] . "'AS xbox, '" . $item["teaser"] . "', '" . $item["image"] . "', '" . $item["content"] . "') AS d
					WHERE 0 IN (SELECT COUNT(*)
					FROM releases WHERE url='" . $item["url"] . "' AND title='" . $item["title"] . "')";
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

        $query = "UPDATE releases SET url='" . $item["url"] . "', title='" . $item["title"] . "', platform_pc='" . $item["platforms"]["pc"] . "', platform_ps4='" . $item["platforms"]["ps4"] . "', platform_xbox='" . $item["platforms"]["xbox"] . "', teaser='" . $item["teaser"] . "', image='" . $item["image"] . "', content='" . $item["content"] . "' WHERE url='" . $item["url"] . "'";
        mysqli_query($connect, $query);
        var_dump(mysqli_error_list($connect));
        mysqli_close($connect);
    }
}