<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('../');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $row = db_one("SELECT photo FROM students WHERE id = ?", 'i', [$id]);
    $r = db_exec("DELETE FROM students WHERE id = ?", 'i', [$id]);
    if ($r === true) {
        if ($row) {
            delete_student_photo($row['photo']);
        }
        flash('success', 'Student deleted.');
    } else {
        flash('error', $r);
    }
}
redirect('index.php');
