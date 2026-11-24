<?php
require_once __DIR__ . '/../includes/functions.php';
$base = '../';
require_login($base);

$q = trim($_GET['q'] ?? '');
$exam = trim($_GET['exam'] ?? '');
$class = trim($_GET['class'] ?? '');

$where = [];
$types = '';
$params = [];
if ($q !== '') {
    $where[] = "(s.full_name LIKE ? OR s.student_id LIKE ?)";
    $like = "%$q%";
    $types .= 'ss';
    array_push($params, $like, $like);
}
if ($exam !== '') {
    $where[] = "r.exam_name = ?";
    $types .= 's';
    $params[] = $exam;
}
if ($class !== '') {
    $where[] = "s.class = ?";
    $types .= 's';
    $params[] = $class;
}
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$from = "FROM results r
         JOIN students s ON s.id = r.student_id
         JOIN subjects sub ON sub.id = r.subject_id";

$total = (int)db_value("SELECT COUNT(*) $from $w", $types, $params);
$p = paginate($total, 15, (int)($_GET['page'] ?? 1));
$rows = db_all("SELECT r.*, s.full_name, s.student_id AS student_code, s.class, sub.subject_name
                $from $w ORDER BY r.id DESC LIMIT {$p['per']} OFFSET {$p['offset']}", $types, $params);

$exams = array_column(db_all("SELECT DISTINCT exam_name FROM results ORDER BY exam_name"), 'exam_name');
$classes = class_list();

$page_title = 'Results';
$active = 'results';
include __DIR__ . '/../includes/header.php';
?>

<div class="content">
    <div class="section-header">
        <h2>Exam Results (<?= $total ?>)</h2>
        <div class="actions-row">
            <a href="report.php" class="btn btn-light">Report Cards</a>
            <a href="form.php" class="btn">+ Add Result</a>
        </div>
    </div>

    <form method="GET" class="filters">
        <div class="form-group"><label>Student</label>
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Name or ID"></div>
        <div class="form-group"><label>Exam</label>
            <select name="exam"><option value="">All exams</option>
                <?php foreach ($exams as $ex): ?>
                    <option value="<?= e($ex) ?>" <?= $ex === $exam ? 'selected' : '' ?>><?= e($ex) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="form-group"><label>Class</label>
            <select name="class"><option value="">All classes</option>
                <?php foreach ($classes as $cl): ?>
                    <option value="<?= e($cl) ?>" <?= $cl === $class ? 'selected' : '' ?>><?= e($cl) ?></option>
                <?php endforeach; ?>
            </select></div>
        <button type="submit" class="btn">Filter</button>
        <a href="index.php" class="btn btn-light">Reset</a>
    </form>

    <div class="table-container">
        <table>
            <thead><tr><th>Student</th><th>Class</th><th>Exam</th><th>Subject</th><th>Marks</th><th>%</th><th>Grade</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $r):
                $pct = $r['total_marks'] > 0 ? ($r['marks_obtained'] / $r['total_marks']) * 100 : 0;
                $grade = grade_for($pct); ?>
                <tr>
                    <td><a href="report.php?student=<?= (int)$r['student_id'] ?>&amp;exam=<?= urlencode($r['exam_name']) ?>"><?= e($r['full_name']) ?></a>
                        <div class="muted"><?= e($r['student_code']) ?></div></td>
                    <td><?= e($r['class']) ?></td>
                    <td><?= e($r['exam_name']) ?></td>
                    <td><?= e($r['subject_name']) ?></td>
                    <td><?= e(floatval($r['marks_obtained'])) ?> / <?= e(floatval($r['total_marks'])) ?></td>
                    <td><?= round($pct, 1) ?>%</td>
                    <td><?= badge($grade, $grade === 'F' ? 'red' : ($pct >= 80 ? 'green' : 'blue')) ?></td>
                    <td class="row-actions">
                        <a href="form.php?id=<?= (int)$r['id'] ?>">Edit</a>
                        <?= delete_form('delete.php', (int)$r['id'], 'Delete this result?') ?>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="8" class="empty">No results found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?= pagination_html($p, ['q' => $q, 'exam' => $exam, 'class' => $class]) ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
