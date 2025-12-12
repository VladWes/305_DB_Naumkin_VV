<?php
require_once 'config.php';

$student = null;
$isEdit = false;

// Получение данных студента для редактирования
if (isset($_GET['id'])) {
    $isEdit = true;
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        header("Location: index.php");
        exit;
    }
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $group_id = $_POST['group_id'];
    $full_name = trim($_POST['full_name']);
    $gender = $_POST['gender'];
    $birth_date = $_POST['birth_date'];
    $student_id = trim($_POST['student_id']);
    
    // Валидация
    $errors = [];
    if (empty($full_name)) $errors[] = "ФИО обязательно для заполнения";
    if (empty($birth_date)) $errors[] = "Дата рождения обязательна";
    if (empty($student_id)) $errors[] = "Номер студенческого билета обязателен";
    
    if (empty($errors)) {
        if ($isEdit) {
            // Проверка уникальности student_id (кроме текущего студента)
            $checkStmt = $db->prepare("SELECT id FROM students WHERE student_id = ? AND id != ?");
            $checkStmt->execute([$student_id, $_GET['id']]);
            if ($checkStmt->fetch()) {
                $errors[] = "Студент с таким номером студенческого билета уже существует";
            } else {
                $stmt = $db->prepare("UPDATE students SET group_id = ?, full_name = ?, gender = ?, birth_date = ?, student_id = ? WHERE id = ?");
                $stmt->execute([$group_id, $full_name, $gender, $birth_date, $student_id, $_GET['id']]);
                header("Location: index.php");
                exit;
            }
        } else {
            // Проверка уникальности student_id
            $checkStmt = $db->prepare("SELECT id FROM students WHERE student_id = ?");
            $checkStmt->execute([$student_id]);
            if ($checkStmt->fetch()) {
                $errors[] = "Студент с таким номером студенческого билета уже существует";
            } else {
                $stmt = $db->prepare("INSERT INTO students (group_id, full_name, gender, birth_date, student_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$group_id, $full_name, $gender, $birth_date, $student_id]);
                header("Location: index.php");
                exit;
            }
        }
    }
}

// Получение списка групп
$groupsStmt = $db->query("SELECT id, group_number FROM groups ORDER BY group_number");
$groups = $groupsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Редактирование студента' : 'Добавление студента' ?></title>
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
            max-width: 600px;
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
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        input[type="text"],
        input[type="date"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        
        .radio-group label {
            font-weight: normal;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .radio-group input[type="radio"] {
            width: auto;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .btn-submit {
            background: #667eea;
            color: white;
            width: 100%;
        }
        
        .btn-submit:hover {
            background: #5568d3;
        }
        
        .btn-cancel {
            background: #999;
            color: white;
            margin-top: 10px;
            width: 100%;
            text-align: center;
        }
        
        .btn-cancel:hover {
            background: #777;
        }
        
        .errors {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .errors ul {
            margin-left: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= $isEdit ? 'Редактирование студента' : 'Добавление студента' ?></h1>
        
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="errors">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="group_id">Группа *</label>
                <select name="group_id" id="group_id" required>
                    <option value="">Выберите группу</option>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?= $group['id'] ?>" 
                                <?= ($student && $student['group_id'] == $group['id']) || (isset($_POST['group_id']) && $_POST['group_id'] == $group['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($group['group_number']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="full_name">ФИО *</label>
                <input type="text" name="full_name" id="full_name" 
                       value="<?= htmlspecialchars($student['full_name'] ?? $_POST['full_name'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Пол *</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="gender" value="М" 
                               <?= ($student && $student['gender'] == 'М') || (isset($_POST['gender']) && $_POST['gender'] == 'М') ? 'checked' : '' ?> required>
                        Мужской
                    </label>
                    <label>
                        <input type="radio" name="gender" value="Ж" 
                               <?= ($student && $student['gender'] == 'Ж') || (isset($_POST['gender']) && $_POST['gender'] == 'Ж') ? 'checked' : '' ?> required>
                        Женский
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="birth_date">Дата рождения *</label>
                <input type="date" name="birth_date" id="birth_date" 
                       value="<?= htmlspecialchars($student['birth_date'] ?? $_POST['birth_date'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="student_id">Номер студенческого билета *</label>
                <input type="text" name="student_id" id="student_id" 
                       value="<?= htmlspecialchars($student['student_id'] ?? $_POST['student_id'] ?? '') ?>" required>
            </div>
            
            <button type="submit" class="btn btn-submit">
                <?= $isEdit ? 'Сохранить изменения' : 'Добавить студента' ?>
            </button>
        </form>
        
        <a href="index.php" class="btn btn-cancel">Отмена</a>
    </div>
</body>
</html>

