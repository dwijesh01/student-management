<?php
// Kept for old links: the student profile now lives in profile.php
header("Location: profile.php?id=" . (int)($_GET['id'] ?? 0));
exit;
