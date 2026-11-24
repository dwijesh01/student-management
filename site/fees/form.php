<?php
require_once __DIR__ . '/../includes/functions.php';
$base = '../';
require_login($base);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$fee = ['student_id' => '', 'fee_type' => '', 'amount' => '', 'paid' => '0', 'due_date' => '', 'paid_date' => '', 'remarks' => ''];

if ($id > 0) {
    $row = db_one("SELECT * FROM fees WHERE id = ?", 'i', [$id]);
    if (!$row) {
        flash('error', 'Fee record not found.');
        redirect('index.php');
    }
    $fee = array_map(function ($v) { return $v ?? ''; }, $row);
    $fee['amount'] = (string)floatval($fee['amount']);
    $fee['paid'] = (string)floatval($fee['paid']);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach (['student_id', 'fee_type', 'amount', 'paid', 'due_date', 'paid_date', 'remarks'] as $k) {
        $fee[$k] = trim((string)($_POST[$k] ?? ''));
    }
    if ($fee['paid'] === '') {
        $fee['paid'] = '0';
    }

    if ((int)$fee['student_id'] <= 0 || $fee['fee_type'] === '') {
        $error = 'Student and fee type are required.';
    } elseif (!is_numeric($fee['amount']) || (float)$fee['amount'] <= 0) {
        $error = 'Amount must be a number greater than zero.';
    } elseif (!is_numeric($fee['paid']) || (float)$fee['paid'] < 0 || (float)$fee['paid'] > (float)$fee['amount']) {
        $error = 'Paid amount must be between 0 and the total amount.';
    } elseif (!valid_date($fee['due_date']) || !valid_date($fee['paid_date'])) {
        $error = 'Please enter valid dates.';
    } else {
        if ((float)$fee['paid'] > 0 && $fee['paid_date'] === '') {
            $fee['paid_date'] = date('Y-m-d');
        }
        if ((float)$fee['paid'] == 0) {
            $fee['paid_date'] = '';
        }
        $params = [(int)$fee['student_id'], $fee['fee_type'], (float)$fee['amount'], (float)$fee['paid'],
                   null_if_empty($fee['due_date']), null_if_empty($fee['paid_date']), $fee['remarks']];
        if ($id > 0) {
            $params[] = $id;
            $r = db_exec("UPDATE fees SET student_id = ?, fee_type = ?, amount = ?, paid = ?, due_date = ?,
                          paid_date = ?, remarks = ? WHERE id = ?", 'isddsssi', $params);
        } else {
            $r = db_exec("INSERT INTO fees (student_id, fee_type, amount, paid, due_date, paid_date, remarks)
                          VALUES (?, ?, ?, ?, ?, ?, ?)", 'isddsss', $params);
        }
        if ($r === true) {
            flash('success', $id > 0 ? 'Fee record updated.' : 'Fee record added.');
            redirect('index.php');
        }
        $error = $r;
    }
}

$student_opts = [];
foreach (db_all("SELECT id, student_id, full_name, class FROM students ORDER BY full_name") as $s) {
    $student_opts[$s['id']] = $s['full_name'] . ' (' . $s['student_id'] . ($s['class'] ? ' · ' . $s['class'] : '') . ')';
}

$page_title = $id > 0 ? 'Edit Fee' : 'Add Fee';
$active = 'fees';
include __DIR__ . '/../includes/header.php';
?>

<div class="content">
    <h2>Fee Information</h2>
    <?php if ($error !== ''): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <?php if (!$student_opts): ?>
        <div class="alert alert-info">Please <a href="../students/add.php">add a student</a> first.</div>
    <?php else: ?>
    <form method="POST" class="form-grid">
        <?= csrf_field() ?>
        <?php if ($id > 0): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

        <?= select_field('Student', 'student_id', $student_opts, $fee['student_id'], true) ?>
        <?= input_field('Fee Type (e.g. Tuition)', 'fee_type', $fee['fee_type'], 'text', true, 'maxlength="100" list="fee-types"') ?>
        <?= input_field('Amount', 'amount', $fee['amount'], 'number', true, 'step="0.01" min="0.01"') ?>
        <?= input_field('Amount Paid', 'paid', $fee['paid'], 'number', false, 'step="0.01" min="0"') ?>
        <?= input_field('Due Date', 'due_date', $fee['due_date'], 'date') ?>
        <?= input_field('Payment Date', 'paid_date', $fee['paid_date'], 'date') ?>
        <div class="form-group full">
            <label for="f_remarks">Remarks</label>
            <input id="f_remarks" type="text" name="remarks" value="<?= e($fee['remarks']) ?>" maxlength="255">
        </div>

        <datalist id="fee-types">
            <?php foreach (['Tuition', 'Admission', 'Exam', 'Transport', 'Library', 'Hostel', 'Other'] as $t): ?><option value="<?= $t ?>"><?php endforeach; ?>
        </datalist>

        <div class="form-actions">
            <button type="submit" class="btn"><?= $id > 0 ? 'Update Fee' : 'Save Fee' ?></button>
            <a href="index.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
