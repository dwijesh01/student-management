<?php
require_once __DIR__ . '/../includes/functions.php';
$base = '../';
require_login($base);

$id = (int)($_GET['id'] ?? 0);
$teacher = $id > 0 ? db_one("SELECT * FROM teachers WHERE id = ?", 'i', [$id]) : null;

if (!$teacher) {
    flash('error', 'Teacher not found.');
    redirect('index.php');
}

$subjects = db_all("SELECT * FROM subjects WHERE teacher_id = ? ORDER BY subject_name", 'i', [$id]);

$labels = [
    'teacher_id' => 'Teacher ID', 'full_name' => 'Full Name', 'phone' => 'Phone', 'email' => 'Email',
    'address' => 'Address', 'subject' => 'Main Subject', 'created_at' => 'Joined Date',
];

$page_title = 'Teacher Details';
$active = 'teachers';
include __DIR__ . '/../includes/header.php';
?>

<div class="content">
    <div class="section-header">
        <h2><?= e($teacher['full_name']) ?></h2>
        <div class="actions-row">
            <a href="edit.php?id=<?= $id ?>" class="btn">Edit</a>
            <a href="index.php" class="btn btn-light">Back to Teachers</a>
        </div>
    </div>
    <table class="detail-table">
        <?php foreach ($labels as $key => $label):
            $val = $teacher[$key] ?? '';
            if ($key === 'created_at') { $val = fmt_date($val); }
        ?>
            <tr><th><?= e($label) ?></th><td><?= e($val) ?></td></tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="content">
    <h2>Assigned Subjects</h2>
    <div class="table-container"><table>
        <thead><tr><th>Code</th><th>Subject</th><th>Class</th></tr></thead>
        <tbody>
        <?php if ($subjects): foreach ($subjects as $s): ?>
            <tr><td><?= e($s['subject_code']) ?></td><td><?= e($s['subject_name']) ?></td><td><?= e($s['class']) ?></td></tr>
        <?php endforeach; else: ?>
            <tr><td colspan="3" class="empty">No subjects assigned yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table></div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
