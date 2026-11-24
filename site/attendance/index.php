<?php
require_once __DIR__ . '/../includes/functions.php';
$base = '../';
require_login($base);

$statuses = ['Present' => 'p', 'Absent' => 'a', 'Late' => 'l', 'Leave' => 'v'];
$today = date('Y-m-d');

$classes = class_list();
$class = trim($_GET['class'] ?? $_POST['class'] ?? '');
if ($class === '' && $classes) {
    $class = $classes[0];
}
$date = trim($_GET['date'] ?? $_POST['date'] ?? $today);
if ($date === '' || !valid_date($date)) {
    $date = $today;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if ($date > $today) {
        $error = 'You cannot mark attendance for a future date.';
    } else {
        $submitted = $_POST['status'] ?? [];
        $remarks = $_POST['remarks'] ?? [];
        $saved = 0;

        // Only accept students that actually belong to the selected class
        $valid_ids = array_column(db_all("SELECT id FROM students WHERE class = ?", 's', [$class]), 'id');
        $valid_ids = array_map('intval', $valid_ids);

        global $conn;
        mysqli_begin_transaction($conn);

        foreach ($submitted as $sid => $st) {
            $sid = (int)$sid;
            if (!in_array($sid, $valid_ids, true) || !is_string($st) || !isset($statuses[$st])) {
                continue;
            }
            $note = mb_substr(trim((string)($remarks[$sid] ?? '')), 0, 255);
            $r = db_exec(
                "INSERT INTO attendance (student_id, attendance_date, status, remarks) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks)",
                'isss', [$sid, $date, $st, $note]
            );
            if ($r !== true) {
                $error = $r;
                break;
            }
            $saved++;
        }

        if ($error === '') {
            mysqli_commit($conn);
            flash('success', "Attendance saved for $saved student(s) on " . fmt_date($date) . '.');
            redirect('index.php?' . http_build_query(['class' => $class, 'date' => $date]));
        }
        mysqli_rollback($conn);
    }
}

$students = [];
if ($class !== '') {
    $students = db_all(
        "SELECT s.id, s.student_id AS code, s.full_name, s.section, a.status, a.remarks
         FROM students s
         LEFT JOIN attendance a ON a.student_id = s.id AND a.attendance_date = ?
         WHERE s.class = ?
         ORDER BY s.full_name", 'ss', [$date, $class]);
}

$already = 0;
foreach ($students as $st) {
    if ($st['status'] !== null) { $already++; }
}

$page_title = 'Attendance';
$active = 'attendance';
include __DIR__ . '/../includes/header.php';
?>

<div class="content">
    <div class="section-header">
        <h2>Mark Attendance</h2>
        <a href="report.php" class="btn btn-light">Monthly Report</a>
    </div>

    <?php if ($error !== ''): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <?php if (!$classes): ?>
        <div class="alert alert-info">No classes yet. <a href="../students/add.php">Add students</a> with a class first.</div>
    <?php else: ?>

    <form method="GET" class="filters">
        <div class="form-group"><label>Class</label>
            <select name="class">
                <?php foreach ($classes as $cl): ?>
                    <option value="<?= e($cl) ?>" <?= $cl === $class ? 'selected' : '' ?>><?= e($cl) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="form-group"><label>Date</label>
            <input type="date" name="date" value="<?= e($date) ?>" max="<?= e($today) ?>"></div>
        <button type="submit" class="btn">Load</button>
    </form>

    <?php if ($students): ?>
        <p class="muted" style="margin-bottom:12px">
            <?= e($class) ?> · <?= fmt_date($date) ?> ·
            <?= $already > 0 ? "$already of " . count($students) . ' already marked (editing existing records)' : 'not marked yet — everyone defaults to Present' ?>
        </p>

        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="class" value="<?= e($class) ?>">
            <input type="hidden" name="date" value="<?= e($date) ?>">

            <div class="actions-row no-print" style="margin-bottom:12px">
                <button type="button" class="btn btn-light" data-mark-all="Present">All Present</button>
                <button type="button" class="btn btn-light" data-mark-all="Absent">All Absent</button>
            </div>

            <div class="table-container">
                <table>
                    <thead><tr><th>#</th><th>Student ID</th><th>Name</th><th>Section</th><th>Status</th><th>Remarks</th></tr></thead>
                    <tbody>
                    <?php foreach ($students as $i => $st):
                        $current = $st['status'] ?? 'Present'; ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($st['code']) ?></td>
                            <td><?= e($st['full_name']) ?></td>
                            <td><?= e($st['section']) ?></td>
                            <td>
                                <div class="radio-group">
                                    <?php foreach ($statuses as $label => $cls): ?>
                                        <label>
                                            <input type="radio" name="status[<?= (int)$st['id'] ?>]" value="<?= e($label) ?>"
                                                   <?= $current === $label ? 'checked' : '' ?>>
                                            <span class="<?= $cls ?>"><?= e($label) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td><input type="text" class="remarks-input" name="remarks[<?= (int)$st['id'] ?>]"
                                       value="<?= e($st['remarks']) ?>" maxlength="255" placeholder="Optional"></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top:20px">
                <button type="submit" class="btn btn-green">Save Attendance</button>
            </div>
        </form>
    <?php else: ?>
        <p class="muted">No students found in this class.</p>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
