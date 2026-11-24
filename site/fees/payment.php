<?php
require_once __DIR__ . '/../includes/functions.php';
$base = '../';
require_login($base);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$fee = $id > 0 ? db_one("SELECT f.*, s.full_name, s.student_id AS student_code
                         FROM fees f JOIN students s ON s.id = f.student_id WHERE f.id = ?", 'i', [$id]) : null;

if (!$fee) {
    flash('error', 'Fee record not found.');
    redirect('index.php');
}

$balance = max(0, (float)$fee['amount'] - (float)$fee['paid']);
$error = '';
$pay_amount = number_format($balance, 2, '.', '');
$pay_date = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pay_amount = trim($_POST['pay_amount'] ?? '');
    $pay_date = trim($_POST['pay_date'] ?? '');

    if (!is_numeric($pay_amount) || (float)$pay_amount <= 0) {
        $error = 'Enter a payment amount greater than zero.';
    } elseif ((float)$pay_amount > $balance + 0.00001) {
        $error = 'Payment cannot exceed the outstanding balance of ' . money($balance) . '.';
    } elseif ($pay_date === '' || !valid_date($pay_date)) {
        $error = 'Please enter a valid payment date.';
    } else {
        $new_paid = min((float)$fee['amount'], (float)$fee['paid'] + (float)$pay_amount);
        $r = db_exec("UPDATE fees SET paid = ?, paid_date = ? WHERE id = ?", 'dsi', [$new_paid, $pay_date, $id]);
        if ($r === true) {
            flash('success', 'Payment of ' . money($pay_amount) . ' recorded.');
            redirect('receipt.php?id=' . $id);
        }
        $error = $r;
    }
}

$page_title = 'Record Payment';
$active = 'fees';
include __DIR__ . '/../includes/header.php';
?>

<div class="content" style="max-width:560px">
    <h2>Record Payment</h2>
    <table class="detail-table" style="margin-bottom:20px">
        <tr><th>Student</th><td><?= e($fee['full_name']) ?> (<?= e($fee['student_code']) ?>)</td></tr>
        <tr><th>Fee</th><td><?= e($fee['fee_type']) ?></td></tr>
        <tr><th>Amount</th><td><?= money($fee['amount']) ?></td></tr>
        <tr><th>Already paid</th><td><?= money($fee['paid']) ?></td></tr>
        <tr><th>Balance</th><td><b><?= money($balance) ?></b></td></tr>
    </table>

    <?php if ($error !== ''): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <?php if ($balance > 0): ?>
    <form method="POST" class="form-grid" style="grid-template-columns:1fr">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <?= input_field('Payment amount', 'pay_amount', $pay_amount, 'number', true, 'step="0.01" min="0.01"') ?>
        <?= input_field('Payment date', 'pay_date', $pay_date, 'date', true) ?>
        <div class="form-actions">
            <button type="submit" class="btn btn-green">Record Payment</button>
            <a href="index.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
    <?php else: ?>
        <div class="alert alert-success">This fee is fully paid.</div>
        <a href="index.php" class="btn btn-light">Back</a>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
