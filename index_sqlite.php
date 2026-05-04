<?php
/**
 * PHP Database Connection Demo (SQLite Version)
 *
 * This script demonstrates:
 * - Connection to SQLite database named "DataBase1.sqlite"
 * - Creation of an empty table named "Table1"
 *
 * Uses PDO with the SQLite driver.
 */

// Database configuration
define('DB_FILE', __DIR__ . '/DataBase1.sqlite');
define('DB_NAME', 'DataBase1.sqlite');

try {
    // Create connection to SQLite database file.
    $pdo = new PDO(
        'sqlite:' . DB_FILE,
        null,
        null,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "<pre>";
    echo "✓ Успешное подключение к SQLite базе данных\n";
    echo "✓ Файл базы данных '" . DB_NAME . "' создан или уже существует\n";

    // Create Table1 (empty table with just an ID column for demonstration)
    $createTableSQL = "CREATE TABLE IF NOT EXISTS \"Table1\" (
        \"id\" INTEGER PRIMARY KEY AUTOINCREMENT,
        \"created_at\" TEXT DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($createTableSQL);

    echo "✓ Таблица 'Table1' успешно создана\n";

    // Verify table exists
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'Table1'");
    if ($stmt->fetch() !== false) {
        echo "✓ Проверка: таблица 'Table1' существует в базе данных\n";
    }

    // Display table structure
    echo "\nСтруктура таблицы 'Table1':\n";
    $stmt = $pdo->query('PRAGMA table_info("Table1")');
    $columns = $stmt->fetchAll();

    echo str_repeat("-", 80) . "\n";
    printf("%-20s %-20s %-10s %-10s %-20s\n", "Field", "Type", "Null", "Key", "Default");
    echo str_repeat("-", 80) . "\n";

    foreach ($columns as $column) {
        printf(
            "%-20s %-20s %-10s %-10s %-20s\n",
            $column['name'],
            $column['type'],
            $column['notnull'] ? 'NO' : 'YES',
            $column['pk'] ? 'PRI' : '',
            $column['dflt_value'] ?? ''
        );
    }
    echo str_repeat("-", 80) . "\n";

    echo "\n✓ Все операции выполнены успешно!\n";
    echo "</pre>";
} catch (PDOException $e) {
    echo "✗ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    // Close connection
    $pdo = null;
}
