<?php

class Database {
    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    die('DB Error: ' . $e->getMessage());
                }
                die('Error de conexión a la base de datos.');
            }
        }
        return self::$instance;
    }

    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array {
        return self::query($sql, $params)->fetch() ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert(string $table, array $data): int {
        $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
        $vals = implode(', ', array_fill(0, count($data), '?'));
        self::query("INSERT INTO `$table` ($cols) VALUES ($vals)", array_values($data));
        return (int) self::get()->lastInsertId();
    }

    public static function update(string $table, array $data, array $where): int {
        $set   = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
        $cond  = implode(' AND ', array_map(fn($k) => "`$k` = ?", array_keys($where)));
        $params = [...array_values($data), ...array_values($where)];
        return self::query("UPDATE `$table` SET $set WHERE $cond", $params)->rowCount();
    }

    public static function delete(string $table, array $where): int {
        $cond   = implode(' AND ', array_map(fn($k) => "`$k` = ?", array_keys($where)));
        return self::query("DELETE FROM `$table` WHERE $cond", array_values($where))->rowCount();
    }

    public static function count(string $table, array $where = []): int {
        $cond   = $where ? ' WHERE ' . implode(' AND ', array_map(fn($k) => "`$k` = ?", array_keys($where))) : '';
        return (int) self::query("SELECT COUNT(*) FROM `$table`$cond", array_values($where))->fetchColumn();
    }
}
