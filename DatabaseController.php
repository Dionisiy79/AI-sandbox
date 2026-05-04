<?php
/**
 * Universal database controller for simple PDO and MySQLi CRUD operations.
 */

class DatabaseController
{
    private string $type;
    private array $config;
    private ?PDO $pdo = null;
    private ?mysqli $mysqli = null;

    public function __construct(string $type, array $config)
    {
        $this->type = strtolower($type);
        $this->config = $config;
    }

    public function connect(): void
    {
        switch ($this->type) {
            case 'mysql':
            case 'pdo_mysql':
                $this->connectPdoMysql();
                break;
            case 'sqlite':
            case 'pdo_sqlite':
                $this->connectPdoSqlite();
                break;
            case 'mysqli':
                $this->connectMysqli();
                break;
            default:
                throw new InvalidArgumentException("Unsupported database type: {$this->type}");
        }
    }

    public function initializeDatabase(): void
    {
        if ($this->isPdoMysql()) {
            $database = $this->config['database'];
            $charset = $this->config['charset'] ?? 'utf8mb4';
            $collation = $this->config['collation'] ?? 'utf8mb4_unicode_ci';

            $this->pdo()->exec(
                "CREATE DATABASE IF NOT EXISTS {$this->quoteIdentifier($database)} " .
                "DEFAULT CHARACTER SET {$charset} COLLATE {$collation}"
            );
            $this->pdo()->exec("USE {$this->quoteIdentifier($database)}");
            return;
        }

        if ($this->type === 'mysqli') {
            $database = $this->config['database'];
            $charset = $this->config['charset'] ?? 'utf8mb4';
            $collation = $this->config['collation'] ?? 'utf8mb4_unicode_ci';

            $this->mysqli()->query(
                "CREATE DATABASE IF NOT EXISTS {$this->quoteIdentifier($database)} " .
                "DEFAULT CHARACTER SET {$charset} COLLATE {$collation}"
            );
            $this->mysqli()->select_db($database);
        }
    }

    public function createTable(string $table, array $columns): void
    {
        $columnSql = [];
        foreach ($columns as $name => $definition) {
            $columnSql[] = $this->quoteIdentifier($name) . ' ' . $definition;
        }

        $sql = "CREATE TABLE IF NOT EXISTS {$this->quoteIdentifier($table)} (\n    " .
            implode(",\n    ", $columnSql) .
            "\n)";

        if ($this->isMysqlFamily()) {
            $charset = $this->config['charset'] ?? 'utf8mb4';
            $collation = $this->config['collation'] ?? 'utf8mb4_unicode_ci';
            $sql .= " ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}";
        }

        $this->execute($sql);
    }

    public function tableExists(string $table): bool
    {
        if ($this->isSqlite()) {
            return $this->fetchOne(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?",
                [$table]
            ) !== null;
        }

        return $this->fetchOne(
            'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$this->config['database'], $table]
        ) !== null;
    }

    public function describeTable(string $table): array
    {
        if ($this->isSqlite()) {
            return $this->fetchAll("PRAGMA table_info({$this->quoteIdentifier($table)})");
        }

        return $this->fetchAll("DESCRIBE {$this->quoteIdentifier($table)}");
    }

    public function create(string $table, array $data): int
    {
        if ($data === []) {
            throw new InvalidArgumentException('Cannot create a row without data.');
        }

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $sql = "INSERT INTO {$this->quoteIdentifier($table)} (" .
            implode(', ', array_map([$this, 'quoteIdentifier'], $columns)) .
            ') VALUES (' . implode(', ', $placeholders) . ')';

        $this->execute($sql, array_values($data));
        return $this->lastInsertId();
    }

    public function read(string $table, array $criteria = []): array
    {
        [$whereSql, $params] = $this->buildWhere($criteria);
        return $this->fetchAll("SELECT * FROM {$this->quoteIdentifier($table)}{$whereSql}", $params);
    }

    public function update(string $table, array $data, array $criteria): int
    {
        if ($data === []) {
            throw new InvalidArgumentException('Cannot update a row without data.');
        }
        if ($criteria === []) {
            throw new InvalidArgumentException('Update criteria are required.');
        }

        $assignments = [];
        foreach (array_keys($data) as $column) {
            $assignments[] = $this->quoteIdentifier($column) . ' = ?';
        }
        [$whereSql, $whereParams] = $this->buildWhere($criteria);

        return $this->execute(
            "UPDATE {$this->quoteIdentifier($table)} SET " . implode(', ', $assignments) . $whereSql,
            array_merge(array_values($data), $whereParams)
        );
    }

    public function delete(string $table, array $criteria): int
    {
        if ($criteria === []) {
            throw new InvalidArgumentException('Delete criteria are required.');
        }

        [$whereSql, $params] = $this->buildWhere($criteria);
        return $this->execute("DELETE FROM {$this->quoteIdentifier($table)}{$whereSql}", $params);
    }

    public function close(): void
    {
        $this->pdo = null;
        if ($this->mysqli !== null) {
            $this->mysqli->close();
            $this->mysqli = null;
        }
    }

    private function connectPdoMysql(): void
    {
        $this->pdo = new PDO(
            'mysql:host=' . $this->config['host'],
            $this->config['username'],
            $this->config['password'],
            $this->pdoOptions()
        );
    }

    private function connectPdoSqlite(): void
    {
        $this->pdo = new PDO(
            'sqlite:' . $this->config['file'],
            null,
            null,
            $this->pdoOptions()
        );
    }

    private function connectMysqli(): void
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->mysqli = new mysqli(
            $this->config['host'],
            $this->config['username'],
            $this->config['password']
        );
        $this->mysqli->set_charset($this->config['charset'] ?? 'utf8mb4');
    }

    private function execute(string $sql, array $params = []): int
    {
        if ($this->usesPdo()) {
            $stmt = $this->pdo()->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        }

        $stmt = $this->prepareMysqli($sql, $params);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        return $affectedRows;
    }

    private function fetchAll(string $sql, array $params = []): array
    {
        if ($this->usesPdo()) {
            $stmt = $this->pdo()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        $stmt = $this->prepareMysqli($sql, $params);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }

    private function fetchOne(string $sql, array $params = []): ?array
    {
        $rows = $this->fetchAll($sql, $params);
        return $rows[0] ?? null;
    }

    private function prepareMysqli(string $sql, array $params): mysqli_stmt
    {
        $stmt = $this->mysqli()->prepare($sql);
        if ($params !== []) {
            $types = '';
            foreach ($params as $param) {
                $types .= is_int($param) ? 'i' : (is_float($param) ? 'd' : 's');
            }
            $stmt->bind_param($types, ...$params);
        }

        return $stmt;
    }

    private function buildWhere(array $criteria): array
    {
        if ($criteria === []) {
            return ['', []];
        }

        $clauses = [];
        foreach (array_keys($criteria) as $column) {
            $clauses[] = $this->quoteIdentifier($column) . ' = ?';
        }

        return [' WHERE ' . implode(' AND ', $clauses), array_values($criteria)];
    }

    private function quoteIdentifier(string $identifier): string
    {
        $parts = explode('.', $identifier);
        $quote = $this->isSqlite() ? '"' : '`';

        return implode('.', array_map(
            static fn(string $part): string => $quote . str_replace($quote, $quote . $quote, $part) . $quote,
            $parts
        ));
    }

    private function lastInsertId(): int
    {
        if ($this->usesPdo()) {
            return (int) $this->pdo()->lastInsertId();
        }

        return $this->mysqli()->insert_id;
    }

    private function pdoOptions(): array
    {
        return $this->config['options'] ?? [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }

    private function pdo(): PDO
    {
        if ($this->pdo === null) {
            throw new RuntimeException('PDO connection is not initialized.');
        }

        return $this->pdo;
    }

    private function mysqli(): mysqli
    {
        if ($this->mysqli === null) {
            throw new RuntimeException('MySQLi connection is not initialized.');
        }

        return $this->mysqli;
    }

    private function usesPdo(): bool
    {
        return in_array($this->type, ['mysql', 'pdo_mysql', 'sqlite', 'pdo_sqlite'], true);
    }

    private function isPdoMysql(): bool
    {
        return in_array($this->type, ['mysql', 'pdo_mysql'], true);
    }

    private function isSqlite(): bool
    {
        return in_array($this->type, ['sqlite', 'pdo_sqlite'], true);
    }

    private function isMysqlFamily(): bool
    {
        return $this->isPdoMysql() || $this->type === 'mysqli';
    }
}
