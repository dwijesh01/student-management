-- Student Management System schema (safe to re-run: uses IF NOT EXISTS)
CREATE DATABASE IF NOT EXISTS student_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_management;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(30) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    gender VARCHAR(10) NOT NULL,
    date_of_birth DATE NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(120) NULL,
    address VARCHAR(255) NULL,
    class VARCHAR(50) NULL,
    section VARCHAR(20) NULL,
    admission_date DATE NULL,
    blood_group VARCHAR(5) NULL,
    guardian_name VARCHAR(120) NULL,
    guardian_relation VARCHAR(40) NULL,
    guardian_phone VARCHAR(30) NULL,
    photo VARCHAR(60) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id VARCHAR(30) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(120) NULL,
    address VARCHAR(255) NULL,
    subject VARCHAR(100) NULL,
    created_at DATE NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(30) NOT NULL UNIQUE,
    subject_name VARCHAR(120) NOT NULL,
    class VARCHAR(50) NULL,
    teacher_id INT NULL,
    CONSTRAINT fk_subject_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('Present','Absent','Late','Leave') NOT NULL DEFAULT 'Present',
    remarks VARCHAR(255) NULL,
    UNIQUE KEY uq_student_day (student_id, attendance_date),
    CONSTRAINT fk_att_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    exam_name VARCHAR(100) NOT NULL,
    marks_obtained DECIMAL(6,2) NOT NULL,
    total_marks DECIMAL(6,2) NOT NULL DEFAULT 100,
    exam_date DATE NULL,
    UNIQUE KEY uq_result (student_id, subject_id, exam_name),
    CONSTRAINT fk_res_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_res_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_type VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    paid DECIMAL(10,2) NOT NULL DEFAULT 0,
    due_date DATE NULL,
    paid_date DATE NULL,
    remarks VARCHAR(255) NULL,
    CONSTRAINT fk_fee_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Default admin login:  username: admin   password: admin123
-- CHANGE THE PASSWORD after your first login (sidebar > Change Password).
-- ---------------------------------------------------------------
INSERT IGNORE INTO admins (username, password) VALUES ('admin', '$6$sms2026salt$nyG.strZGBqZ38EzLmS2lXUJd9hzUrhNCx2WRQLPaSDEfFSx6IdZPt8dZvR7FjucAZrpJRmrgBpmlKl5NG4hF.');
