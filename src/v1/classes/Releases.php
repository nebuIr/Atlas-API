<?php

use \Dotenv\Dotenv;

class Releases
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

    public function getItems($order = 'DESC')
    {
        $stmt = $this->conn->prepare("SELECT * FROM releases ORDER BY id $order");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        return $result;
    }

    public function getItemCount(): int
    {
        $stmt = $this->conn->prepare("SELECT * FROM releases");
        $stmt->execute();

        return $stmt->get_result()->num_rows;
    }

    public function getResultByID($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM releases WHERE id = ?");
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

    public function getResultByURL($url)
    {
        $stmt = $this->conn->prepare("SELECT * FROM releases WHERE url = ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $url);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function getRowByURL($url)
    {
        $result = $this->getResultByURL($url);
        if ($result) {
            return $result->fetch_assoc();
        }

        return false;
    }

    public function getFieldByURL($field, $url)
    {
        $row = $this->getRowByURL($url);

        return $row[$field] ?? 0;
    }

    public function getJSONFromSQL($sql_result, $latest, $params): array
    {
        $output = array();
        $return_arr = array();

        if ($sql_result->num_rows > 0) {
            while ($row = $sql_result->fetch_assoc()) {
                $output['id'] = (int) $row['id'];
                $output['url'] = $row['url'];
                $output['title'] = $row['title'];
                $output['timestamp'] = $row['timestamp'];
                $output['platforms']['pc'] = (bool)$row['platform_pc'];
                $output['platforms']['ps4'] = (bool)$row['platform_ps4'];
                $output['platforms']['xbox'] = (bool)$row['platform_xbox'];
                $output['platforms']['ms-store'] = (bool)$row['platform_ms_store'];
                $output['images']['image_large'] = $row['image'];
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
        $stmt = $this->conn->prepare('INSERT INTO releases (id, url, title, timestamp, platform_pc, platform_ps4, platform_xbox, platform_ms_store, excerpt, image, body) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('issiiiiisss', $item['id'], $item['url'], $item['title'], $item['timestamp'], $item['platforms']['pc'], $item['platforms']['ps4'], $item['platforms']['xbox'], $item['platforms']['ms-store'], $item['excerpt'], $item['image'], $item['body']);
        if ($stmt->execute()) {
            echo 'Item added to DB: ' . $item['title'] . "\n";
        } else {
            echo 'Import failed';
        }
    }

    public function updateSQLEntry($item): void
    {
        $stmt = $this->conn->prepare('UPDATE releases SET url = ?, title = ?, timestamp = ?, platform_pc = ?, platform_ps4 = ?, platform_xbox = ?, platform_ms_store = ?, excerpt = ?, image = ?, body = ? WHERE id = ?');
        $stmt->bind_param('ssiiiiisssi', $item['url'], $item['title'], $item['timestamp'], $item['platforms']['pc'], $item['platforms']['ps4'], $item['platforms']['xbox'], $item['platforms']['ms-store'], $item['excerpt'], $item['image'], $item['body'], $item['id']);
        if ($stmt->execute()) {
            echo 'Item in DB updated: ' . $item['title'] . "\n";
        } else {
            echo 'Import failed';
        }
    }

    public function clearTable(): void
    {
        $stmt = $this->conn->prepare('DELETE FROM releases');
        if ($stmt->execute()) {
            echo "Table cleared\n";
        } else {
            echo "Clearing failed";
        }
    }
}