<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_form.php';
$base = '../';
require_login($base);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$student = $id > 0 ? db_one("SELECT * FROM students WHERE id = ?", 'i', [$id]) : null;

if (!$student) {
    flash('error', 'Student not found.');
    redirect('index.php');
}

$old_photo = (string)($student['photo'] ?? '');
$s = array_merge(student_defaults(), array_map(function ($v) { return $v ?? ''; }, $student));
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $s = student_from_post();
    $s['photo'] = $old_photo;
    $error = student_validate($s);

    $new_photo = null;
    if ($error === '') {
        $new_photo = upload_student_photo('photo', $error);
    }

    if ($error === '') {
        $final_photo = $old_photo !== '' ? $old_photo : null;
        if (!empty($_POST['remove_photo'])) {
            $final_photo = null;
        }
        if ($new_photo !== null) {
            $final_photo = $new_photo;
        }

        $r = db_exec(
            "UPDATE students SET student_id = ?, full_name = ?, gender = ?, date_of_birth = ?, blood_group = ?,
             phone = ?, email = ?, address = ?, class = ?, section = ?, admission_date = ?,
             guardian_name = ?, guardian_relation = ?, guardian_phone = ?, photo = ? WHERE id = ?",
            str_repeat('s', 15) . 'i',
            [$s['student_id'], $s['full_name'], $s['gender'], null_if_empty($s['date_of_birth']),
             null_if_empty($s['blood_group']), $s['phone'], $s['email'], $s['address'], $s['class'], $s['section'],
             null_if_empty($s['admission_date']), $s['guardian_name'], $s['guardian_relation'], $s['guardian_phone'],
             $final_photo, $id]
        );
        if ($r === true) {
            if ($old_photo !== '' && $final_photo !== $old_photo) {
                delete_student_photo($old_photo);
            }
            flash('success', 'Student updated successfully.');
            redirect('profile.php?id=' . $id);
        }
        $error = $r;
        delete_student_photo($new_photo);
    }
}

$page_title = 'Edit Student';
$active = 'students';
include __DIR__ . '/../includes/header.php';
?>

<div class="content">
    <h2>Edit: <?= e($student['full_name']) ?></h2>
    <?php if ($error !== ''): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php student_form_render($s, 'Update Student', $id); ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
