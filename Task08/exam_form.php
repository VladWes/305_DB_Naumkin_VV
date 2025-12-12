<?php
require_once 'config.php';

$exam = null;
$isEdit = false;
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

// Получение данных экзамена для редактирования
if (isset($_GET['exam_id'])) {
    $isEdit = true;
    $stmt = $db->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->execute([$_GET['exam_id']]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$exam) {
        header("Location: index.php");
        exit;
    }
    
    // Получаем student_id из экзамена, если не передан
    if (!$student_id) {
        $student_id = $exam['student_id'];
    }
}

// Если student_id задан, получаем информацию о студенте
$student = null;
if ($student_id) {
    $stmt = $db->prepare("SELECT s.*, g.group_number, g.specialization, g.graduation_year 
                          FROM students s 
                          JOIN groups g ON s.group_id = g.id 
                          WHERE s.id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = (int)$_POST['student_id'];
    $subject_id = (int)$_POST['subject_id'];
    $exam_date = $_POST['exam_date'];
    $grade = (int)$_POST['grade'];
    $course = (int)$_POST['course'];
    
    // Валидация
    $errors = [];
    if (empty($exam_date)) $errors[] = "Дата экзамена обязательна";
    if ($grade < 2 || $grade > 5) $errors[] = "Оценка должна быть от 2 до 5";
    if ($course < 1 || $course > 4) $errors[] = "Курс должен быть от 1 до 4";
    
    if (empty($errors)) {
        // Проверяем, что дисциплина соответствует специализации и курсу
        $stmt = $db->prepare("SELECT s.id, s.specialization, s.course 
                              FROM subjects s 
                              WHERE s.id = ?");
        $stmt->execute([$subject_id]);
        $subject = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$subject) {
            $errors[] = "Дисциплина не найдена";
        } else {
            // Получаем специализацию студента
            $stmt = $db->prepare("SELECT g.specialization 
                                  FROM students s 
                                  JOIN groups g ON s.group_id = g.id 
                                  WHERE s.id = ?");
            $stmt->execute([$student_id]);
            $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($studentInfo && $subject['specialization'] != $studentInfo['specialization']) {
                $errors[] = "Дисциплина не соответствует специализации студента";
            }
            
            if ($subject['course'] != $course) {
                $errors[] = "Дисциплина не соответствует выбранному курсу";
            }
        }
    }
    
    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $db->prepare("UPDATE exams SET student_id = ?, subject_id = ?, exam_date = ?, grade = ?, course = ? WHERE id = ?");
            $stmt->execute([$student_id, $subject_id, $exam_date, $grade, $course, $_GET['exam_id']]);
            header("Location: exams.php?student_id=" . $student_id);
            exit;
        } else {
            $stmt = $db->prepare("INSERT INTO exams (student_id, subject_id, exam_date, grade, course) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $subject_id, $exam_date, $grade, $course]);
            header("Location: exams.php?student_id=" . $student_id);
            exit;
        }
    }
}

// Получение списка групп
$groupsStmt = $db->query("SELECT id, group_number FROM groups ORDER BY group_number");
$groups = $groupsStmt->fetchAll(PDO::FETCH_ASSOC);

// Получение списка студентов (для случая, когда студент не выбран)
$studentsStmt = $db->query("SELECT s.id, s.full_name, g.group_number 
                            FROM students s 
                            JOIN groups g ON s.group_id = g.id 
                            ORDER BY g.group_number, s.full_name");
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Функция для получения дисциплин по специализации и курсу
function getSubjects($db, $specialization, $course) {
    $stmt = $db->prepare("SELECT id, name FROM subjects WHERE specialization = ? AND course = ? ORDER BY name");
    $stmt->execute([$specialization, $course]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Редактирование экзамена' : 'Добавление экзамена' ?></title>
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
        input[type="number"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
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
        
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
    <script>
        function updateSubjects() {
            const groupSelect = document.getElementById('group_id');
            const studentSelect = document.getElementById('student_id');
            const courseSelect = document.getElementById('course');
            const subjectSelect = document.getElementById('subject_id');
            
            let groupId, studentId;
            
            // Если group_id - это скрытое поле (input), берем его value
            if (groupSelect && groupSelect.tagName === 'INPUT') {
                groupId = groupSelect.value;
            } else if (groupSelect) {
                groupId = groupSelect.value;
            } else {
                groupId = '<?= $student ? $student["group_id"] : "" ?>';
            }
            
            // Если student_id - это скрытое поле (input), берем его value
            if (studentSelect && studentSelect.tagName === 'INPUT') {
                studentId = studentSelect.value;
            } else if (studentSelect) {
                studentId = studentSelect.value;
            } else {
                studentId = '<?= $student_id ?>';
            }
            
            const course = courseSelect.value;
            
            if (!groupId || !studentId || !course) {
                subjectSelect.innerHTML = '<option value="">Сначала выберите группу, студента и курс</option>';
                return;
            }
            
            // Получаем специализацию группы через AJAX
            fetch('get_subjects.php?group_id=' + groupId + '&course=' + course)
                .then(response => response.json())
                .then(data => {
                    subjectSelect.innerHTML = '<option value="">Выберите дисциплину</option>';
                    data.forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject.id;
                        option.textContent = subject.name;
                        subjectSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        // Обновляем список студентов при выборе группы
        function updateStudents() {
            const groupSelect = document.getElementById('group_id');
            const studentSelect = document.getElementById('student_id');
            const groupId = groupSelect.value;
            
            if (!groupId) {
                studentSelect.innerHTML = '<option value="">Сначала выберите группу</option>';
                return;
            }
            
            fetch('get_students.php?group_id=' + groupId)
                .then(response => response.json())
                .then(data => {
                    studentSelect.innerHTML = '<option value="">Выберите студента</option>';
                    data.forEach(student => {
                        const option = document.createElement('option');
                        option.value = student.id;
                        option.textContent = student.full_name;
                        studentSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        // При загрузке страницы, если студент уже выбран, обновляем дисциплины
        window.onload = function() {
            const courseSelect = document.getElementById('course');
            courseSelect.onchange = function() {
                updateSubjects();
            };
            
            <?php if ($exam): ?>
                document.getElementById('course').value = <?= $exam['course'] ?>;
                <?php if ($student_id && $student): ?>
                    // Загружаем дисциплины для выбранного курса
                    setTimeout(function() {
                        updateSubjects();
                        setTimeout(function() {
                            document.getElementById('subject_id').value = <?= $exam['subject_id'] ?>;
                        }, 300);
                    }, 100);
                <?php endif; ?>
            <?php endif; ?>
        };
    </script>
</head>
<body>
    <div class="container">
        <h1><?= $isEdit ? 'Редактирование экзамена' : 'Добавление экзамена' ?></h1>
        
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="errors">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($student): ?>
            <div class="info">
                <strong>Студент:</strong> <?= htmlspecialchars($student['full_name']) ?><br>
                <strong>Группа:</strong> <?= htmlspecialchars($student['group_number']) ?><br>
                <strong>Специализация:</strong> <?= htmlspecialchars($student['specialization']) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?php if ($student_id): ?>
                <input type="hidden" name="student_id" id="student_id" value="<?= $student_id ?>">
                <input type="hidden" name="group_id" id="group_id" value="<?= $student['group_id'] ?>">
            <?php else: ?>
                <div class="form-group">
                    <label for="group_id">Группа *</label>
                    <select name="group_id" id="group_id" onchange="updateStudents()" required>
                        <option value="">Выберите группу</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= $group['id'] ?>">
                                <?= htmlspecialchars($group['group_number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="student_id">Студент *</label>
                    <select name="student_id" id="student_id" onchange="updateSubjects()" required>
                        <option value="">Сначала выберите группу</option>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="course">Курс *</label>
                <select name="course" id="course" onchange="updateSubjects()" required>
                    <option value="">Выберите курс</option>
                    <option value="1" <?= ($exam && $exam['course'] == 1) || (isset($_POST['course']) && $_POST['course'] == 1) ? 'selected' : '' ?>>1 курс</option>
                    <option value="2" <?= ($exam && $exam['course'] == 2) || (isset($_POST['course']) && $_POST['course'] == 2) ? 'selected' : '' ?>>2 курс</option>
                    <option value="3" <?= ($exam && $exam['course'] == 3) || (isset($_POST['course']) && $_POST['course'] == 3) ? 'selected' : '' ?>>3 курс</option>
                    <option value="4" <?= ($exam && $exam['course'] == 4) || (isset($_POST['course']) && $_POST['course'] == 4) ? 'selected' : '' ?>>4 курс</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="subject_id">Дисциплина *</label>
                <select name="subject_id" id="subject_id" required>
                    <option value="">Сначала выберите группу, студента и курс</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="exam_date">Дата экзамена *</label>
                <input type="date" name="exam_date" id="exam_date" 
                       value="<?= htmlspecialchars($exam['exam_date'] ?? $_POST['exam_date'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="grade">Оценка *</label>
                <select name="grade" id="grade" required>
                    <option value="">Выберите оценку</option>
                    <option value="5" <?= ($exam && $exam['grade'] == 5) || (isset($_POST['grade']) && $_POST['grade'] == 5) ? 'selected' : '' ?>>5 (Отлично)</option>
                    <option value="4" <?= ($exam && $exam['grade'] == 4) || (isset($_POST['grade']) && $_POST['grade'] == 4) ? 'selected' : '' ?>>4 (Хорошо)</option>
                    <option value="3" <?= ($exam && $exam['grade'] == 3) || (isset($_POST['grade']) && $_POST['grade'] == 3) ? 'selected' : '' ?>>3 (Удовлетворительно)</option>
                    <option value="2" <?= ($exam && $exam['grade'] == 2) || (isset($_POST['grade']) && $_POST['grade'] == 2) ? 'selected' : '' ?>>2 (Неудовлетворительно)</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-submit">
                <?= $isEdit ? 'Сохранить изменения' : 'Добавить экзамен' ?>
            </button>
        </form>
        
        <a href="<?= $student_id ? 'exams.php?student_id=' . $student_id : 'index.php' ?>" class="btn btn-cancel">Отмена</a>
    </div>
</body>
</html>

