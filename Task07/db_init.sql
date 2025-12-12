DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS groups;

CREATE TABLE groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_number TEXT NOT NULL UNIQUE,
    specialization TEXT NOT NULL,
    graduation_year INTEGER NOT NULL CHECK(graduation_year > 2000 AND graduation_year <= 2100)
);

CREATE TABLE students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id INTEGER NOT NULL,
    full_name TEXT NOT NULL,
    gender TEXT NOT NULL CHECK(gender IN ('М', 'Ж', 'Мужской', 'Женский')),
    birth_date TEXT NOT NULL,
    student_id TEXT NOT NULL UNIQUE,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
);

CREATE INDEX idx_groups_number ON groups(group_number);
CREATE INDEX idx_groups_graduation_year ON groups(graduation_year);
CREATE INDEX idx_students_group_id ON students(group_id);
CREATE INDEX idx_students_full_name ON students(full_name);

INSERT INTO groups (group_number, specialization, graduation_year) VALUES
('ПИ-304', 'Программная инженерия', 2025),
('ПИ-305', 'Программная инженерия', 2025);

INSERT INTO students (group_id, full_name, gender, birth_date, student_id) VALUES
(1, 'Зубков Роман Сергеевич', 'М', '2005-01-15', '000098770'),
(1, 'Иванов Максим Александрович', 'М', '2005-03-20', '000099967'),
(1, 'Ивенин Артём Андреевич', 'М', '2005-05-10', '000100275'),
(1, 'Казейкин Иван Иванович', 'М', '2005-07-25', '000096158'),
(1, 'Кочнев Артем Алексеевич', 'М', '2005-09-12', '000096917'),
(1, 'Логунов Илья Сергеевич', 'М', '2005-11-08', '000096940'),
(1, 'Макарова Юлия Сергеевна', 'Ж', '2005-02-18', '000096527'),
(1, 'Маклаков Сергей Александрович', 'М', '2005-04-22', '000099515'),
(1, 'Маскинскова Наталья Сергеевна', 'Ж', '2005-06-30', '000100224'),
(1, 'Мукасеев Дмитрий Александрович', 'М', '2005-07-28', '000097089'),
(1, 'Наумкин Владислав Валерьевич', 'М', '2005-10-05', '000099292'),
(1, 'Паркаев Василий Александрович', 'М', '2005-12-20', '000098067'),
(1, 'Полковников Дмитрий Александрович', 'М', '2005-01-28', '000096761'),
(2, 'Пузаков Дмитрий Александрович', 'М', '2005-02-10', '000100239'),
(2, 'Пшеницына Полина Алексеевна', 'Ж', '2005-04-15', '000099694'),
(2, 'Пяткин Игорь Алексеевич', 'М', '2005-06-22', '000098931'),
(2, 'Рыбаков Евгений Геннадьевич', 'М', '2005-08-30', '000096791'),
(2, 'Рыжкин Владислав Дмитриевич', 'М', '2005-10-12', '000096747'),
(2, 'Рябченко Александра Станиславовна', 'Ж', '2005-12-05', '000100758'),
(2, 'Томилин Илья Петрович', 'М', '2002-03-18', '000087957'),
(2, 'Тульсков Илья Андреевич', 'М', '2005-05-25', '000099785'),
(2, 'Фирстов Артём Александрович', 'М', '2005-07-08', '000100815'),
(2, 'Четайкин Владислав Александрович', 'М', '2005-09-14', '000103535'),
(2, 'Шарунов Максим Игоревич', 'М', '2005-11-20', '000097456'),
(2, 'Шушев Денис Сергеевич', 'М', '2005-01-30', '000101046');

