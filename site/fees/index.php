<?php
require_once __DIR__ . '/../includes/functions.php';
$base = '../';
require_login($base);

$status = trim($_GET['status'] ?? '');
$q = trim($_GET['q'] ?? '');

$status_sql = [
    'paid'    => 'f.paid >= f.amount',
    'partial' => '(f.paid > 0 AND f.paid < f.amount)',
    'unpaid'  => 'f.paid = 0',
    'overdue' => '(f.paid < f.amount AND f.due_date IS NOT NULL AND f.due_date < CURDATE())',
];

$where = [];
$types = '';
$params = [];
if ($q !== '') {
    $where[] = "(s.full_name LIKE ? OR s.student_id LIKE ? OR f.fee_type LIKE ?)";
    $like = "%$q%";
    $types .= 'sss';
    array_push($params, $like, $like, $like);
}
if (isset($status_sql[$status])) {
    $where[] = $status_sql[$status];
}
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sum = db_one("SELECT COALESCE(SUM(amount), 0) AS billed, COALESCE(SUM(LEAST(paid, amount)), 0) AS collected FROM fees");
$outstanding = (float)$sum['billed'] - (float)$sum['collected'];
$overdue_count = (int)db_value("SELECT COUNT(*) FROM fees f WHERE " . $status_sql['overdue']);

$from = "FROM fees f JOIN students s ON s.id = f.student_id";
$total = (int)db_value("SELECT COUNT(*) $from $w", $types, $params);
$p = paginate($total, 15, (int)($_GET['page'] ?? 1));
$rows = db_all("SELECT f.*, s.full_name, s.student_id AS student_code $from $w
                ORDER BY f.id DESC LIMIT {$p['per']} OFFSET {$p['offset']}", $types, $params);

$page_title = 'Fees';
$active = 'fees';
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard">
    <div class="card"><h3>Total Billed</h3><h1><?= money($sum['billed']) ?></h1></div>
    <div class="card green"><h3>Collected</h3><h1><?= money($sum['collected']) ?></h1></div>
    <div class="card amber"><h3>Outstanding</h3><h1><?= money($outstanding) ?></h1></div>
    <div class="card red"><h3>Overdue Records</h3><h1><?= $overdue_count ?></h1></div>
</div>

<div class="content">
    <div class="section-header">
        <h2>Fee Records (<?= $total ?>)</h2>
        <a href="form.php" class="btn">+ Add Fee</a>
    </div>

    <form method="GET" class="filters">
        <div class="form-group"><label>Search</label>
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Student or fee type"></div>
        <div class="form-group"><label>Status</label>
            <select name="status">
                <option value="">All</option>
                <?php foreach (['paid' => 'Paid', 'partial' => 'Partial', 'unpaid' => 'Unpaid', 'overdue' => 'Overdue'] as $k => $label): ?>
                    <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select></div>
        <button type="submit" class="btn">Filter</button>
        <a href="index.php" class="btn btn-light">Reset</a>
    </form>

    <div class="table-container">
        <table>
            <thead><tr><th>Student</th><th>Fee</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Due</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $r):
                $balance = max(0, (float)$r['amount'] - (float)$r['paid']); ?>
                <tr>
                    <td><?= e($r['full_name']) ?><div class="muted"><?= e($r['student_code']) ?></div></td>
                    <td><?= e($r['fee_type']) ?></td>
                    <td><?= money($r['amount']) ?></td>
                    <td><?= money($r['paid']) ?></td>
                    <td><?= money($balance) ?></td>
                    <td><?= fmt_date($r['due_date']) ?></td>
                    <td><?= status_badge(fee_status($r)) ?></td>
                    <td class="row-actions">
                        <?php if ($balance > 0): ?><a href="payment.php?id=<?= (int)$r['id'] ?>">Pay</a><?php endif; ?>
                        <?php if ((float)$r['paid'] > 0): ?><a href="receipt.php?id=<?= (int)$r['id'] ?>">Receipt</a><?php endif; ?>
                        <a href="form.php?id=<?= (int)$r['id'] ?>">Edit</a>
                        <?= delete_form('delete.php', (int)$r['id'], 'Delete this fee record?') ?>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="8" class="empty">No fee records found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?= pagination_html($p, ['q' => $q, 'status' => $status]) ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
