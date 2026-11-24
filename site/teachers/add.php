<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_form.php';
$base = '../';
require_login($base);

$t = teacher_defaults();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $t = teacher_from_post();
    $error = teacher_validate($t);

    if ($error === '') {
        $joined = $t['created_at'] !== '' ? $t['created_at'] : date('Y-m-d');
        $r = db_exec(
            "INSERT INTO teachers (teacher_id, full_name, phone, email, address, subject, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            'sssssss',
            [$t['teacher_id'], $t['full_name'], $t['phone'], $t['email'], $t['address'], $t['subject'], $joined]
        );
        if ($r === true) {
            flash('success', 'Teacher added successfully.');
            redirect('index.php');
        }
        $error = $r;
    }
}

$page_title = 'Add Teacher';
$active = 'teachers';
include __DIR__ . '/../includes/header.php';
?>

<div class="content">
    <h2>Teacher Information</h2>
    <?php if ($error !== ''): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php teacher_form_render($t, 'Save Teacher'); ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
