<?php

require_once 'config.php';

if (!file_exists(DB_PATH)) {
    die("Database not initialized. Please run init_db.php first to create the database.");
}

function getDatabaseConnection() {
    try {
        $pdo = new PDO(DB_DSN);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection error: " . $e->getMessage());
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

$pdo = getDatabaseConnection();
$activeGroups = getActiveGroupNumbers($pdo);
$selectedGroup = $_GET['group'] ?? '';
$students = [];

if ($selectedGroup !== '' && !in_array($selectedGroup, $activeGroups)) {
    $selectedGroup = '';
}

try {
    $students = getStudents($pdo, $selectedGroup !== '' ? $selectedGroup : null);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список студентов - Лабораторная работа 7</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 30px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2em;
        }
        
        .filter-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .form-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        label {
            font-weight: 600;
            color: #555;
            font-size: 1.1em;
        }
        
        select {
            flex: 1;
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            background: white;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        
        select:hover {
            border-color: #667eea;
        }
        
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        button {
            padding: 10px 25px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.3s;
            font-weight: 600;
        }
        
        button:hover {
            background: #5568d3;
        }
        
        button:active {
            transform: scale(0.98);
        }
        
        .info {
            margin-bottom: 20px;
            padding: 15px;
            background: #e7f3ff;
            border-left: 4px solid #667eea;
            border-radius: 4px;
            color: #555;
        }
        
        .error {
            margin-bottom: 20px;
            padding: 15px;
            background: #ffe7e7;
            border-left: 4px solid #dc3545;
            border-radius: 4px;
            color: #dc3545;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9em;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        tbody tr {
            transition: background 0.2s;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        tbody tr:nth-child(even) {
            background: #fafafa;
        }
        
        tbody tr:nth-child(even):hover {
            background: #f0f0f0;
        }
        
        .count {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            text-align: center;
            font-weight: 600;
            color: #555;
        }
        
        .empty {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Список студентов</h1>
        
        <div class="filter-form">
            <form method="GET" action="">
                <div class="form-group">
                    <label for="group">Фильтр по группе:</label>
                    <select name="group" id="group">
                        <option value="">Все группы</option>
                        <?php if (!empty($activeGroups)): ?>
                            <?php foreach ($activeGroups as $group): ?>
                                <option value="<?= htmlspecialchars($group) ?>" 
                                    <?= $selectedGroup === $group ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($group) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <button type="submit">Применить фильтр</button>
                </div>
            </form>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php elseif (!empty($selectedGroup)): ?>
            <div class="info">
                Отображаются студенты группы: <strong><?= htmlspecialchars($selectedGroup) ?></strong>
            </div>
        <?php else: ?>
            <div class="info">
                Отображаются студенты всех активных групп
            </div>
        <?php endif; ?>
        
        <?php if (!empty($students)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Номер группы</th>
                        <th>Направление подготовки</th>
                        <th>ФИО</th>
                        <th>Пол</th>
                        <th>Дата рождения</th>
                        <th>Номер студенческого билета</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['group_number']) ?></td>
                            <td><?= htmlspecialchars($student['specialization']) ?></td>
                            <td><?= htmlspecialchars($student['full_name']) ?></td>
                            <td><?= htmlspecialchars($student['gender']) ?></td>
                            <td><?= htmlspecialchars($student['birth_date']) ?></td>
                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="count">
                Всего студентов: <?= count($students) ?>
            </div>
        <?php else: ?>
            <div class="empty">
                Студенты не найдены
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

