<?php
require_once __DIR__ . '/../includes/functions.php';
$base = '../';
require_login($base);

$student_id = (int)($_GET['student'] ?? 0);
$exam = trim($_GET['exam'] ?? '');

$student = $student_id > 0 ? db_one("SELECT * FROM students WHERE id = ?", 'i', [$student_id]) : null;

$exams = [];
$rows = [];
if ($student) {
    $exams = array_column(db_all("SELECT DISTINCT exam_name FROM results WHERE student_id = ? ORDER BY exam_name", 'i', [$student_id]), 'exam_name');
    if ($exam === '' && $exams) {
        $exam = $exams[0];
    }
    if ($exam !== '') {
        $rows = db_all("SELECT r.*, sub.subject_name, sub.subject_code
                        FROM results r JOIN subjects sub ON sub.id = r.subject_id
                        WHERE r.student_id = ? AND r.exam_name = ? ORDER BY sub.subject_name", 'is', [$student_id, $exam]);
    }
}

$sum_obtained = 0;
$sum_total = 0;
$any_fail = false;
foreach ($rows as $r) {
    $sum_obtained += (float)$r['marks_obtained'];
    $sum_total += (float)$r['total_marks'];
    if ($r['total_marks'] > 0 && ($r['marks_obtained'] / $r['total_marks']) * 100 < 40) {
        $any_fail = true;
    }
}
$overall = $sum_total > 0 ? ($sum_obtained / $sum_total) * 100 : 0;

$student_opts = [];
foreach (db_all("SELECT id, student_id, full_name, class FROM students ORDER BY full_name") as $s) {
    $student_opts[$s['id']] = $s['full_name'] . ' (' . $s['student_id'] . ')';
}

$page_title = 'Report Card';
$active = 'results';
include __DIR__ . '/../includes/header.php';
?>

<div class="content no-print">
    <h2>Select Student</h2>
    <form method="GET" class="filters">
        <?= select_field('Student', 'student', $student_opts, $student_id ?: '', true) ?>
        <?php if ($exams): ?>
        <div class="form-group"><label>Exam</label>
            <select name="exam">
                <?php foreach ($exams as $ex): ?>
                    <option value="<?= e($ex) ?>" <?= $ex === $exam ? 'selected' : '' ?>><?= e($ex) ?></option>
                <?php endforeach; ?>
            </select></div>
        <?php endif; ?>
        <button type="submit" class="btn">Show</button>
        <a href="index.php" class="btn btn-light">Back to Results</a>
    </form>
</div>

<?php if ($student): ?>
<div class="content">
    <div class="report">
        <div class="report-head">
            <h2><?= e(APP_NAME) ?></h2>
            <div class="muted">Report Card — <?= e($exam !== '' ? $exam : 'No exam selected') ?></div>
        </div>

        <div class="report-meta">
            <div><b>Student:</b> <?= e($student['full_name']) ?></div>
            <div><b>Student ID:</b> <?= e($student['student_id']) ?></div>
            <div><b>Class:</b> <?= e($student['class']) ?> <?= e($student['section']) ?></div>
            <div><b>Date:</b> <?= fmt_date(date('Y-m-d')) ?></div>
        </div>

        <?php if ($rows): ?>
        <div class="table-container">
        <table style="min-width:0">
            <thead><tr><th>Subject</th><th>Marks</th><th>Total</th><th>%</th><th>Grade</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r):
                $pct = $r['total_marks'] > 0 ? ($r['marks_obtained'] / $r['total_marks']) * 100 : 0; ?>
                <tr>
                    <td><?= e($r['subject_name']) ?></td>
                    <td><?= e(floatval($r['marks_obtained'])) ?></td>
                    <td><?= e(floatval($r['total_marks'])) ?></td>
                    <td><?= round($pct, 1) ?>%</td>
                    <td><?= e(grade_for($pct)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td><?= e($sum_obtained) ?></td>
                    <td><?= e($sum_total) ?></td>
                    <td><?= round($overall, 1) ?>%</td>
                    <td><?= e(grade_for($overall)) ?></td>
                </tr>
            </tfoot>
        </table>
        </div>

        <p class="big-grade" style="margin-top:20px">
            Result: <?= $any_fail ? badge('FAIL', 'red') : badge('PASS', 'green') ?>
            <span class="muted" style="font-weight:normal">(pass requires at least 40% in every subject)</span>
        </p>

        <div class="no-print" style="margin-top:20px">
            <button type="button" class="btn" data-print>Print Report Card</button>
        </div>
        <?php else: ?>
            <p class="muted">No results recorded for this student yet.</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
