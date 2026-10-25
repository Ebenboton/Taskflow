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

            // Récupère uniquement les sessions non expirées
            $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE id = :id AND expires_at >= :now LIMIT 1");
            $stmt->execute([
                'id'  => $id,
                'now' => time(),
            ]);
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

            // Utilise expires_at avec la durée de vie configurée (7200s par défaut)
            $lifetime = (int)ini_get('session.gc_maxlifetime') ?: 7200;
            $expiresAt = time() + $lifetime;

            $stmt = $this->pdo->prepare("
                INSERT INTO sessions (id, data, expires_at)
                VALUES (:id, :data, :expires_at)
                ON DUPLICATE KEY UPDATE data = VALUES(data), expires_at = VALUES(expires_at)
            ");

            return $stmt->execute([
                'id'         => $id,
                'data'       => $data,
                'expires_at' => $expiresAt,
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

            // Nettoyage des sessions expirées
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE expires_at < :now");
            $stmt->execute(['now' => time()]);

            return $stmt->rowCount();
        } catch (Throwable $e) {
            return false;
        }
    }
}