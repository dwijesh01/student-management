<?php
require_once __DIR__ . '/includes/functions.php';
$base = '';
require_login($base);

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_check();

    $current = $_POST['current'] ?? '';
    $new     = $_POST['new'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    $admin = db_one("SELECT id, password FROM admins WHERE id = ?", "i", [(int)$_SESSION['admin_id']]);

    if (!$admin || !password_verify($current, $admin['password'])) {
        $error = "Your current password is incorrect.";
    } elseif (strlen($new) < 8) {
        $error = "New password must be at least 8 characters.";
    } elseif ($new !== $confirm) {
        $error = "New password and confirmation do not match.";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $r = db_exec("UPDATE admins SET password = ? WHERE id = ?", "si", [$hash, (int)$admin['id']]);
        if ($r === true) {
            flash('success', 'Password changed successfully.');
            redirect('profile.php');
        }
        $error = $r;
    }
}

$page_title = 'Change Password';
$active = 'profile';
include __DIR__ . '/includes/header.php';
?>

<div class="content" style="max-width:520px">
    <h2>Account: <?= e($_SESSION['username']) ?></h2>

    <?php if ($error !== ""): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="POST" class="form-grid" style="grid-template-columns:1fr">
        <?= csrf_field() ?>
        <?= input_field('Current password', 'current', '', 'password', true) ?>
        <?= input_field('New password (min 8 characters)', 'new', '', 'password', true) ?>
        <?= input_field('Confirm new password', 'confirm', '', 'password', true) ?>
        <div class="form-actions"><button type="submit" class="btn">Update Password</button></div>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
