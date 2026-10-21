<?php
// src/DatabaseSessionHandler.php

require_once __DIR__ . '/Database.php';

class DatabaseSessionHandler implements SessionHandlerInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        try {
            $stmt = $this->db->prepare("SELECT data FROM sessions WHERE id = ? AND expires_at > ? LIMIT 1");
            $stmt->execute([$id, time()]);
            $data = $stmt->fetchColumn();
            return $data !== false ? (string)$data : '';
        } catch (Throwable $e) {
            return '';
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            $expires = time() + 7200; // 2 heures
            $stmt = $this->db->prepare(
                "INSERT INTO sessions (id, data, expires_at)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE data = VALUES(data), expires_at = VALUES(expires_at)"
            );
            return $stmt->execute([$id, $data, $expires]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM sessions WHERE expires_at < ?");
            $stmt->execute([time()]);
            return $stmt->rowCount();
        } catch (Throwable $e) {
            return 0;
        }
    }
}
