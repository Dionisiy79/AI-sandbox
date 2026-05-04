<?php

require_once __DIR__ . '/../DatabaseController.php';

$file = sys_get_temp_dir() . '/database_controller_' . getmypid() . '.sqlite';
@unlink($file);

$database = new DatabaseController('sqlite', ['file' => $file]);

try {
    $database->connect();
    $database->createTable('people', [
        'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        'name' => 'TEXT NOT NULL',
        'age' => 'INTEGER NOT NULL',
    ]);

    assert($database->tableExists('people'));

    $id = $database->create('people', ['name' => 'Alice', 'age' => 30]);
    assert($id > 0);

    $rows = $database->read('people', ['id' => $id]);
    assert(count($rows) === 1);
    assert($rows[0]['name'] === 'Alice');

    $updated = $database->update('people', ['age' => 31], ['id' => $id]);
    assert($updated === 1);
    assert((int) $database->read('people', ['id' => $id])[0]['age'] === 31);

    $deleted = $database->delete('people', ['id' => $id]);
    assert($deleted === 1);
    assert($database->read('people', ['id' => $id]) === []);

    echo "SQLite CRUD controller test passed.\n";
} finally {
    $database->close();
    @unlink($file);
}
