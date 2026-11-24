<?php
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('dashboard.php');
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    $admin = db_one("SELECT id, username, password FROM admins WHERE username = ?", "s", [$username]);

    if ($admin && password_verify($password, $admin["password"])) {

        session_regenerate_id(true);
        $_SESSION["admin_id"] = (int)$admin["id"];
        $_SESSION["username"] = $admin["username"];

        redirect("dashboard.php");

    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Management System</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>

<div class="login-container">
    <div class="login-box">

        <h1>Student Management System</h1>
        <h2>Admin Login</h2>

        <?php if ($error !== ""): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrf_field() ?>

            <label>Username</label>
            <input type="text" name="username" placeholder="Enter username" required autofocus
                   value="<?= e($_POST['username'] ?? '') ?>">

            <label>Password</label>
            <input type="password" name="password" placeholder="Enter password" required>

            <button type="submit">Login</button>
        </form>

    </div>
</div>

</body>
</html>
