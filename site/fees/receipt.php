<?php
require_once __DIR__ . '/../includes/functions.php';
$base = '../';
require_login($base);

$id = (int)($_GET['id'] ?? 0);
$fee = $id > 0 ? db_one("SELECT f.*, s.full_name, s.student_id AS student_code, s.class, s.section
                         FROM fees f JOIN students s ON s.id = f.student_id WHERE f.id = ?", 'i', [$id]) : null;

if (!$fee) {
    flash('error', 'Fee record not found.');
    redirect('index.php');
}

$balance = max(0, (float)$fee['amount'] - (float)$fee['paid']);

$page_title = 'Fee Receipt';
$active = 'fees';
include __DIR__ . '/../includes/header.php';
?>

<div class="content">
    <div class="report">
        <div class="report-head">
            <h2><?= e(APP_NAME) ?></h2>
            <div class="muted">Fee Receipt #<?= str_pad((string)$id, 5, '0', STR_PAD_LEFT) ?></div>
        </div>

        <div class="report-meta">
            <div><b>Student:</b> <?= e($fee['full_name']) ?></div>
            <div><b>Student ID:</b> <?= e($fee['student_code']) ?></div>
            <div><b>Class:</b> <?= e($fee['class']) ?> <?= e($fee['section']) ?></div>
            <div><b>Payment date:</b> <?= fmt_date($fee['paid_date']) ?></div>
        </div>

        <table class="detail-table" style="max-width:none">
            <tr><th>Fee type</th><td><?= e($fee['fee_type']) ?></td></tr>
            <tr><th>Total amount</th><td><?= money($fee['amount']) ?></td></tr>
            <tr><th>Amount paid</th><td><b><?= money($fee['paid']) ?></b></td></tr>
            <tr><th>Balance due</th><td><?= money($balance) ?></td></tr>
            <tr><th>Status</th><td><?= status_badge(fee_status($fee)) ?></td></tr>
            <?php if (!empty($fee['remarks'])): ?><tr><th>Remarks</th><td><?= e($fee['remarks']) ?></td></tr><?php endif; ?>
        </table>

        <div class="no-print actions-row" style="margin-top:20px">
            <button type="button" class="btn" data-print>Print Receipt</button>
            <a href="index.php" class="btn btn-light">Back to Fees</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
