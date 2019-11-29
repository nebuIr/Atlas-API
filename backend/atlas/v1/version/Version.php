<?php

use \Dotenv\Dotenv;

class Version
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

    public function generateJSONFromSQL()
    {
        $stmt = $this->conn->prepare('SELECT id, url, version, timestamp FROM version');
        $stmt->execute();
        $result = $stmt->get_result();

        $output = array();
        $return_arr = array();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output['id'] = (int)$row['id'];
                $output['url'] = $row['url'];
                $output['version'] = $row['version'];
                $output['timestamp'] = $row['timestamp'];

                $return_arr[] = $output;
            }
        }

        return $return_arr;
    }

    public function writeJSONFile($json_array) {
        $output_file = fopen(__DIR__ . '/../../../../public/atlas/v1/version/output.json', 'wb') or die('Unable to open file!');
        fwrite($output_file, json_encode($json_array));
    }

    public function SQLImport()
    {
        foreach ($this->getJsonFile() as $item) {
            $this->runSQLImport($item);
        }
        echo 'Version updated!';
    }

    public function getJsonFile()
    {
        $url = __DIR__ . '/posts.json';
        $json = file_get_contents($url);

        return json_decode($json, true);
    }

    public function runSQLImport($item)
    {
        $stmt = $this->conn->prepare('SELECT * FROM version');
        $stmt->execute();
        $result = $stmt->get_result();
        $row_count = $result->num_rows;

        $stmt->close();

        if ($row_count) {
            if ($row_count === 1) {
                $this->updateSQLEntries($item);
            }
        } else {
            $this->addSQLEntries($item);
        }
    }

    public function addSQLEntries($item)
    {
        $stmt = $this->conn->prepare('INSERT INTO version (id, url, version, timestamp) VALUES (?, ?, ?)');
        $stmt->bind_param('issi', $item['id'], $item['url'], $item['version'], $item['timestamp']);
        $stmt->execute();

        $this->writeJSONFile($this->generateJSONFromSQL());
    }

    public function updateSQLEntries($item)
    {
        $stmt = $this->conn->prepare('UPDATE version SET url=?, version=?, timestamp=? WHERE id=?');
        $stmt->bind_param('ssii', $item['url'], $item['version'], $item['timestamp'], $item['id']);
        $stmt->execute();

        $this->writeJSONFile($this->generateJSONFromSQL());
    }
}
