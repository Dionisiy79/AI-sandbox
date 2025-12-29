<?php
/**
 * PHP Database Connection Demo
 *
 * This script demonstrates:
 * - Connection to MySQL database named "DataBase1"
 * - Creation of an empty table named "Table1"
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'DataBase1');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    // Create connection to MySQL server (without specifying database)
    $pdo = new PDO(
        "mysql:host=" . DB_HOST,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "✓ Успешное подключение к MySQL серверу\n";

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`
                DEFAULT CHARACTER SET utf8mb4
                COLLATE utf8mb4_unicode_ci");

    echo "✓ База данных '" . DB_NAME . "' создана или уже существует\n";

    // Connect to the specific database
    $pdo->exec("USE `" . DB_NAME . "`");

    echo "✓ Подключение к базе данных '" . DB_NAME . "'\n";

    // Create Table1 (empty table with just an ID column for demonstration)
    $createTableSQL = "CREATE TABLE IF NOT EXISTS `Table1` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($createTableSQL);

    echo "✓ Таблица 'Table1' успешно создана\n";

    // Verify table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'Table1'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Проверка: таблица 'Table1' существует в базе данных\n";
    }

    // Display table structure
    echo "\nСтруктура таблицы 'Table1':\n";
    $stmt = $pdo->query("DESCRIBE `Table1`");
    $columns = $stmt->fetchAll();

    echo str_repeat("-", 80) . "\n";
    printf("%-20s %-20s %-10s %-10s %-20s\n", "Field", "Type", "Null", "Key", "Extra");
    echo str_repeat("-", 80) . "\n";

    foreach ($columns as $column) {
        printf(
            "%-20s %-20s %-10s %-10s %-20s\n",
            $column['Field'],
            $column['Type'],
            $column['Null'],
            $column['Key'],
            $column['Extra']
        );
    }
    echo str_repeat("-", 80) . "\n";

    echo "\n✓ Все операции выполнены успешно!\n";

} catch (PDOException $e) {
    echo "✗ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    // Close connection
    $pdo = null;
}
