<?php

use \Dotenv\Dotenv;

class Version
{
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

    public function getSQL()
    {
        $stmt = $this->conn->prepare('SELECT * FROM version');
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        return $result;
    }

    public function getJSONFromSQL(): array
    {
        $result = $this->getSQL();

        $output = array();
        $return_arr = array();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output['url'] = $row['url'];
                $output['version'] = $row['version'];
                $output['timestamp'] = $row['timestamp'];

                $return_arr[] = $output;
            }
        }

        return $return_arr;
    }

    public function SQLImport($item): void
    {
        $result = $this->getSQL();
        $row_count = $result->num_rows;

        if ($row_count) {
            if ($row_count === 1) {
                $this->updateSQLEntries($item);
            }
        } else {
            $this->addSQLEntries($item);
        }
    }

    public function addSQLEntries($item): void
    {
        $stmt = $this->conn->prepare('INSERT INTO version (id, url, version, timestamp) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('issi', $item['id'], $item['url'], $item['version'], $item['timestamp']);
        $stmt->execute();
    }

    public function updateSQLEntries($item): void
    {
        $stmt = $this->conn->prepare('UPDATE version SET url = ?, version = ?, timestamp = ? WHERE id = ?');
        $stmt->bind_param('ssii', $item['url'], $item['version'], $item['timestamp'], $item['id']);
        $stmt->execute();
    }
}