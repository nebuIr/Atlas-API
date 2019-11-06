<?php

use \Dotenv\Dotenv;

class Version
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

        $query = "SELECT id, url, version, date FROM version";
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

    public function mainSqlUpdate()
    {
        $this->getJsonAllUpdate();
        echo 'Version updated!';
    }

    public function getJsonAllUpdate()
    {
        $url = __DIR__ . "/../../../../backend/atlas/v1/version/posts.json";
        $json = file_get_contents($url);
        $data = json_decode($json, true);
        $table = 'version';
        if ($result = $this->connect->query("SELECT COUNT(1) FROM '".$table."'")) {
            if($result->num_rows == 1) {
                foreach ($data as $item) {
                    $this->querySqlUpdate($item);
                }
            }
        } else {
            foreach ($data as $item) {
                $this->querySqlSet($item);
            }
        }
    }

    public function querySqlUpdate($item)
    {
        $connect = mysqli_connect("$this->db_host", "$this->db_user", "$this->db_pass", "$this->db_name");
        if (!$connect) {
            die("Connection failed: " . mysqli_connect_error());
        }

        $query = "UPDATE version SET url='" . $item["url"] . "', version='" . $item["version"] . "', date='" . $item["date"] . "' WHERE id='0'";
        mysqli_query($connect, $query);
        var_dump(mysqli_error_list($connect));
        mysqli_close($connect);
    }

    public function querySqlSet($item)
    {
        $connect = mysqli_connect("$this->db_host", "$this->db_user", "$this->db_pass", "$this->db_name");
        if (!$connect) {
            die("Connection failed: " . mysqli_connect_error());
        };

        $sql_set = "INSERT INTO version(url, version, date) VALUES('" . $item["url"] . "', '" . $item["version"] . "', '" . $item["date"] . "')";
        mysqli_query($connect, $sql_set);
        var_dump(mysqli_error_list($connect));
        mysqli_close($connect);
    }
}
