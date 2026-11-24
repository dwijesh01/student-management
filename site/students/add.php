<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_form.php';
$base = '../';
require_login($base);

$s = student_defaults();
$s['photo'] = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $s = student_from_post();
    $s['photo'] = '';
    $error = student_validate($s);

    $photo = null;
    if ($error === '') {
        $photo = upload_student_photo('photo', $error);
    }

    if ($error === '') {
        $r = db_exec(
            "INSERT INTO students
             (student_id, full_name, gender, date_of_birth, blood_group, phone, email, address, class, section,
              admission_date, guardian_name, guardian_relation, guardian_phone, photo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            str_repeat('s', 15),
            [$s['student_id'], $s['full_name'], $s['gender'], null_if_empty($s['date_of_birth']),
             null_if_empty($s['blood_group']), $s['phone'], $s['email'], $s['address'], $s['class'], $s['section'],
             null_if_empty($s['admission_date']), $s['guardian_name'], $s['guardian_relation'], $s['guardian_phone'], $photo]
        );
        if ($r === true) {
            flash('success', 'Student added successfully.');
            redirect('index.php');
        }
        $error = $r;
        delete_student_photo($photo); // don't leave an orphan file behind
    }
}

$page_title = 'Add Student';
$active = 'students';
include __DIR__ . '/../includes/header.php';
?>

<div class="content">
    <h2>Student Information</h2>
    <?php if ($error !== ''): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php student_form_render($s, 'Save Student'); ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
