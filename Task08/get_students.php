<?php
require_once 'config.php';

header('Content-Type: application/json');

$group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;

if (!$group_id) {
    echo json_encode([]);
    exit;
}

$stmt = $db->prepare("SELECT s.id, s.full_name 
                      FROM students s 
                      WHERE s.group_id = ? 
                      ORDER BY s.full_name");
$stmt->execute([$group_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($students);

