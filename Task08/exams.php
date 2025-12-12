<?php
require_once 'config.php';

// Получение ID студента
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

if (!$student_id) {
    header("Location: index.php");
    exit;
}

// Получение информации о студенте
$stmt = $db->prepare("SELECT s.*, g.group_number, g.specialization, g.graduation_year 
                      FROM students s 
                      JOIN groups g ON s.group_id = g.id 
                      WHERE s.id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header("Location: index.php");
    exit;
}

// Обработка удаления экзамена
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['exam_id'])) {
    $stmt = $db->prepare("DELETE FROM exams WHERE id = ? AND student_id = ?");
    $stmt->execute([$_GET['exam_id'], $student_id]);
    header("Location: exams.php?student_id=" . $student_id);
    exit;
}

// Получение экзаменов студента
$stmt = $db->prepare("SELECT e.*, s.name as subject_name 
                      FROM exams e 
                      JOIN subjects s ON e.subject_id = s.id 
                      WHERE e.student_id = ? 
                      ORDER BY e.exam_date DESC, e.course DESC");
$stmt->execute([$student_id]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Результаты экзаменов</title>
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
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            padding: 30px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .student-info {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .student-info p {
            margin: 5px 0;
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
        
        .grade {
            font-weight: bold;
            font-size: 18px;
        }
        
        .grade-5 { color: #4CAF50; }
        .grade-4 { color: #2196F3; }
        .grade-3 { color: #FF9800; }
        .grade-2 { color: #f44336; }
        
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
        
        .btn-add {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            font-size: 16px;
        }
        
        .btn-add:hover {
            background: #5568d3;
        }
        
        .btn-back {
            background: #999;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .btn-back:hover {
            background: #777;
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
        <a href="index.php" class="btn-back">← Назад к списку студентов</a>
        
        <h1>Результаты экзаменов</h1>
        
        <div class="student-info">
            <p><strong>Студент:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
            <p><strong>Группа:</strong> <?= htmlspecialchars($student['group_number']) ?></p>
            <p><strong>Специализация:</strong> <?= htmlspecialchars($student['specialization']) ?></p>
            <p><strong>Год выпуска:</strong> <?= htmlspecialchars($student['graduation_year']) ?></p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Дата экзамена</th>
                    <th>Курс</th>
                    <th>Дисциплина</th>
                    <th>Оценка</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($exams)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px;">
                            Экзамены не найдены
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($exams as $exam): ?>
                        <tr>
                            <td><?= htmlspecialchars($exam['exam_date']) ?></td>
                            <td><?= htmlspecialchars($exam['course']) ?></td>
                            <td><?= htmlspecialchars($exam['subject_name']) ?></td>
                            <td>
                                <span class="grade grade-<?= $exam['grade'] ?>">
                                    <?= htmlspecialchars($exam['grade']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="exam_form.php?student_id=<?= $student_id ?>&exam_id=<?= $exam['id'] ?>" 
                                       class="btn btn-edit">Редактировать</a>
                                    <a href="?action=delete&student_id=<?= $student_id ?>&exam_id=<?= $exam['id'] ?>" 
                                       class="btn btn-delete" 
                                       onclick="return confirm('Вы уверены, что хотите удалить этот экзамен?')">Удалить</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="add-section">
            <a href="exam_form.php?student_id=<?= $student_id ?>" class="btn btn-add">Добавить экзамен</a>
        </div>
    </div>
</body>
</html>

