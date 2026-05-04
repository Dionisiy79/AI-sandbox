<?php
/**
 * PHP Database Connection Demo
 *
 * This script demonstrates:
 * - Connection to MySQL database named "DataBase1"
 * - Creation of an empty table named "Table1"
 */

$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/DatabaseController.php';

$database = new DatabaseController('mysql', $config);

try {
    $database->connect();
    
    echo "<pre>";
    echo "✓ Успешное подключение к MySQL серверу\n";

    $database->initializeDatabase();

    echo "✓ База данных '" . $config['database'] . "' создана или уже существует\n";
    echo "✓ Подключение к базе данных '" . $config['database'] . "'\n";

    $database->createTable('Table1', [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
    ]);

    echo "✓ Таблица 'Table1' успешно создана\n";

    if ($database->tableExists('Table1')) {
        echo "✓ Проверка: таблица 'Table1' существует в базе данных\n";
    }

    echo "\nСтруктура таблицы 'Table1':\n";
    $columns = $database->describeTable('Table1');

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
    echo "</pre>";
} catch (PDOException $e) {
    echo "✗ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    $database->close();
}
