<?php

require_once 'config.php';

$sqlFile = __DIR__ . '/db_init.sql';

if (!file_exists($sqlFile)) {
    die("Error: SQL file not found: $sqlFile\n");
}

try {
    if (file_exists(DB_PATH)) {
        unlink(DB_PATH);
        echo "Removed existing database.\n";
    }
    
    $pdo = new PDO(DB_DSN);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = file_get_contents($sqlFile);
    
    $pdo->exec('BEGIN TRANSACTION');
    
    $statements = [];
    $currentStatement = '';
    $inString = false;
    $stringChar = '';
    
    $lines = explode("\n", $sql);
    foreach ($lines as $line) {
        $line = rtrim($line);
        if (empty($line) || preg_match('/^\s*--/', $line)) {
            continue;
        }
        
        $currentStatement .= $line . "\n";
        
        if (preg_match('/;\s*$/', $line)) {
            $statement = trim($currentStatement);
            if (!empty($statement)) {
                $statements[] = $statement;
            }
            $currentStatement = '';
        }
    }
    
    if (!empty(trim($currentStatement))) {
        $statements[] = trim($currentStatement);
    }
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                if (stripos($statement, 'DROP') !== false && stripos($e->getMessage(), 'no such table') !== false) {
                    continue;
                }
                throw $e;
            }
        }
    }
    
    $pdo->exec('COMMIT');
    
    echo "Database initialized successfully!\n";
    echo "Database file: " . DB_PATH . "\n";
    
} catch (PDOException $e) {
    if (isset($pdo)) {
        try {
            $pdo->exec('ROLLBACK');
        } catch (PDOException $rollbackError) {
        }
    }
    die("Error initializing database: " . $e->getMessage() . "\n");
}

