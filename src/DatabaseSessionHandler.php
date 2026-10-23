<?php
// src/DatabaseSessionHandler.php

require_once __DIR__ . '/Database.php';

class DatabaseSessionHandler implements SessionHandlerInterface
{
    private ?PDO $pdo = null;

    public function open(string $path, string $name): bool
    {
        try {
            $this->pdo = Database::getConnection();
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function close(): bool
    {
        $this->pdo = null;
        return true;
    }

    public function read(string $id): string|false
    {
        try {
            if (!$this->pdo) {
                $this->pdo = Database::getConnection();
            }

            $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetchColumn();

            return $result !== false ? (string)$result : '';
        } catch (Throwable $e) {
            return '';
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            if (!$this->pdo) {
                $this->pdo = Database::getConnection();
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO sessions (id, data, access)
                VALUES (:id, :data, :access)
                ON DUPLICATE KEY UPDATE data = VALUES(data), access = VALUES(access)
            ");

            return $stmt->execute([
                'id'     => $id,
                'data'   => $data,
                'access' => time(),
            ]);
        } catch (Throwable $e) {
            error_log("DatabaseSessionHandler::write error: " . $e->getMessage());
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            if (!$this->pdo) {
                $this->pdo = Database::getConnection();
            }

            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        try {
            if (!$this->pdo) {
                $this->pdo = Database::getConnection();
            }

            $old = time() - $max_lifetime;
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE access < :old");
            $stmt->execute(['old' => $old]);

            return $stmt->rowCount();
        } catch (Throwable $e) {
            return false;
        }
    }
}