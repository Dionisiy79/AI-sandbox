<?php
/**
 * PHP Database Connection Demo (MySQLi Version)
 *
 * This script demonstrates:
 * - Connection to MySQL database named "DataBase1"
 * - Creation of an empty table named "Table1"
 *
 * Uses MySQLi extension (alternative to PDO)
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'DataBase1');
define('DB_USER', 'root');
define('DB_PASS', '');

// Enable error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Create connection to MySQL server
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);

    // Set charset to UTF-8
    $mysqli->set_charset('utf8mb4');

    echo "<pre>";
    echo "✓ Успешное подключение к MySQL серверу\n";

    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`
            DEFAULT CHARACTER SET utf8mb4
            COLLATE utf8mb4_unicode_ci";

    if ($mysqli->query($sql)) {
        echo "✓ База данных '" . DB_NAME . "' создана или уже существует\n";
    }

    // Select the database
    $mysqli->select_db(DB_NAME);
    echo "✓ Подключение к базе данных '" . DB_NAME . "'\n";

    // Create Table1
    $createTableSQL = "CREATE TABLE IF NOT EXISTS `Table1` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($mysqli->query($createTableSQL)) {
        echo "✓ Таблица 'Table1' успешно создана\n";
    }

    // Verify table exists
    $result = $mysqli->query("SHOW TABLES LIKE 'Table1'");
    if ($result->num_rows > 0) {
        echo "✓ Проверка: таблица 'Table1' существует в базе данных\n";
    }

    // Display table structure
    echo "\nСтруктура таблицы 'Table1':\n";
    $result = $mysqli->query("DESCRIBE `Table1`");

    echo str_repeat("-", 80) . "\n";
    printf("%-20s %-20s %-10s %-10s %-20s\n", "Field", "Type", "Null", "Key", "Extra");
    echo str_repeat("-", 80) . "\n";

    while ($row = $result->fetch_assoc()) {
        printf(
            "%-20s %-20s %-10s %-10s %-20s\n",
            $row['Field'],
            $row['Type'],
            $row['Null'],
            $row['Key'],
            $row['Extra']
        );
    }
    echo str_repeat("-", 80) . "\n";

    echo "\n✓ Все операции выполнены успешно!\n";
    echo "</pre>";
    
} catch (mysqli_sql_exception $e) {
    echo "✗ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    // Close connection
    if (isset($mysqli)) {
        $mysqli->close();
    }
}

