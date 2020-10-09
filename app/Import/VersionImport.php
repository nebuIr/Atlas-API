<?php

namespace AtlasAPI\Import;

use PDO;
use Psr\Container\ContainerInterface;

class VersionImport
{
    private $pdo;

    public function __construct(ContainerInterface $container)
    {
        $this->pdo = $container->get(PDO::class);
    }

    public function getItems()
    {
        return $this->pdo->query('SELECT * FROM version');
    }

    public function SQLImport($item): bool
    {
        $result = $this->getItems();
        $row_count = $result->rowCount();

        if ($row_count) {
            if ($row_count === 1) {
                return $this->updateSQLEntry($item);
            }
        } else {
            return $this->addSQLEntry($item);
        }

        return false;
    }

    public function addSQLEntry($item): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO version (id, url, version, timestamp) VALUES (?, ?, ?, ?)');
        if ($stmt->execute([$item['id'], $item['url'], $item['version'], $item['timestamp']])) {
            return true;
        }

        return false;
    }

    public function updateSQLEntry($item): bool
    {
        $stmt = $this->pdo->prepare('UPDATE version SET url = ?, version = ?, timestamp = ? WHERE id = ?');
        $stmt->execute([$item['url'], $item['version'], $item['timestamp'], $item['id']]);
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function clearTable(): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM version');
        if ($stmt->execute()) {
            echo "[VERSION] Table cleared\n";
        } else {
            echo "[VERSION] Clearing failed\n";
        }
    }
}