<?php

use \Dotenv\Dotenv;

class News
{
    private $conn;

    public function __construct()
    {
        require_once __DIR__ . '/../../../vendor/autoload.php';

        $dotenv = Dotenv::create(__DIR__ . '/../../../');
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

    public function getItems($order = 'DESC')
    {
        $stmt = $this->conn->prepare("SELECT * FROM news ORDER BY id $order");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        return $result;
    }

    public function getItemCount(): int
    {
        $stmt = $this->conn->prepare("SELECT * FROM news");
        $stmt->execute();

        return $stmt->get_result()->num_rows;
    }

    public function getResultByID($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM news WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function getRowByID($id): ?array
    {
        return $this->getResultByID($id)->fetch_assoc();
    }

    public function getFieldByID($field, $id)
    {
        $row = $this->getRowByID($id);

        return $row[$field];
    }

    public function getResultByTimestamp($timestamp)
    {
        $stmt = $this->conn->prepare("SELECT * FROM news WHERE timestamp = ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $timestamp);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function getRowByTimestamp($timestamp)
    {
        $result = $this->getResultByTimestamp($timestamp);
        if ($result) {
            return $result->fetch_assoc();
        }

        return false;
    }

    public function getFieldByTimestamp($field, $timestamp)
    {
        $row = $this->getRowByTimestamp($timestamp);

        return $row[$field] ?? 0;
    }

    public function getJSONFromSQL($sql_result, $latest, $params): array
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

        if ((array_key_exists('offset', $params) && (bool)preg_match('/^\d+$/', $params['offset']) === false)
            || (array_key_exists('limit', $params) && (bool)preg_match('/^\d+$/', $params['limit']) === false)) {
            return [];
        }

        if (!array_key_exists('offset', $params) || $params['offset'] < 0) {
            $params['offset'] = 0;
        }

        if (!array_key_exists('limit', $params) || $params['limit'] <= 0) {
            $params['limit'] = $array_length;
        }

        return array_slice($return_arr, 0 + $params['offset'], $params['limit']);
    }

    public function SQLImport($item, $mode): void
    {
        switch ($mode) {
            case 'add':
                $this->addSQLEntry($item);
                break;

            case 'update':
                $this->updateSQLEntry($item);
                break;
        }
    }

    public function addSQLEntry($item): void
    {
        $stmt = $this->conn->prepare('INSERT INTO news (id, url, title, timestamp, excerpt, image, image_small, body) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ississss', $item['id'], $item['url'], $item['title'], $item["timestamp"], $item['excerpt'], $item['image'], $item['image_small'], $item['body']);
        if ($stmt->execute()) {
            echo 'Item added to DB: ' . $item['title'] . "\n";
        } else {
            echo 'Import failed';
        }
    }

    public function updateSQLEntry($item): void
    {
        $stmt = $this->conn->prepare('UPDATE news SET url = ?, title = ?, timestamp = ?, excerpt = ?, image = ?, image_small = ?, body = ? WHERE id = ?');
        $stmt->bind_param('ssssssi', $item['url'], $item['title'], $item['timestamp'], $item['excerpt'], $item['image'], $item['image_small'], $item['body'], $item['id']);
        if ($stmt->execute()) {
            echo 'Item in DB updated: ' . $item['title'] . "\n";
        } else {
            echo 'Import failed';
        }
    }
}