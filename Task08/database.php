<?php
class Database {
    private $db;
    
    public function __construct() {
        $dbPath = __DIR__ . '/students.db';
        $this->db = new PDO('sqlite:' . $dbPath);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    public function getConnection() {
        return $this->db;
    }
    
    public function initDatabase() {
        $sql = file_get_contents(__DIR__ . '/db_init.sql');
        // Разбиваем SQL на отдельные запросы
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
            }
        );
        
        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                try {
                    $this->db->exec($statement);
                } catch (PDOException $e) {
                    // Игнорируем ошибки, если таблицы уже существуют
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        throw $e;
                    }
                }
            }
        }
    }
}

