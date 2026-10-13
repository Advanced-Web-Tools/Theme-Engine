<?php

namespace ThemeEngine\service;

use database\interface\ITransactionalProvider;
use PDO;
use ThemeEngine\service\contracts\IStore;

/** JSON persistence for site activation, per-theme configuration and presets. */
final class DatabaseStore implements IStore
{
    public function __construct(private readonly ITransactionalProvider $db) {}

    public function state(): array
    {
        $row = $this->row('SELECT payload FROM theme_engine_state WHERE id = 1');
        return $row ? json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR) : Engine::defaultState();
    }

    public function saveState(array $state): void
    {
        $this->write('UPDATE theme_engine_state SET payload = :payload WHERE id = 1', [
            ':payload' => json_encode($state, JSON_THROW_ON_ERROR),
        ]);
    }

    public function config(int $packageId): array
    {
        $row = $this->row('SELECT payload FROM theme_engine_settings WHERE package_id = :id', [':id' => $packageId]);
        return $row ? json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR) : ['settings' => [], 'pages' => []];
    }

    public function saveConfig(int $packageId, array $config): void
    {
        // MySQL/MariaDB atomic upsert; package_id is the primary key.
        $this->write(
            'INSERT INTO theme_engine_settings (package_id, payload) VALUES (:id, :payload) ON DUPLICATE KEY UPDATE payload = VALUES(payload)',
            [':id' => $packageId, ':payload' => json_encode($config, JSON_THROW_ON_ERROR)],
        );
    }

    public function presets(int $packageId): array
    {
        $statement = $this->db->execute(
            'SELECT id, name, payload FROM theme_engine_presets WHERE package_id = :id ORDER BY id',
            [':id' => $packageId],
        );
        try {
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            $statement->closeCursor();
        }
        foreach ($rows as &$row) {
            $row['payload'] = json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR);
        }
        return $rows;
    }

    public function savePreset(int $packageId, string $name, array $payload): int
    {
        $this->write(
            'INSERT INTO theme_engine_presets (package_id, name, payload) VALUES (:id, :name, :payload)',
            [':id' => $packageId, ':name' => $name, ':payload' => json_encode($payload, JSON_THROW_ON_ERROR)],
        );
        return $this->db->lastInsertId();
    }

    public function deletePreset(int $packageId, int $id): void
    {
        $this->write(
            'DELETE FROM theme_engine_presets WHERE package_id = :package AND id = :id',
            [':package' => $packageId, ':id' => $id],
        );
    }

    public function transaction(callable $callback): mixed
    {
        return $this->db->transaction($callback);
    }

    private function row(string $sql, array $args = []): ?array
    {
        $statement = $this->db->execute($sql, $args);
        try {
            return $statement->fetch(PDO::FETCH_ASSOC) ?: null;
        } finally {
            $statement->closeCursor();
        }
    }

    private function write(string $sql, array $args): void
    {
        $this->db->execute($sql, $args)->closeCursor();
    }
}
