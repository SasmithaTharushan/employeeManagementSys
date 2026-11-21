<?php
declare(strict_types=1);

/** Shared PDO connection. Every query in the app goes through prepared statements. */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $c = config('db');
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if ($c['driver'] === 'sqlite') {
        $isNew = !is_file($c['sqlite_path']) || filesize($c['sqlite_path']) === 0;
        $pdo = new PDO('sqlite:' . $c['sqlite_path'], null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON');
        if ($isNew) {
            // Zero-setup demo: build the schema and demo data on first request.
            require_once APP_ROOT . '/database/setup.php';
            install_database($pdo, true);
        }
    } else {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']);
        $pdo = new PDO($dsn, $c['user'], $c['pass'], $options);
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function one(string $sql, array $params = []): ?array
{
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function all(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function scalar(string $sql, array $params = [])
{
    $value = q($sql, $params)->fetchColumn();
    return $value === false ? null : $value;
}

/** Insert a row. Table and column names are always hard-coded by the caller, never user input. */
function insert(string $table, array $data): int
{
    $cols = array_keys($data);
    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $table,
        implode(', ', $cols),
        implode(', ', array_map(fn($c) => ':' . $c, $cols))
    );
    q($sql, $data);
    return (int) db()->lastInsertId();
}

function update(string $table, array $data, int $id): void
{
    $sets = implode(', ', array_map(fn($c) => "$c = :$c", array_keys($data)));
    $data['id'] = $id;
    q("UPDATE $table SET $sets WHERE id = :id", $data);
}
