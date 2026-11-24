<?php
require_once __DIR__ . '/includes/functions.php';
$base = '';
require_login($base);

$total_students = (int)db_value("SELECT COUNT(*) FROM students");
$total_teachers = (int)db_value("SELECT COUNT(*) FROM teachers");
$total_subjects = (int)db_value("SELECT COUNT(*) FROM subjects");

// Today's attendance
$att = db_one("SELECT COUNT(*) AS total,
                      COALESCE(SUM(status IN ('Present','Late')), 0) AS attended
               FROM attendance WHERE attendance_date = CURDATE()");
$att_total = (int)($att['total'] ?? 0);
$att_pct = $att_total > 0 ? round(((int)$att['attended'] / $att_total) * 100) : null;

// Fees
$fee = db_one("SELECT COALESCE(SUM(amount), 0) AS billed,
                      COALESCE(SUM(LEAST(paid, amount)), 0) AS collected
               FROM fees");
$outstanding = (float)$fee['billed'] - (float)$fee['collected'];

$recent = db_all("SELECT id, student_id, full_name, gender, class, phone FROM students ORDER BY id DESC LIMIT 5");

$by_class = db_all("SELECT class, COUNT(*) AS n FROM students
                    WHERE class IS NOT NULL AND class <> '' GROUP BY class ORDER BY class");
$max_class = 1;
foreach ($by_class as $bc) {
    $max_class = max($max_class, (int)$bc['n']);
}

$dues = db_all("SELECT f.id, f.fee_type, f.amount, f.paid, f.due_date, s.full_name
                FROM fees f JOIN students s ON s.id = f.student_id
                WHERE f.paid < f.amount AND f.due_date IS NOT NULL
                ORDER BY f.due_date ASC LIMIT 5");

$page_title = 'Dashboard';
$active = 'dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="dashboard">
    <div class="card"><h3>Total Students</h3><h1><?= $total_students ?></h1></div>
    <div class="card purple"><h3>Total Teachers</h3><h1><?= $total_teachers ?></h1></div>
    <div class="card amber"><h3>Total Subjects</h3><h1><?= $total_subjects ?></h1></div>
    <div class="card green">
        <h3>Today's Attendance</h3>
        <h1><?= $att_pct === null ? '—' : $att_pct . '%' ?></h1>
        <small><?= $att_total > 0 ? $att_total . ' students marked' : 'Not marked yet' ?></small>
    </div>
    <div class="card red">
        <h3>Fees Outstanding</h3>
        <h1><?= money($outstanding) ?></h1>
        <small>Collected: <?= money($fee['collected']) ?></small>
    </div>
</div>

<div class="content">
    <div class="section-header">
        <h2>Recent Students</h2>
        <a href="students/add.php" class="btn">+ Add Student</a>
    </div>
    <div class="table-container">
        <table>
            <thead><tr><th>ID</th><th>Student ID</th><th>Full Name</th><th>Gender</th><th>Class</th><th>Phone</th></tr></thead>
            <tbody>
            <?php if ($recent): foreach ($recent as $r): ?>
                <tr>
                    <td><?= (int)$r['id'] ?></td>
                    <td><?= e($r['student_id']) ?></td>
                    <td><a href="students/profile.php?id=<?= (int)$r['id'] ?>"><?= e($r['full_name']) ?></a></td>
                    <td><?= e($r['gender']) ?></td>
                    <td><?= e($r['class']) ?></td>
                    <td><?= e($r['phone']) ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="6" class="empty">No students found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="grid-2">
    <div class="content">
        <h2>Students by Class</h2>
        <?php if ($by_class): foreach ($by_class as $bc): ?>
            <div class="bar-row">
                <div class="label"><?= e($bc['class']) ?></div>
                <div class="bar"><span style="width:<?= round(($bc['n'] / $max_class) * 100) ?>%"></span></div>
                <div class="count"><?= (int)$bc['n'] ?></div>
            </div>
        <?php endforeach; else: ?>
            <p class="muted">No class data yet.</p>
        <?php endif; ?>
    </div>

    <div class="content">
        <div class="section-header"><h2>Upcoming / Overdue Fees</h2><a href="fees/index.php" class="btn btn-light">All fees</a></div>
        <?php if ($dues): ?>
            <div class="table-container"><table style="min-width:0">
                <thead><tr><th>Student</th><th>Fee</th><th>Balance</th><th>Due</th></tr></thead>
                <tbody>
                <?php foreach ($dues as $d):
                    $overdue = $d['due_date'] < date('Y-m-d'); ?>
                    <tr>
                        <td><?= e($d['full_name']) ?></td>
                        <td><?= e($d['fee_type']) ?></td>
                        <td><?= money($d['amount'] - $d['paid']) ?></td>
                        <td><?= fmt_date($d['due_date']) ?> <?= $overdue ? badge('Overdue', 'red') : '' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php else: ?>
            <p class="muted">No pending fees. 🎉</p>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
