-- Manual upgrade for an existing database (the app also does this automatically on first page load).
USE student_management;
ALTER TABLE students
    ADD COLUMN blood_group VARCHAR(5) NULL,
    ADD COLUMN guardian_name VARCHAR(120) NULL,
    ADD COLUMN guardian_relation VARCHAR(40) NULL,
    ADD COLUMN guardian_phone VARCHAR(30) NULL,
    ADD COLUMN photo VARCHAR(60) NULL;
