<?php
require_once __DIR__ . '/../includes/functions.php';
$base = '../';
require_login($base);

$month = trim($_GET['month'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
    $month = date('Y-m');
}
$class = trim($_GET['class'] ?? '');

$start = $month . '-01';
$end = date('Y-m-t', strtotime($start));

$types = 'ss';
$params = [$start, $end];
$w = '';
if ($class !== '') {
    $w = 'WHERE s.class = ?';
    $types .= 's';
    $params[] = $class;
}

$rows = db_all(
    "SELECT s.id, s.student_id AS code, s.full_name, s.class,
            COUNT(a.id) AS total,
            COALESCE(SUM(a.status = 'Present'), 0) AS present,
            COALESCE(SUM(a.status = 'Absent'), 0) AS absent,
            COALESCE(SUM(a.status = 'Late'), 0) AS late,
            COALESCE(SUM(a.status = 'Leave'), 0) AS leave_days
     FROM students s
     LEFT JOIN attendance a ON a.student_id = s.id AND a.attendance_date BETWEEN ? AND ?
     $w
     GROUP BY s.id, s.student_id, s.full_name, s.class
     ORDER BY s.class, s.full_name", $types, $params);

$classes = class_list();

$page_title = 'Attendance Report';
$active = 'attendance';
include __DIR__ . '/../includes/header.php';
?>

<div class="content">
    <div class="section-header">
        <h2>Monthly Report — <?= e(date('F Y', strtotime($start))) ?></h2>
        <div class="actions-row no-print">
            <button type="button" class="btn btn-light" data-print>Print</button>
            <a href="index.php" class="btn btn-light">Mark Attendance</a>
        </div>
    </div>

    <form method="GET" class="filters no-print">
        <div class="form-group"><label>Month</label><input type="month" name="month" value="<?= e($month) ?>"></div>
        <div class="form-group"><label>Class</label>
            <select name="class">
                <option value="">All classes</option>
                <?php foreach ($classes as $cl): ?>
                    <option value="<?= e($cl) ?>" <?= $cl === $class ? 'selected' : '' ?>><?= e($cl) ?></option>
                <?php endforeach; ?>
            </select></div>
        <button type="submit" class="btn">Show</button>
    </form>

    <div class="table-container">
        <table>
            <thead><tr><th>Student ID</th><th>Name</th><th>Class</th><th>Present</th><th>Late</th><th>Absent</th><th>Leave</th><th>Days Marked</th><th>Attendance %</th></tr></thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $r):
                $total = (int)$r['total'];
                $pct = $total > 0 ? round((((int)$r['present'] + (int)$r['late']) / $total) * 100) : null; ?>
                <tr>
                    <td><?= e($r['code']) ?></td>
                    <td><a href="../students/profile.php?id=<?= (int)$r['id'] ?>"><?= e($r['full_name']) ?></a></td>
                    <td><?= e($r['class']) ?></td>
                    <td><?= (int)$r['present'] ?></td>
                    <td><?= (int)$r['late'] ?></td>
                    <td><?= (int)$r['absent'] ?></td>
                    <td><?= (int)$r['leave_days'] ?></td>
                    <td><?= $total ?></td>
                    <td><?php if ($pct === null): ?>—<?php else: ?>
                        <?= badge($pct . '%', $pct >= 85 ? 'green' : ($pct >= 70 ? 'amber' : 'red')) ?><?php endif; ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="9" class="empty">No students found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
