<?php
require_once __DIR__ . '/../includes/functions.php';
$base = '../';
require_login($base);

$class = trim($_GET['class'] ?? '');
$w = '';
$types = '';
$params = [];
if ($class !== '') {
    $w = "WHERE sub.class = ?";
    $types = 's';
    $params = [$class];
}

$rows = db_all("SELECT sub.*, t.full_name AS teacher_name
                FROM subjects sub LEFT JOIN teachers t ON t.id = sub.teacher_id
                $w ORDER BY sub.class, sub.subject_name", $types, $params);
$classes = array_column(db_all("SELECT DISTINCT class FROM subjects WHERE class IS NOT NULL AND class <> '' ORDER BY class"), 'class');

$page_title = 'Subjects';
$active = 'subjects';
include __DIR__ . '/../includes/header.php';
?>

<div class="content">
    <div class="section-header">
        <h2>Subjects (<?= count($rows) ?>)</h2>
        <a href="form.php" class="btn">+ Add Subject</a>
    </div>

    <form method="GET" class="filters">
        <div class="form-group"><label>Class</label>
            <select name="class">
                <option value="">All classes</option>
                <?php foreach ($classes as $cl): ?>
                    <option value="<?= e($cl) ?>" <?= $cl === $class ? 'selected' : '' ?>><?= e($cl) ?></option>
                <?php endforeach; ?>
            </select></div>
        <button type="submit" class="btn">Filter</button>
        <a href="index.php" class="btn btn-light">Reset</a>
    </form>

    <div class="table-container">
        <table>
            <thead><tr><th>Code</th><th>Subject</th><th>Class</th><th>Teacher</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $r): ?>
                <tr>
                    <td><?= e($r['subject_code']) ?></td>
                    <td><?= e($r['subject_name']) ?></td>
                    <td><?= e($r['class']) ?></td>
                    <td><?= $r['teacher_name'] ? e($r['teacher_name']) : '<span class="muted">Unassigned</span>' ?></td>
                    <td class="row-actions">
                        <a href="form.php?id=<?= (int)$r['id'] ?>">Edit</a>
                        <?= delete_form('delete.php', (int)$r['id'], 'Delete this subject? Results recorded for it will also be deleted.') ?>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="5" class="empty">No subjects found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
