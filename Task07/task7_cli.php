<?php

require_once 'config.php';

if (!file_exists(DB_PATH)) {
    die("Database not initialized. Please run init_db.php first to create the database.\n");
}

function getDatabaseConnection() {
    try {
        $pdo = new PDO(DB_DSN);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection error: " . $e->getMessage() . "\n");
    }
}


function getActiveGroupNumbers($pdo) {
    $currentYear = (int)date('Y');
    $stmt = $pdo->prepare("
        SELECT DISTINCT group_number 
        FROM groups 
        WHERE graduation_year <= :current_year 
        ORDER BY group_number
    ");
    $stmt->execute(['current_year' => $currentYear]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}


function extractSurname($fullName) {
    $parts = explode(' ', trim($fullName));
    return $parts[0] ?? '';
}

function getStudents($pdo, $groupNumber = null) {
    $currentYear = (int)date('Y');
    
    $sql = "
        SELECT 
            g.group_number,
            g.specialization,
            s.full_name,
            s.gender,
            s.birth_date,
            s.student_id
        FROM students s
        INNER JOIN groups g ON s.group_id = g.id
        WHERE g.graduation_year <= :current_year
    ";
    
    $params = ['current_year' => $currentYear];
    
    if ($groupNumber !== null && $groupNumber !== '') {
        $sql .= " AND g.group_number = :group_number";
        $params['group_number'] = $groupNumber;
    }
    
    $sql .= " ORDER BY g.group_number, s.full_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function calculateColumnWidths($students) {
    $widths = [
        'group_number' => 12,
        'specialization' => 25,
        'full_name' => 30,
        'gender' => 6,
        'birth_date' => 12,
        'student_id' => 12
    ];
    
    foreach ($students as $student) {
        $widths['group_number'] = max($widths['group_number'], mb_strlen($student['group_number']));
        $widths['specialization'] = max($widths['specialization'], mb_strlen($student['specialization']));
        $widths['full_name'] = max($widths['full_name'], mb_strlen($student['full_name']));
        $widths['gender'] = max($widths['gender'], mb_strlen($student['gender']));
        $widths['birth_date'] = max($widths['birth_date'], mb_strlen($student['birth_date']));
        $widths['student_id'] = max($widths['student_id'], mb_strlen($student['student_id']));
    }
    
    return $widths;
}


function printTableHeader($widths) {
    $totalWidth = array_sum($widths) + count($widths) * 3 + 1;
    echo str_repeat('=', $totalWidth) . "\n";
    echo "| " . str_pad("Группа", $widths['group_number'], ' ', STR_PAD_RIGHT) . " | ";
    echo str_pad("Направление", $widths['specialization'], ' ', STR_PAD_RIGHT) . " | ";
    echo str_pad("ФИО", $widths['full_name'], ' ', STR_PAD_RIGHT) . " | ";
    echo str_pad("Пол", $widths['gender'], ' ', STR_PAD_RIGHT) . " | ";
    echo str_pad("Дата рождения", $widths['birth_date'], ' ', STR_PAD_RIGHT) . " | ";
    echo str_pad("Студ. билет", $widths['student_id'], ' ', STR_PAD_RIGHT) . " |\n";
    echo str_repeat('=', $totalWidth) . "\n";
}


function printTableRow($student, $widths) {
    echo "| " . str_pad($student['group_number'], $widths['group_number'], ' ', STR_PAD_RIGHT) . " | ";
    echo str_pad($student['specialization'], $widths['specialization'], ' ', STR_PAD_RIGHT) . " | ";
    echo str_pad($student['full_name'], $widths['full_name'], ' ', STR_PAD_RIGHT) . " | ";
    echo str_pad($student['gender'], $widths['gender'], ' ', STR_PAD_RIGHT) . " | ";
    echo str_pad($student['birth_date'], $widths['birth_date'], ' ', STR_PAD_RIGHT) . " | ";
    echo str_pad($student['student_id'], $widths['student_id'], ' ', STR_PAD_RIGHT) . " |\n";
}


function printTableFooter($widths) {
    $totalWidth = array_sum($widths) + count($widths) * 3 + 1;
    echo str_repeat('=', $totalWidth) . "\n";
}


function displayStudentsTable($students) {
    if (empty($students)) {
        echo "Студенты не найдены.\n";
        return;
    }
    
    $widths = calculateColumnWidths($students);
    printTableHeader($widths);
    
    foreach ($students as $student) {
        printTableRow($student, $widths);
    }
    
    printTableFooter($widths);
    echo "\nВсего студентов: " . count($students) . "\n";
}

try {
    $pdo = getDatabaseConnection();
    
    $activeGroups = getActiveGroupNumbers($pdo);
    
    if (empty($activeGroups)) {
        echo "Активные группы не найдены.\n";
        exit(1);
    }
    
    echo "Доступные номера групп:\n";
    foreach ($activeGroups as $group) {
        echo "  - $group\n";
    }
    echo "\n";
    
    echo "Введите номер группы для фильтрации (или нажмите Enter для всех групп): ";
    $input = trim(fgets(STDIN));
    
    $selectedGroup = null;
    if ($input !== '') {
        if (!in_array($input, $activeGroups)) {
            echo "Ошибка: группа '$input' не найдена в списке активных групп.\n";
            exit(1);
        }
        $selectedGroup = $input;
        echo "\nФильтр: группа $selectedGroup\n";
    } else {
        echo "\nФильтр: все группы\n";
    }
    
    echo "\n";
    
    $students = getStudents($pdo, $selectedGroup);
    displayStudentsTable($students);
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

