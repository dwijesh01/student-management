<?php
require_once __DIR__ . '/../includes/functions.php';
$base = '../';
require_login($base);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$res = ['student_id' => '', 'subject_id' => '', 'exam_name' => '', 'marks_obtained' => '', 'total_marks' => '100', 'exam_date' => date('Y-m-d')];

if ($id > 0) {
    $row = db_one("SELECT * FROM results WHERE id = ?", 'i', [$id]);
    if (!$row) {
        flash('error', 'Result not found.');
        redirect('index.php');
    }
    $res = array_map(function ($v) { return $v ?? ''; }, $row);
    $res['marks_obtained'] = (string)floatval($res['marks_obtained']);
    $res['total_marks'] = (string)floatval($res['total_marks']);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach (['student_id', 'subject_id', 'exam_name', 'marks_obtained', 'total_marks', 'exam_date'] as $k) {
        $res[$k] = trim((string)($_POST[$k] ?? ''));
    }

    if ((int)$res['student_id'] <= 0 || (int)$res['subject_id'] <= 0 || $res['exam_name'] === '') {
        $error = 'Student, subject and exam name are required.';
    } elseif (!is_numeric($res['marks_obtained']) || !is_numeric($res['total_marks'])) {
        $error = 'Marks must be numbers.';
    } elseif ((float)$res['total_marks'] <= 0) {
        $error = 'Total marks must be greater than zero.';
    } elseif ((float)$res['marks_obtained'] < 0 || (float)$res['marks_obtained'] > (float)$res['total_marks']) {
        $error = 'Marks obtained must be between 0 and the total marks.';
    } elseif (!valid_date($res['exam_date'])) {
        $error = 'Please enter a valid exam date.';
    } else {
        $params = [(int)$res['student_id'], (int)$res['subject_id'], $res['exam_name'],
                   (float)$res['marks_obtained'], (float)$res['total_marks'], null_if_empty($res['exam_date'])];
        if ($id > 0) {
            $params[] = $id;
            $r = db_exec("UPDATE results SET student_id = ?, subject_id = ?, exam_name = ?, marks_obtained = ?,
                          total_marks = ?, exam_date = ? WHERE id = ?", 'iisddsi', $params);
        } else {
            $r = db_exec("INSERT INTO results (student_id, subject_id, exam_name, marks_obtained, total_marks, exam_date)
                          VALUES (?, ?, ?, ?, ?, ?)", 'iisdds', $params);
        }
        if ($r === true) {
            flash('success', $id > 0 ? 'Result updated.' : 'Result added.');
            redirect('index.php');
        }
        $error = $r;
    }
}

$student_opts = [];
foreach (db_all("SELECT id, student_id, full_name, class FROM students ORDER BY full_name") as $s) {
    $student_opts[$s['id']] = $s['full_name'] . ' (' . $s['student_id'] . ($s['class'] ? ' · ' . $s['class'] : '') . ')';
}
$subject_opts = [];
foreach (db_all("SELECT id, subject_code, subject_name, class FROM subjects ORDER BY subject_name") as $s) {
    $subject_opts[$s['id']] = $s['subject_name'] . ' (' . $s['subject_code'] . ($s['class'] ? ' · ' . $s['class'] : '') . ')';
}
$exam_names = array_column(db_all("SELECT DISTINCT exam_name FROM results ORDER BY exam_name"), 'exam_name');

$page_title = $id > 0 ? 'Edit Result' : 'Add Result';
$active = 'results';
include __DIR__ . '/../includes/header.php';
?>

<div class="content">
    <h2>Result Information</h2>
    <?php if ($error !== ''): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <?php if (!$student_opts || !$subject_opts): ?>
        <div class="alert alert-info">
            You need at least one <a href="../students/add.php">student</a> and one <a href="../subjects/form.php">subject</a> before adding results.
        </div>
    <?php else: ?>
    <form method="POST" class="form-grid">
        <?= csrf_field() ?>
        <?php if ($id > 0): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

        <?= select_field('Student', 'student_id', $student_opts, $res['student_id'], true) ?>
        <?= select_field('Subject', 'subject_id', $subject_opts, $res['subject_id'], true) ?>
        <?= input_field('Exam Name (e.g. Midterm 2026)', 'exam_name', $res['exam_name'], 'text', true, 'maxlength="100" list="exam-options"') ?>
        <?= input_field('Exam Date', 'exam_date', $res['exam_date'], 'date') ?>
        <?= input_field('Marks Obtained', 'marks_obtained', $res['marks_obtained'], 'number', true, 'step="0.01" min="0"') ?>
        <?= input_field('Total Marks', 'total_marks', $res['total_marks'], 'number', true, 'step="0.01" min="1"') ?>

        <datalist id="exam-options">
            <?php foreach ($exam_names as $ex): ?><option value="<?= e($ex) ?>"><?php endforeach; ?>
        </datalist>

        <div class="form-actions">
            <button type="submit" class="btn"><?= $id > 0 ? 'Update Result' : 'Save Result' ?></button>
            <a href="index.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
