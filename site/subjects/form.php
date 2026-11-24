<?php
require_once __DIR__ . '/../includes/functions.php';
$base = '../';
require_login($base);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$sub = ['subject_code' => '', 'subject_name' => '', 'class' => '', 'teacher_id' => ''];

if ($id > 0) {
    $row = db_one("SELECT * FROM subjects WHERE id = ?", 'i', [$id]);
    if (!$row) {
        flash('error', 'Subject not found.');
        redirect('index.php');
    }
    $sub = array_map(function ($v) { return $v ?? ''; }, $row);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $sub['subject_code'] = trim($_POST['subject_code'] ?? '');
    $sub['subject_name'] = trim($_POST['subject_name'] ?? '');
    $sub['class'] = trim($_POST['class'] ?? '');
    $sub['teacher_id'] = (int)($_POST['teacher_id'] ?? 0) ?: '';

    if ($sub['subject_code'] === '' || $sub['subject_name'] === '') {
        $error = 'Subject code and name are required.';
    } else {
        $teacher = $sub['teacher_id'] === '' ? null : (int)$sub['teacher_id'];
        if ($id > 0) {
            $r = db_exec("UPDATE subjects SET subject_code = ?, subject_name = ?, class = ?, teacher_id = ? WHERE id = ?",
                'sssii', [$sub['subject_code'], $sub['subject_name'], $sub['class'], $teacher, $id]);
        } else {
            $r = db_exec("INSERT INTO subjects (subject_code, subject_name, class, teacher_id) VALUES (?, ?, ?, ?)",
                'sssi', [$sub['subject_code'], $sub['subject_name'], $sub['class'], $teacher]);
        }
        if ($r === true) {
            flash('success', $id > 0 ? 'Subject updated.' : 'Subject added.');
            redirect('index.php');
        }
        $error = $r;
    }
}

$teacher_opts = [];
foreach (db_all("SELECT id, teacher_id, full_name FROM teachers ORDER BY full_name") as $t) {
    $teacher_opts[$t['id']] = $t['full_name'] . ' (' . $t['teacher_id'] . ')';
}

$page_title = $id > 0 ? 'Edit Subject' : 'Add Subject';
$active = 'subjects';
include __DIR__ . '/../includes/header.php';
?>

<div class="content">
    <h2>Subject Information</h2>
    <?php if ($error !== ''): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="POST" class="form-grid">
        <?= csrf_field() ?>
        <?php if ($id > 0): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

        <?= input_field('Subject Code', 'subject_code', $sub['subject_code'], 'text', true, 'maxlength="30"') ?>
        <?= input_field('Subject Name', 'subject_name', $sub['subject_name'], 'text', true, 'maxlength="120"') ?>
        <?= input_field('Class', 'class', $sub['class'], 'text', false, 'maxlength="50" list="class-options"') ?>
        <?= select_field('Teacher', 'teacher_id', $teacher_opts, $sub['teacher_id'], false, '-- Unassigned --') ?>

        <datalist id="class-options">
            <?php foreach (class_list() as $cl): ?><option value="<?= e($cl) ?>"><?php endforeach; ?>
        </datalist>

        <div class="form-actions">
            <button type="submit" class="btn"><?= $id > 0 ? 'Update Subject' : 'Save Subject' ?></button>
            <a href="index.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
