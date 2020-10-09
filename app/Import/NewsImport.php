<?php

namespace AtlasAPI\Import;

use PDO;
use Psr\Container\ContainerInterface;

class NewsImport
{
    private $pdo;

    public function __construct(ContainerInterface $container)
    {
        $this->pdo = $container->get(PDO::class);
    }

    public function getItems($order = 'DESC'): array
    {
        $stmt = $this->pdo->query("SELECT * FROM news ORDER BY id $order");

        return $stmt->fetchAll();
    }

    public function getItemCount(): int
    {
        $stmt =  $this->pdo->query("SELECT * FROM news");

        return $stmt->rowCount();
    }

    public function getResultByTimestamp($timestamp)
    {
        $stmt =  $this->pdo->prepare("SELECT * FROM news WHERE timestamp = ?");
        if (!$stmt->execute([$timestamp])) {
            return false;
        }

        return $stmt->fetchAll()[0] ?? '';
    }

    public function getRowByTimestamp($timestamp)
    {
        $result = $this->getResultByTimestamp($timestamp);
        if ($result) {
            return $result;
        }

        return false;
    }

    public function getFieldByTimestamp($field, $timestamp)
    {
        $row = $this->getRowByTimestamp($timestamp);

        return $row[$field] ?? 0;
    }

    public function SQLImport($item, $mode): bool
    {
        switch ($mode) {
            case 'add':
                return $this->addSQLEntry($item);
            case 'update':
                return $this->updateSQLEntry($item);
        }

        return false;
    }

    public function addSQLEntry($item): bool
    {
        $stmt =  $this->pdo->prepare('INSERT INTO news (id, url, title, timestamp, excerpt, image, image_small, body) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        if ($stmt->execute([$item['id'], $item['url'], $item['title'], (int) $item["timestamp"], $item['excerpt'], $item['image'], $item['image_small'], $item['body']])) {
            return true;
        }
        echo "[NEWS] Import failed for: {$item['title']}\n";

        return false;
    }

    public function updateSQLEntry($item): bool
    {
        $stmt =  $this->pdo->prepare('UPDATE news SET url = ?, title = ?, timestamp = ?, excerpt = ?, image = ?, image_small = ?, body = ? WHERE id = ?');
        if ($stmt->execute([$item['url'], $item['title'], (int) $item['timestamp'], $item['excerpt'], $item['image'], $item['image_small'], $item['body'], $item['id']])) {
            return true;
        }
        echo "[NEWS] Import failed for: {$item['title']}\n";

        return false;
    }

    public function clearTable($limit): void
    {
        $stmt =  $this->pdo->prepare("DELETE FROM news ORDER BY id DESC LIMIT $limit");
        if ($stmt->execute()) {
            echo "[NEWS] Table cleared\n";
        } else {
            echo "[NEWS] Clearing failed\n";
        }
    }
}