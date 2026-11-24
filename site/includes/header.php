<?php
// Expects: $base (relative path to project root), $page_title, $active
$base = $base ?? '';
$page_title = $page_title ?? 'Dashboard';
$active = $active ?? '';

$nav = [
    'dashboard'  => ['Dashboard',  'dashboard.php'],
    'students'   => ['Students',   'students/index.php'],
    'teachers'   => ['Teachers',   'teachers/index.php'],
    'subjects'   => ['Subjects',   'subjects/index.php'],
    'attendance' => ['Attendance', 'attendance/index.php'],
    'results'    => ['Results',    'results/index.php'],
    'fees'       => ['Fees',       'fees/index.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/app.css">
</head>
<body>

<div class="sidebar" id="sidebar">
    <h2>Student System</h2>
    <?php foreach ($nav as $key => $item): ?>
        <a href="<?= $base . $item[1] ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= e($item[0]) ?></a>
    <?php endforeach; ?>
    <a href="<?= $base ?>profile.php" class="<?= $active === 'profile' ? 'active' : '' ?>">Change Password</a>
    <a href="<?= $base ?>logout.php" data-confirm-link="Are you sure you want to logout?">Logout</a>
</div>

<div class="main">
    <header>
        <button id="menuBtn" type="button" aria-label="Toggle menu">☰</button>
        <h1><?= e($page_title) ?></h1>
        <div class="admin"><?= e($_SESSION['username'] ?? 'Admin') ?></div>
    </header>

    <?= flash_render() ?>
