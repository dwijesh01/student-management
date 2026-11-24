<?php
/**
 * One-time installer: creates the database, all tables and the first admin account.
 * It locks itself as soon as an admin exists. Delete this file after installing.
 */
require_once __DIR__ . '/config/config.php';

$error = "";
$done = false;
$locked = false;

$c = @mysqli_connect(DB_HOST, DB_USER, DB_PASS);
if (!$c) {
    die("Cannot connect to MySQL: " . htmlspecialchars(mysqli_connect_error())
        . ". Check the credentials in config/config.php and make sure MySQL is running.");
}

// Already installed?
if (@mysqli_select_db($c, DB_NAME)) {
    $r = @mysqli_query($c, "SELECT COUNT(*) AS n FROM admins");
    if ($r) {
        $row = mysqli_fetch_assoc($r);
        if ((int)$row['n'] > 0) {
            $locked = true;
        }
    }
}

if (!$locked && $_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (strlen($username) < 3) {
        $error = "Username must be at least 3 characters.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {

        $sql = file_get_contents(__DIR__ . '/database/schema.sql');

        if (mysqli_multi_query($c, $sql)) {
            while (true) {
                if ($res = mysqli_store_result($c)) {
                    mysqli_free_result($res);
                }
                if (!mysqli_more_results($c)) {
                    break;
                }
                if (!mysqli_next_result($c)) {
                    break;
                }
            }
        }

        if (mysqli_errno($c)) {
            $error = "Schema error: " . mysqli_error($c)
                   . " (If you already have tables from an older version, try a fresh database name in config/config.php and database/schema.sql.)";
        } else {
            mysqli_select_db($c, DB_NAME);
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($c, "INSERT INTO admins (username, password) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ss", $username, $hash);

            if (mysqli_stmt_execute($stmt)) {
                $done = true;
            } else {
                $error = "Could not create admin: " . mysqli_stmt_error($stmt)
                       . " (That username may already exist.)";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Student Management System</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
<div class="login-container">
    <div class="login-box">

        <h1>Student Management System</h1>
        <h2>Installation</h2>

        <?php if ($locked): ?>

            <div class="error">Already installed. For security, delete <b>setup.php</b> from the server.</div>
            <p style="text-align:center"><a href="login.php">Go to login</a></p>

        <?php elseif ($done): ?>

            <p style="color:#166534;margin-bottom:15px">Installation complete! Your admin account was created.</p>
            <p style="margin-bottom:15px"><b>Important:</b> delete <b>setup.php</b> now.</p>
            <p style="text-align:center"><a href="login.php">Go to login</a></p>

        <?php else: ?>

            <?php if ($error !== ""): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <label>Admin username</label>
                <input type="text" name="username" required minlength="3"
                       value="<?= htmlspecialchars($_POST['username'] ?? 'admin') ?>">

                <label>Admin password (min 8 characters)</label>
                <input type="password" name="password" required minlength="8">

                <label>Confirm password</label>
                <input type="password" name="confirm" required minlength="8">

                <button type="submit">Install</button>
            </form>

        <?php endif; ?>

    </div>
</div>
</body>
</html>
