<?php
declare(strict_types=1);
namespace App\Core;

use mysqli;
use mysqli_sql_exception;

/**
 * Thin MySQLi wrapper. ALL queries use prepared statements with bound parameters;
 * values are never interpolated into SQL.
 */
final class Db
{
    private static ?Db $instance = null;
    private mysqli $conn;
    private int $txDepth = 0;

    private function __construct(mysqli $conn) { $this->conn = $conn; }

    public static function get(): Db
    {
        if (!self::$instance) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $c = new mysqli(
                Env::get('DB_HOST', '127.0.0.1'), Env::get('DB_USER', 'root'), Env::get('DB_PASS', ''),
                Env::get('DB_NAME', 'memorii'), (int) Env::get('DB_PORT', '3306'),
            );
            $c->set_charset('utf8mb4');
            $c->query("SET time_zone = '+07:00', sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
            self::$instance = new Db($c);
        }
        return self::$instance;
    }
    public static function reset(): void { self::$instance = null; }
    public function mysqli(): mysqli { return $this->conn; }

    /** @return array<int, array<string, mixed>> */
    public function all(string $sql, array $params = []): array
    {
        $stmt = $this->run($sql, $params);
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }
    public function one(string $sql, array $params = []): ?array
    {
        return $this->all($sql, $params)[0] ?? null;
    }
    public function value(string $sql, array $params = []): mixed
    {
        $row = $this->one($sql, $params);
        return $row ? array_values($row)[0] : null;
    }
    /** Executes a write; returns affected rows. */
    public function exec(string $sql, array $params = []): int
    {
        $stmt = $this->run($sql, $params);
        $n = $stmt->affected_rows;
        $stmt->close();
        return $n;
    }
    public function insert(string $table, array $data): void
    {
        if (!preg_match('/^[a-z_]+$/', $table)) throw new \InvalidArgumentException('bad table');
        $cols = array_keys($data);
        foreach ($cols as $c) if (!preg_match('/^[a-z_]+$/', $c)) throw new \InvalidArgumentException('bad column');
        $sql = sprintf('INSERT INTO %s (`%s`) VALUES (%s)', $table, implode('`,`', $cols), implode(',', array_fill(0, count($cols), '?')));
        $this->exec($sql, array_values($data));
    }

    /** Runs $fn inside a transaction (nested calls join the outer one). */
    public function tx(callable $fn): mixed
    {
        if ($this->txDepth++ === 0) $this->conn->begin_transaction();
        try {
            $r = $fn($this);
            if (--$this->txDepth === 0) $this->conn->commit();
            return $r;
        } catch (\Throwable $e) {
            if (--$this->txDepth === 0) $this->conn->rollback(); // inner levels rethrow; outermost rolls back
            throw $e;
        }
    }

    private function run(string $sql, array $params): \mysqli_stmt
    {
        $stmt = $this->conn->prepare($sql);
        if ($params) {
            $types = '';
            $vals = [];
            foreach ($params as $p) {
                if (is_bool($p)) { $types .= 'i'; $vals[] = $p ? 1 : 0; }
                elseif (is_int($p)) { $types .= 'i'; $vals[] = $p; }
                elseif (is_float($p)) { $types .= 'd'; $vals[] = $p; }
                elseif (is_array($p)) { $types .= 's'; $vals[] = json_encode($p, JSON_UNESCAPED_UNICODE); }
                else { $types .= 's'; $vals[] = $p; }
            }
            $stmt->bind_param($types, ...$vals);
        }
        $stmt->execute();
        return $stmt;
    }

    /** Placeholder list for IN (...) clauses. */
    public static function in(array $values): string
    {
        return implode(',', array_fill(0, max(1, count($values)), '?'));
    }

    public static function uuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    /** Run a .sql file (supports single-statement and BEGIN…END triggers). */
    public function runFile(string $path): void
    {
        $sql = file_get_contents($path);
        if (!$this->conn->multi_query($sql)) throw new mysqli_sql_exception($this->conn->error);
        do {
            if ($r = $this->conn->store_result()) $r->free();
        } while ($this->conn->more_results() && $this->conn->next_result());
    }
}
