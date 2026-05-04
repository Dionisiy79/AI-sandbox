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

require_once __DIR__ . '/DatabaseController.php';

$config = [
    'file' => __DIR__ . '/DataBase1.sqlite',
    'name' => 'DataBase1.sqlite',
];

$database = new DatabaseController('sqlite', $config);

try {
    $database->connect();

    echo "<pre>";
    echo "✓ Успешное подключение к SQLite базе данных\n";
    echo "✓ Файл базы данных '" . $config['name'] . "' создан или уже существует\n";

    $database->createTable('Table1', [
        'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        'created_at' => 'TEXT DEFAULT CURRENT_TIMESTAMP',
    ]);

    echo "✓ Таблица 'Table1' успешно создана\n";

    if ($database->tableExists('Table1')) {
        echo "✓ Проверка: таблица 'Table1' существует в базе данных\n";
    }

    echo "\nСтруктура таблицы 'Table1':\n";
    $columns = $database->describeTable('Table1');

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
    $database->close();
}
