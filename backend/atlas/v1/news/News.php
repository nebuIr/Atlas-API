<?php

use \Dotenv\Dotenv;

class News
{
    /**
     * @var mysqli
     */
    private $conn;

    public function __construct()
    {
        require_once __DIR__ . '/../../../../vendor/autoload.php';

        $dotenv = Dotenv::create(__DIR__ . '/../../../../');
        $dotenv->load();

        $db_host = getenv('DB_HOST');
        $db_name = getenv('DB_NAME');
        $db_user = getenv('DB_USER');
        $db_pass = getenv('DB_PASS');

        $this->conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if (!$this->conn) {
            die('Connection failed: ' . $this->conn->connect_error);
        }
    }

    public function getSQL($order = 'DESC')
    {
        $stmt = $this->conn->prepare("SELECT * FROM news ORDER BY id $order");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        return $result;
    }

    public function getSQLbyId($id)
    {
        $stmt = $this->conn->prepare('SELECT * FROM news WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        return $result;
    }

    public function getJSONFromSQL($sql_result, $latest, $params)
    {
        $output = [];
        $return_arr = [];

        if ($sql_result->num_rows > 0) {
            while ($row = $sql_result->fetch_assoc()) {
                $output['id'] = (int) $row['id'];
                $output['url'] = $row['url'];
                $output['title'] = $row['title'];
                $output['timestamp'] = $row['timestamp'];
                $output['images']['image_large'] = $row['image'];
                $output['images']['image_small'] = $row['image_small'];
                $output['excerpt'] = $row['excerpt'];
                $output['body'] = $row['body'];

                $return_arr[] = $output;
            }
        }

        $array_length = count($return_arr);

        if ($latest) {
            return $return_arr[0];
        }

        if (!array_key_exists('offset', $params) || $params['offset'] < 0) {
            $params['offset'] = 0;
        }

        if (!array_key_exists('limit', $params) || $params['limit'] <= 0) {
            $params['limit'] = $array_length;
        }

        return array_slice($return_arr, 0 + $params['offset'], $params['limit']);
    }

    public function writeJSONFile($json_array) {
        $output_file = fopen(__DIR__ . '/output.json', 'wb') or die('Unable to open file!');
        fwrite($output_file, json_encode($json_array));
    }

    public function SQLImport()
    {
        foreach ($this->getJsonFile() as $item) {
            $this->runSQLImport($item);
        }

        echo 'News updated!';
    }

    public function getJsonFile()
    {
        $url = __DIR__ . '/posts.json';
        $json = file_get_contents($url);

        return json_decode($json, true);
    }

    public function runSQLImport($item)
    {
        $this->addSQLEntries($item);
        $this->updateSQLEntries($item);
    }

    public function addSQLEntries($item)
    {
        $stmt = $this->conn->prepare('INSERT INTO news (id, url, title, timestamp, excerpt, image, image_small, body) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ississss', $item['id'], $item['url'], $item['title'], $item["timestamp"], $item['excerpt'], $item['image'], $item['image_small'], $item['body']);
        $stmt->execute();
    }

    public function updateSQLEntries($item)
    {
        $stmt = $this->conn->prepare('UPDATE news SET url=?, title=?, excerpt=?, image=?, image_small=?, body=? WHERE id=?');
        $stmt->bind_param('ssssssi', $item['url'], $item['title'], $item['excerpt'], $item['image'], $item['image_small'], $item['body'], $item['id']);
        $stmt->execute();
    }
}