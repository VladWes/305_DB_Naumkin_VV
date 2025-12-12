<?php
require_once __DIR__ . '/database.php';

$database = new Database();
$db = $database->getConnection();

// Проверяем, существует ли база данных и таблицы, если нет - инициализируем
$dbPath = __DIR__ . '/students.db';
if (!file_exists($dbPath)) {
    $database->initDatabase();
} else {
    // Проверяем, существуют ли таблицы
    try {
        $db->query("SELECT 1 FROM groups LIMIT 1");
    } catch (PDOException $e) {
        // Если таблицы не существуют, инициализируем БД
        $database->initDatabase();
    }
}

