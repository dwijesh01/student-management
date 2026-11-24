<?php
require_once __DIR__ . '/../includes/functions.php';
$base = '../';
require_login($base);

$id = (int)($_GET['id'] ?? 0);
$student = $id > 0 ? db_one("SELECT * FROM students WHERE id = ?", 'i', [$id]) : null;

if (!$student) {
    flash('error', 'Student not found.');
    redirect('index.php');
}

// Age
$age = null;
if (!empty($student['date_of_birth'])) {
    try {
        $age = (new DateTime($student['date_of_birth']))->diff(new DateTime('today'))->y;
    } catch (Exception $ex) {
        $age = null;
    }
}

// Attendance
$att = db_one("SELECT COUNT(*) AS total,
                      COALESCE(SUM(status = 'Present'), 0) AS present,
                      COALESCE(SUM(status = 'Late'), 0) AS late,
                      COALESCE(SUM(status = 'Absent'), 0) AS absent,
                      COALESCE(SUM(status = 'Leave'), 0) AS leave_days
               FROM attendance WHERE student_id = ?", 'i', [$id]);
$att_total = (int)$att['total'];
$att_pct = $att_total > 0 ? round((((int)$att['present'] + (int)$att['late']) / $att_total) * 100) : null;
$recent_att = db_all("SELECT attendance_date, status, remarks FROM attendance
                      WHERE student_id = ? ORDER BY attendance_date DESC LIMIT 10", 'i', [$id]);

// Results grouped by exam
$results = db_all("SELECT r.*, sub.subject_name FROM results r
                   JOIN subjects sub ON sub.id = r.subject_id
                   WHERE r.student_id = ? ORDER BY r.exam_name, sub.subject_name", 'i', [$id]);
$by_exam = [];
$all_obtained = 0;
$all_total = 0;
foreach ($results as $r) {
    $by_exam[$r['exam_name']][] = $r;
    $all_obtained += (float)$r['marks_obtained'];
    $all_total += (float)$r['total_marks'];
}
$avg_pct = $all_total > 0 ? round(($all_obtained / $all_total) * 100, 1) : null;

// Fees
$fees = db_all("SELECT * FROM fees WHERE student_id = ? ORDER BY id DESC", 'i', [$id]);
$fee_balance = 0;
foreach ($fees as $f) {
    $fee_balance += max(0, (float)$f['amount'] - (float)$f['paid']);
}

$page_title = 'Student Profile';
$active = 'students';
include __DIR__ . '/../includes/header.php';
?>

<div class="content">
    <div class="profile-hero">
        <?= avatar_html($student, $base, 'lg') ?>
        <div style="flex:1;min-width:220px">
            <h2><?= e($student['full_name']) ?></h2>
            <div class="muted">Student ID: <b><?= e($student['student_id']) ?></b></div>
            <div class="meta">
                <?php if ($student['class']): ?><?= badge('Class ' . $student['class'] . ($student['section'] ? ' · ' . $student['section'] : ''), 'blue') ?><?php endif; ?>
                <?php if ($student['gender']): ?><?= badge($student['gender'], 'gray') ?><?php endif; ?>
                <?php if (!empty($student['blood_group'])): ?><?= badge('Blood ' . $student['blood_group'], 'red') ?><?php endif; ?>
                <?php if ($age !== null): ?><?= badge($age . ' years', 'amber') ?><?php endif; ?>
            </div>
        </div>
        <div class="actions-row no-print">
            <a href="edit.php?id=<?= $id ?>" class="btn">Edit Profile</a>
            <a href="../results/report.php?student=<?= $id ?>" class="btn btn-light">Report Card</a>
            <button type="button" class="btn btn-light" data-print>Print</button>
            <a href="index.php" class="btn btn-light">Back</a>
        </div>
    </div>
</div>

<div class="dashboard">
    <div class="card green"><h3>Attendance</h3><h1><?= $att_pct === null ? '—' : $att_pct . '%' ?></h1>
        <small><?= $att_total ?> days marked</small></div>
    <div class="card purple"><h3>Overall Result</h3><h1><?= $avg_pct === null ? '—' : $avg_pct . '%' ?></h1>
        <small><?= $avg_pct === null ? 'No results yet' : 'Grade ' . e(grade_for((float)$avg_pct)) ?></small></div>
    <div class="card <?= $fee_balance > 0 ? 'red' : 'green' ?>"><h3>Fee Balance</h3><h1><?= money($fee_balance) ?></h1>
        <small><?= $fee_balance > 0 ? 'Outstanding' : 'All clear' ?></small></div>
</div>

<div class="grid-2" style="margin-bottom:25px">
    <div class="content">
        <h2>Personal Information</h2>
        <table class="detail-table" style="min-width:0">
            <tr><th>Date of Birth</th><td><?= fmt_date($student['date_of_birth']) ?><?= $age !== null ? ' (' . $age . ' yrs)' : '' ?></td></tr>
            <tr><th>Gender</th><td><?= e($student['gender']) ?></td></tr>
            <tr><th>Blood Group</th><td><?= e($student['blood_group'] ?? '') ?: '—' ?></td></tr>
            <tr><th>Phone</th><td><?= e($student['phone']) ?: '—' ?></td></tr>
            <tr><th>Email</th><td><?= e($student['email']) ?: '—' ?></td></tr>
            <tr><th>Address</th><td><?= e($student['address']) ?: '—' ?></td></tr>
            <tr><th>Admission Date</th><td><?= fmt_date($student['admission_date']) ?></td></tr>
        </table>
    </div>
    <div class="content">
        <h2>Guardian / Emergency Contact</h2>
        <table class="detail-table" style="min-width:0">
            <tr><th>Name</th><td><?= e($student['guardian_name'] ?? '') ?: '—' ?></td></tr>
            <tr><th>Relation</th><td><?= e($student['guardian_relation'] ?? '') ?: '—' ?></td></tr>
            <tr><th>Phone</th><td><?= e($student['guardian_phone'] ?? '') ?: '—' ?></td></tr>
        </table>
    </div>
</div>

<div class="content">
    <h2>Attendance</h2>
    <p class="muted" style="margin-bottom:12px">
        Present <?= (int)$att['present'] ?> · Late <?= (int)$att['late'] ?> · Absent <?= (int)$att['absent'] ?> · Leave <?= (int)$att['leave_days'] ?>
        <?= $recent_att ? ' — last ' . count($recent_att) . ' records:' : '' ?>
    </p>
    <div class="table-container"><table>
        <thead><tr><th>Date</th><th>Status</th><th>Remarks</th></tr></thead>
        <tbody>
        <?php if ($recent_att): foreach ($recent_att as $a): ?>
            <tr><td><?= fmt_date($a['attendance_date']) ?></td><td><?= status_badge($a['status']) ?></td><td><?= e($a['remarks']) ?></td></tr>
        <?php endforeach; else: ?>
            <tr><td colspan="3" class="empty">No attendance recorded.</td></tr>
        <?php endif; ?>
        </tbody>
    </table></div>
</div>

<div class="content">
    <h2>Academic Results</h2>
    <?php if ($by_exam): foreach ($by_exam as $exam => $rows):
        $o = 0; $t = 0;
        foreach ($rows as $r) { $o += (float)$r['marks_obtained']; $t += (float)$r['total_marks']; }
        $ep = $t > 0 ? ($o / $t) * 100 : 0; ?>
        <h3 style="margin:18px 0 8px;font-size:16px"><?= e($exam) ?>
            <span class="muted">— <?= round($ep, 1) ?>% · Grade <?= e(grade_for($ep)) ?></span></h3>
        <div class="table-container"><table>
            <thead><tr><th>Subject</th><th>Marks</th><th>%</th><th>Grade</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r):
                $pct = $r['total_marks'] > 0 ? ($r['marks_obtained'] / $r['total_marks']) * 100 : 0; ?>
                <tr>
                    <td><?= e($r['subject_name']) ?></td>
                    <td><?= e(floatval($r['marks_obtained'])) ?> / <?= e(floatval($r['total_marks'])) ?></td>
                    <td><?= round($pct, 1) ?>%</td>
                    <td><?= e(grade_for($pct)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endforeach; else: ?>
        <p class="muted">No results recorded.</p>
    <?php endif; ?>
</div>

<div class="content">
    <h2>Fees</h2>
    <div class="table-container"><table>
        <thead><tr><th>Fee</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Due</th><th>Status</th></tr></thead>
        <tbody>
        <?php if ($fees): foreach ($fees as $f): ?>
            <tr>
                <td><?= e($f['fee_type']) ?></td>
                <td><?= money($f['amount']) ?></td>
                <td><?= money($f['paid']) ?></td>
                <td><?= money(max(0, $f['amount'] - $f['paid'])) ?></td>
                <td><?= fmt_date($f['due_date']) ?></td>
                <td><?= status_badge(fee_status($f)) ?></td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="6" class="empty">No fee records.</td></tr>
        <?php endif; ?>
        </tbody>
    </table></div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
