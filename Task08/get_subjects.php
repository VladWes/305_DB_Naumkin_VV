<?php
require_once 'config.php';

header('Content-Type: application/json');

$group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
$course = isset($_GET['course']) ? (int)$_GET['course'] : 0;

if (!$group_id || !$course) {
    echo json_encode([]);
    exit;
}

// Получаем специализацию группы
$stmt = $db->prepare("SELECT specialization FROM groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    echo json_encode([]);
    exit;
}

// Получаем дисциплины для данной специализации и курса
$stmt = $db->prepare("SELECT id, name 
                      FROM subjects 
                      WHERE specialization = ? AND course = ? 
                      ORDER BY name");
$stmt->execute([$group['specialization'], $course]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($subjects);

