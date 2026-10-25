<?php
// src/DatabaseSessionHandler.php

require_once __DIR__ . '/Database.php';

class DatabaseSessionHandler implements SessionHandlerInterface
{
    private ?PDO $pdo = null;

    private function getPdo(): ?PDO
    {
        try {
            if ($this->pdo === null) {
                $this->pdo = Database::getConnection();
            }
            return $this->pdo;
        } catch (Throwable $e) {
            error_log("DatabaseSessionHandler::getPdo error: " . $e->getMessage());
            return null;
        }
    }

    public function open(string $path, string $name): bool
    {
        return $this->getPdo() !== null;
    }

    public function close(): bool
    {
        $this->pdo = null;
        return true;
    }

    public function read(string $id): string|false
    {
        $pdo = $this->getPdo();
        if (!$pdo) {
            return '';
        }

        try {
            // Requête compatible : teste expires_at, ou fallback si table avec access
            $stmt = $pdo->prepare("
                SELECT data 
                FROM sessions 
                WHERE id = :id 
                  AND (
                    (expires_at IS NOT NULL AND expires_at >= :now) 
                    OR expires_at IS NULL
                  ) 
                LIMIT 1
            ");
            $stmt->execute([
                'id'  => $id,
                'now' => time(),
            ]);
            $result = $stmt->fetchColumn();

            return $result !== false ? (string)$result : '';
        } catch (Throwable) {
            // Fallback si la table utilise 'access'
            try {
                $stmt = $pdo->prepare("SELECT data FROM sessions WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => $id]);
                $result = $stmt->fetchColumn();
                return $result !== false ? (string)$result : '';
            } catch (Throwable $e) {
                return '';
            }
        }
    }

    public function write(string $id, string $data): bool
    {
        $pdo = $this->getPdo();
        if (!$pdo) {
            return false;
        }

        $lifetime = (int)ini_get('session.gc_maxlifetime') ?: 7200;
        $now = time();
        $expiresAt = $now + $lifetime;

        // Tentative 1 : avec le format officiel expires_at
        try {
            $stmt = $pdo->prepare("
                INSERT INTO sessions (id, data, expires_at)
                VALUES (:id, :data, :expires_at)
                ON DUPLICATE KEY UPDATE 
                    data = VALUES(data), 
                    expires_at = VALUES(expires_at)
            ");
            return $stmt->execute([
                'id'         => $id,
                'data'       => $data,
                'expires_at' => $expiresAt,
            ]);
        } catch (Throwable) {
            // Tentative 2 : fallback si la table possède la colonne 'access'
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO sessions (id, data, access)
                    VALUES (:id, :data, :access)
                    ON DUPLICATE KEY UPDATE 
                        data = VALUES(data), 
                        access = VALUES(access)
                ");
                return $stmt->execute([
                    'id'     => $id,
                    'data'   => $data,
                    'access' => $now,
                ]);
            } catch (Throwable $e) {
                error_log("DatabaseSessionHandler::write fallback failed: " . $e->getMessage());
                return false;
            }
        }
    }

    public function destroy(string $id): bool
    {
        $pdo = $this->getPdo();
        if (!$pdo) {
            return false;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM sessions WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        $pdo = $this->getPdo();
        if (!$pdo) {
            return false;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM sessions WHERE expires_at < :now");
            $stmt->execute(['now' => time()]);
            return $stmt->rowCount();
        } catch (Throwable) {
            try {
                $old = time() - $max_lifetime;
                $stmt = $pdo->prepare("DELETE FROM sessions WHERE access < :old");
                $stmt->execute(['old' => $old]);
                return $stmt->rowCount();
            } catch (Throwable $e) {
                return false;
            }
        }
    }
}