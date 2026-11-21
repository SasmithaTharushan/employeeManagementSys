<?php
declare(strict_types=1);

/**
 * Stores PHP sessions in the database instead of local files.
 *
 * Needed on platforms like Vercel, where the app runs on several short-lived
 * instances that don't share a disk: without it, users would be logged out
 * whenever a request reached a different instance.
 */
final class DatabaseSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    private bool $tableReady = false;

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
        $this->ensureTable();
        return (string) (scalar('SELECT data FROM sessions WHERE id = ?', [$id]) ?? '');
    }

    public function write(string $id, string $data): bool
    {
        $this->ensureTable();
        // REPLACE works on MySQL, MariaDB, TiDB and SQLite.
        q('REPLACE INTO sessions (id, data, last_activity) VALUES (?, ?, ?)', [$id, $data, time()]);
        return true;
    }

    public function destroy(string $id): bool
    {
        q('DELETE FROM sessions WHERE id = ?', [$id]);
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return q('DELETE FROM sessions WHERE last_activity < ?', [time() - $max_lifetime])->rowCount();
    }

    public function validateId(string $id): bool
    {
        $this->ensureTable();
        return (bool) scalar('SELECT 1 FROM sessions WHERE id = ?', [$id]);
    }

    public function updateTimestamp(string $id, string $data): bool
    {
        q('UPDATE sessions SET last_activity = ? WHERE id = ?', [time(), $id]);
        return true;
    }

    /** The sessions table is created on first use, so login works even before install.php has run. */
    private function ensureTable(): void
    {
        if (!$this->tableReady) {
            db()->exec(sessions_table_sql());
            $this->tableReady = true;
        }
    }
}

function sessions_table_sql(): string
{
    return 'CREATE TABLE IF NOT EXISTS sessions (
        id VARCHAR(128) NOT NULL PRIMARY KEY,
        data TEXT NOT NULL,
        last_activity INT NOT NULL
    )';
}
