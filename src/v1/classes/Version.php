<?php

use \Dotenv\Dotenv;

class Version
{
    private $conn;

    public function __construct()
    {
        require_once __DIR__ . '/../../../vendor/autoload.php';

        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();

        $db_host = $_ENV['DB_HOST'];
        $db_name = $_ENV['DB_NAME'];
        $db_user = $_ENV['DB_USER'];
        $db_pass = $_ENV['DB_PASS'];

        $this->conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if (!$this->conn) {
            die('Connection failed: ' . $this->conn->connect_error);
        }
    }

    public function getItems()
    {
        $stmt = $this->conn->prepare('SELECT * FROM version');
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        return $result;
    }

    public function getJSONFromSQL(): array
    {
        $result = $this->getItems();

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
        $result = $this->getItems();
        $row_count = $result->num_rows;

        if ($row_count) {
            if ($row_count === 1) {
                $this->updateSQLEntry($item);
            }
        } else {
            $this->addSQLEntry($item);
        }
    }

    public function addSQLEntry($item): void
    {
        $stmt = $this->conn->prepare('INSERT INTO version (id, url, version, timestamp) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('issi', $item['id'], $item['url'], $item['version'], $item['timestamp']);
        if ($stmt->execute()) {
            echo 'Version added to DB: ' . $item['version'] . "\n";
        } else {
            echo 'Import failed';
        }
    }

    public function updateSQLEntry($item): void
    {
        $stmt = $this->conn->prepare('UPDATE version SET url = ?, version = ?, timestamp = ? WHERE id = ?');
        $stmt->bind_param('ssii', $item['url'], $item['version'], $item['timestamp'], $item['id']);
        if ($stmt->execute()) {
            echo 'Version in DB updated: ' . $item['version'] . "\n";
        } else {
            echo 'Import failed';
        }
    }

    public function clearTable(): void
    {
        $stmt = $this->conn->prepare('DELETE FROM version');
        if ($stmt->execute()) {
            echo "Table cleared\n";
        } else {
            echo "Clearing failed";
        }
    }
}