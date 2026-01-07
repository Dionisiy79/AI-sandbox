<?php
/**
 * Database Configuration File
 *
 * Настройте эти параметры в соответствии с вашей средой MySQL
 */

return [
    'host' => 'localhost',
    'database' => 'DataBase1',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
