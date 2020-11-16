<?php

namespace AtlasAPI\Import;

use PDO;
use Psr\Container\ContainerInterface;

class ReleasesImport
{
    private $pdo;

    public function __construct(ContainerInterface $container)
    {
        $this->pdo = $container->get(PDO::class);
    }

    public function getItems($order = 'DESC'): array
    {
        $stmt = $this->pdo->query("SELECT * FROM releases ORDER BY id $order");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getItemCount(): int
    {
        $stmt = $this->pdo->query("SELECT * FROM releases");
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function getResultByURL($url)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM releases WHERE url = ?");
        if (!$stmt) {
            return false;
        }
        $stmt->execute([$url]);

        return $stmt->fetchAll()[0] ?? '';
    }

    public function getRowByURL($url)
    {
        $result = $this->getResultByURL($url);
        if ($result) {
            return $result;
        }

        return false;
    }

    public function getFieldByURL($field, $url)
    {
        $row = $this->getRowByURL($url);

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
        $stmt = $this->pdo->prepare('INSERT INTO releases (id, url, title, timestamp, platform_pc, platform_ps4, platform_ps5, platform_xbox_one, platform_xbox_series, platform_ms_store, excerpt, image, body) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        if ($stmt->execute([$item['id'], $item['url'], $item['title'], (int) $item['timestamp'], $item['platforms']['pc'], $item['platforms']['ps4'], $item['platforms']['ps5'], $item['platforms']['xbox-one'], $item['platforms']['xbox-series'], $item['platforms']['ms-store'], $item['excerpt'], $item['image'], $item['body']])) {
            return true;
        }
        echo "[RELEASES] Import failed for: {$item['title']}\n";

        return false;
    }

    public function updateSQLEntry($item): bool
    {
        $stmt = $this->pdo->prepare('UPDATE releases SET url = ?, title = ?, timestamp = ?, platform_pc = ?, platform_ps4 = ?, platform_ps5 = ?, platform_xbox_one = ?, platform_xbox_series = ?, platform_ms_store = ?, excerpt = ?, image = ?, body = ? WHERE id = ?');
        if ($stmt->execute([$item['url'], $item['title'], (int) $item['timestamp'], $item['platforms']['pc'], $item['platforms']['ps4'], $item['platforms']['ps5'], $item['platforms']['xbox-one'], $item['platforms']['xbox-series'], $item['platforms']['ms-store'], $item['excerpt'], $item['image'], $item['body'], $item['id']])) {
            return true;
        }
        echo "[RELEASES] Import failed for: {$item['title']}\n";

        return false;
    }

    public function clearTable($limit): void
    {
        if ($this->pdo->query("DELETE FROM releases ORDER BY id DESC LIMIT $limit")) {
            echo "[RELEASES] Table cleared\n";
        } else {
            echo "[RELEASES] Clearing failed\n";
        }
    }
}