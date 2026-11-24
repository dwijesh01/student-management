<?php
require_once __DIR__ . '/../includes/functions.php';
$base = '../';
require_login($base);

$q = trim($_GET['q'] ?? '');
$w = '';
$types = '';
$params = [];
if ($q !== '') {
    $w = "WHERE full_name LIKE ? OR teacher_id LIKE ? OR subject LIKE ?";
    $like = "%$q%";
    $types = 'sss';
    $params = [$like, $like, $like];
}

$total = (int)db_value("SELECT COUNT(*) FROM teachers $w", $types, $params);
$p = paginate($total, 15, (int)($_GET['page'] ?? 1));
$rows = db_all("SELECT * FROM teachers $w ORDER BY id DESC LIMIT {$p['per']} OFFSET {$p['offset']}", $types, $params);

$page_title = 'Teachers';
$active = 'teachers';
include __DIR__ . '/../includes/header.php';
?>

<div class="content">
    <div class="section-header">
        <h2>Teachers List (<?= $total ?>)</h2>
        <a href="add.php" class="btn">+ Add Teacher</a>
    </div>

    <form method="GET" class="filters">
        <div class="form-group"><label>Search</label>
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Name, ID or subject"></div>
        <button type="submit" class="btn">Search</button>
        <a href="index.php" class="btn btn-light">Reset</a>
    </form>

    <div class="table-container">
        <table>
            <thead><tr><th>ID</th><th>Teacher ID</th><th>Name</th><th>Subject</th><th>Phone</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $r): ?>
                <tr>
                    <td><?= (int)$r['id'] ?></td>
                    <td><?= e($r['teacher_id']) ?></td>
                    <td><?= e($r['full_name']) ?></td>
                    <td><?= e($r['subject']) ?></td>
                    <td><?= e($r['phone']) ?></td>
                    <td class="row-actions">
                        <a href="view.php?id=<?= (int)$r['id'] ?>">View</a>
                        <a href="edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
                        <?= delete_form('delete.php', (int)$r['id'], 'Are you sure you want to delete this teacher?') ?>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="6" class="empty">No teachers found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?= pagination_html($p, ['q' => $q]) ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
