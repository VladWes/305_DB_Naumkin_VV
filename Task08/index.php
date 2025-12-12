<?php
require_once 'config.php';

// Обработка удаления студента
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: index.php");
    exit;
}

// Получение фильтра по группе
$groupFilter = isset($_GET['group_filter']) ? $_GET['group_filter'] : '';

// Получение списка групп для фильтра
$groupsStmt = $db->query("SELECT id, group_number FROM groups ORDER BY group_number");
$groups = $groupsStmt->fetchAll(PDO::FETCH_ASSOC);

// Формирование запроса для получения студентов
$query = "SELECT s.id, s.full_name, s.gender, s.birth_date, s.student_id, 
                 g.group_number, g.specialization, g.graduation_year
          FROM students s
          JOIN groups g ON s.group_id = g.id";
$params = [];

if ($groupFilter) {
    $query .= " WHERE s.group_id = ?";
    $params[] = $groupFilter;
}

$query .= " ORDER BY g.group_number, s.full_name";

$stmt = $db->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Функция для извлечения фамилии
function getLastName($fullName) {
    $parts = explode(' ', $fullName);
    return $parts[0] ?? '';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список студентов</title>
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
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            padding: 30px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2em;
        }
        
        .filter-section {
            margin-bottom: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        
        .filter-section label {
            font-weight: bold;
            margin-right: 10px;
        }
        
        .filter-section select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .filter-section button {
            padding: 8px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .filter-section button:hover {
            background: #5568d3;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #667eea;
            color: white;
            font-weight: bold;
        }
        
        tr:hover {
            background: #f5f5f5;
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .btn-edit {
            background: #4CAF50;
            color: white;
        }
        
        .btn-edit:hover {
            background: #45a049;
        }
        
        .btn-delete {
            background: #f44336;
            color: white;
        }
        
        .btn-delete:hover {
            background: #da190b;
        }
        
        .btn-exams {
            background: #2196F3;
            color: white;
        }
        
        .btn-exams:hover {
            background: #0b7dda;
        }
        
        .btn-add {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            font-size: 16px;
            margin-top: 20px;
        }
        
        .btn-add:hover {
            background: #5568d3;
        }
        
        .add-section {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Список студентов</h1>
        
        <div class="filter-section">
            <form method="GET" action="index.php">
                <label for="group_filter">Фильтр по группе:</label>
                <select name="group_filter" id="group_filter">
                    <option value="">Все группы</option>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?= $group['id'] ?>" <?= $groupFilter == $group['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($group['group_number']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Применить</button>
                <?php if ($groupFilter): ?>
                    <a href="index.php" class="btn" style="background: #999; color: white; margin-left: 10px;">Сбросить</a>
                <?php endif; ?>
            </form>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Группа</th>
                    <th>ФИО</th>
                    <th>Пол</th>
                    <th>Дата рождения</th>
                    <th>Студенческий билет</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 20px;">
                            Студенты не найдены
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['group_number']) ?></td>
                            <td><?= htmlspecialchars($student['full_name']) ?></td>
                            <td><?= htmlspecialchars($student['gender']) ?></td>
                            <td><?= htmlspecialchars($student['birth_date']) ?></td>
                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                            <td>
                                <div class="actions">
                                    <a href="student_form.php?id=<?= $student['id'] ?>" class="btn btn-edit">Редактировать</a>
                                    <a href="?action=delete&id=<?= $student['id'] ?>" 
                                       class="btn btn-delete" 
                                       onclick="return confirm('Вы уверены, что хотите удалить этого студента?')">Удалить</a>
                                    <a href="exams.php?student_id=<?= $student['id'] ?>" class="btn btn-exams">Результаты экзаменов</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="add-section">
            <a href="student_form.php" class="btn btn-add">Добавить студента</a>
        </div>
    </div>
</body>
</html>

