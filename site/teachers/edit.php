<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_form.php';
$base = '../';
require_login($base);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$teacher = $id > 0 ? db_one("SELECT * FROM teachers WHERE id = ?", 'i', [$id]) : null;

if (!$teacher) {
    flash('error', 'Teacher not found.');
    redirect('index.php');
}

$t = array_merge(teacher_defaults(), array_map(function ($v) { return $v ?? ''; }, $teacher));
// created_at may be DATETIME in older databases; the form only needs the date part
$t['created_at'] = substr((string)$t['created_at'], 0, 10);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $t = teacher_from_post();
    $error = teacher_validate($t);

    if ($error === '') {
        $r = db_exec(
            "UPDATE teachers SET teacher_id = ?, full_name = ?, phone = ?, email = ?, address = ?, subject = ?, created_at = ?
             WHERE id = ?",
            'sssssssi',
            [$t['teacher_id'], $t['full_name'], $t['phone'], $t['email'], $t['address'], $t['subject'],
             null_if_empty($t['created_at']), $id]
        );
        if ($r === true) {
            flash('success', 'Teacher updated successfully.');
            redirect('index.php');
        }
        $error = $r;
    }
}

$page_title = 'Edit Teacher';
$active = 'teachers';
include __DIR__ . '/../includes/header.php';
?>

<div class="content">
    <h2>Edit: <?= e($teacher['full_name']) ?></h2>
    <?php if ($error !== ''): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php teacher_form_render($t, 'Update Teacher', $id); ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
