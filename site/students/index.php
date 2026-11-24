<?php
require_once __DIR__ . '/../includes/functions.php';
$base = '../';
require_login($base);

$q = trim($_GET['q'] ?? '');
$class = trim($_GET['class'] ?? '');

$where = [];
$types = '';
$params = [];

if ($q !== '') {
    $where[] = "(full_name LIKE ? OR student_id LIKE ? OR phone LIKE ?)";
    $like = "%$q%";
    $types .= 'sss';
    array_push($params, $like, $like, $like);
}
if ($class !== '') {
    $where[] = "class = ?";
    $types .= 's';
    $params[] = $class;
}
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int)db_value("SELECT COUNT(*) FROM students $w", $types, $params);
$p = paginate($total, 15, (int)($_GET['page'] ?? 1));
$rows = db_all("SELECT * FROM students $w ORDER BY id DESC LIMIT {$p['per']} OFFSET {$p['offset']}", $types, $params);
$classes = class_list();

$page_title = 'Students';
$active = 'students';
include __DIR__ . '/../includes/header.php';
?>

<div class="content">
    <div class="section-header">
        <h2>Student List (<?= $total ?>)</h2>
        <a href="add.php" class="btn">+ Add Student</a>
    </div>

    <form method="GET" class="filters">
        <div class="form-group"><label>Search</label>
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Name, ID or phone"></div>
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
            <thead>
                <tr><th>ID</th><th>Student ID</th><th>Name</th><th>Gender</th><th>Class</th><th>Section</th><th>Phone</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $r): ?>
                <tr>
                    <td><?= (int)$r['id'] ?></td>
                    <td><?= e($r['student_id']) ?></td>
                    <td><a href="profile.php?id=<?= (int)$r['id'] ?>" style="color:inherit;font-weight:bold"><?= avatar_html($r, $base) ?><?= e($r['full_name']) ?></a></td>
                    <td><?= e($r['gender']) ?></td>
                    <td><?= e($r['class']) ?></td>
                    <td><?= e($r['section']) ?></td>
                    <td><?= e($r['phone']) ?></td>
                    <td class="row-actions">
                        <a href="profile.php?id=<?= (int)$r['id'] ?>">Profile</a>
                        <a href="edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
                        <?= delete_form('delete.php', (int)$r['id'], 'Delete this student? Their attendance, results and fees will also be deleted.') ?>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="8" class="empty">No students found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?= pagination_html($p, ['q' => $q, 'class' => $class]) ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
