<?php

use \Dotenv\Dotenv;

class Version
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

        $query = "SELECT id, url, version, timestamp FROM version";
        $data = mysqli_query($connect, $query);
        $output = array();
        $return_arr = array();

        if (mysqli_num_rows($data) > 0) {
            while ($row = mysqli_fetch_assoc($data)) {
                $output['id'] = (int) $row['id'];
                $output['url'] = $row['url'];
                $output['version'] = $row['version'];
                $output['timestamp'] = $row['timestamp'];

                array_push($return_arr, $output);
            }
        }

        mysqli_close($connect);

        $output_file = fopen(__DIR__ . "/output.json", "w") or die("Unable to open file!");
        fwrite($output_file, json_encode($return_arr));
    }

    public function mainSqlUpdate()
    {
        $this->getJsonAllUpdate();
        echo 'Version updated!';
    }

    public function getJsonAllUpdate()
    {
        $connect = mysqli_connect("$this->db_host", "$this->db_user", "$this->db_pass", "$this->db_name");
        $url = __DIR__ . "/../../../../backend/atlas/v1/version/posts.json";
        $json = file_get_contents($url);
        $data = json_decode($json, true);
        $table = 'version';
        $result = $connect->query("SELECT * FROM ".$table);
        if ($result->num_rows) {
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

        $query = "UPDATE version SET url='" . $item["url"] . "', version='" . $item["version"] . "', timestamp='" . $item["timestamp"] . "' WHERE id='0'";
        mysqli_query($connect, $query);
        var_dump(mysqli_error_list($connect));
        mysqli_close($connect);

        $this->generateJson();
    }

    public function querySqlSet($item)
    {
        $connect = mysqli_connect("$this->db_host", "$this->db_user", "$this->db_pass", "$this->db_name");
        if (!$connect) {
            die("Connection failed: " . mysqli_connect_error());
        };

        $sql_set = "INSERT INTO version(url, version, timestamp) VALUES('" . $item["url"] . "', '" . $item["version"] . "', '" . $item["timestamp"] . "')";
        mysqli_query($connect, $sql_set);
        var_dump(mysqli_error_list($connect));
        mysqli_close($connect);

        $this->generateJson();
    }
}
