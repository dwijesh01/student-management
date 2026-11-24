<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/* ---------- Output / navigation ---------- */

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

function require_login(string $base = ''): void
{
    if (empty($_SESSION['admin_id'])) {
        redirect($base . 'login.php');
    }
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(419);
        die('Your session expired or the form was invalid. Please go back, refresh the page and try again.');
    }
}

/* ---------- Flash messages ---------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $message];
}

function flash_render(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return '<div class="alert alert-' . e($f['type']) . '">' . e($f['msg']) . '</div>';
}

/* ---------- Database helpers (prepared statements) ---------- */

function db_query(string $sql, string $types = '', array $params = [])
{
    global $conn;

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        die('Database error: ' . e(mysqli_error($conn)));
    }
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        $GLOBALS['db_error'] = mysqli_stmt_error($stmt);
        $GLOBALS['db_errno'] = mysqli_stmt_errno($stmt);
        mysqli_stmt_close($stmt);
        return false;
    }
    return $stmt;
}

function db_all(string $sql, string $types = '', array $params = []): array
{
    $stmt = db_query($sql, $types, $params);
    if (!$stmt) {
        die('Database error: ' . e($GLOBALS['db_error'] ?? 'unknown'));
    }
    $res = mysqli_stmt_get_result($stmt);
    $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);
    return $rows;
}

function db_one(string $sql, string $types = '', array $params = []): ?array
{
    $rows = db_all($sql, $types, $params);
    return $rows[0] ?? null;
}

function db_value(string $sql, string $types = '', array $params = [], $default = 0)
{
    $row = db_one($sql, $types, $params);
    return $row ? reset($row) : $default;
}

/** Returns true on success, or a human-readable error string. */
function db_exec(string $sql, string $types = '', array $params = [])
{
    $stmt = db_query($sql, $types, $params);
    if (!$stmt) {
        $errno = (int)($GLOBALS['db_errno'] ?? 0);
        if ($errno === 1062) {
            return 'That ID / code (or combination) already exists. Please use a different value.';
        }
        if ($errno === 1452) {
            return 'A related record (student, teacher or subject) does not exist.';
        }
        return 'Database error: ' . ($GLOBALS['db_error'] ?? 'unknown');
    }
    mysqli_stmt_close($stmt);
    return true;
}

/** Shared POST-only delete handler used by every module's delete.php */
function handle_delete(string $table, string $label): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('index.php');
    }
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $r = db_exec("DELETE FROM `$table` WHERE id = ?", 'i', [$id]);
        if ($r === true) {
            flash('success', "$label deleted.");
        } else {
            flash('error', $r);
        }
    }
    redirect('index.php');
}

/* ---------- Validation / formatting ---------- */

function null_if_empty($v)
{
    $v = trim((string)$v);
    return $v === '' ? null : $v;
}

function valid_date(string $d): bool
{
    if ($d === '') {
        return true;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

function fmt_date($d): string
{
    if (!$d || $d === '0000-00-00') {
        return '—';
    }
    $t = strtotime((string)$d);
    return $t ? date('d M Y', $t) : e($d);
}

function money($n): string
{
    return number_format((float)$n, 2);
}

function grade_for(float $pct): string
{
    if ($pct >= 90) return 'A+';
    if ($pct >= 80) return 'A';
    if ($pct >= 70) return 'B+';
    if ($pct >= 60) return 'B';
    if ($pct >= 50) return 'C';
    if ($pct >= 40) return 'D';
    return 'F';
}

function fee_status(array $f): string
{
    $amount = (float)$f['amount'];
    $paid = (float)$f['paid'];
    if ($paid >= $amount) return 'Paid';
    if (!empty($f['due_date']) && $f['due_date'] < date('Y-m-d')) return 'Overdue';
    if ($paid > 0) return 'Partial';
    return 'Unpaid';
}

function badge(string $text, string $type = 'gray'): string
{
    return '<span class="badge badge-' . e($type) . '">' . e($text) . '</span>';
}

function status_badge(string $status): string
{
    $map = [
        'Paid' => 'green', 'Partial' => 'blue', 'Unpaid' => 'gray', 'Overdue' => 'red',
        'Present' => 'green', 'Absent' => 'red', 'Late' => 'amber', 'Leave' => 'blue',
    ];
    return badge($status, $map[$status] ?? 'gray');
}

/* ---------- Pagination ---------- */

function paginate(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    return ['page' => $page, 'pages' => $pages, 'per' => $perPage, 'offset' => ($page - 1) * $perPage, 'total' => $total];
}

function pagination_html(array $p, array $params = []): string
{
    if ($p['pages'] <= 1) {
        return '';
    }
    $html = '<div class="pagination">';
    for ($i = 1; $i <= $p['pages']; $i++) {
        $q = http_build_query(array_merge($params, ['page' => $i]));
        $html .= '<a href="?' . e($q) . '" class="' . ($i === $p['page'] ? 'active' : '') . '">' . $i . '</a>';
    }
    return $html . '</div>';
}

/* ---------- Form helpers ---------- */

function input_field(string $label, string $name, $value = '', string $type = 'text', bool $required = false, string $attrs = ''): string
{
    return '<div class="form-group"><label for="f_' . e($name) . '">' . e($label)
        . ($required ? ' <span class="req">*</span>' : '') . '</label>'
        . '<input id="f_' . e($name) . '" type="' . e($type) . '" name="' . e($name) . '" value="' . e($value) . '"'
        . ($required ? ' required' : '') . ' ' . $attrs . '></div>';
}

function select_field(string $label, string $name, array $options, $value = '', bool $required = false, string $placeholder = '-- Select --'): string
{
    $html = '<div class="form-group"><label for="f_' . e($name) . '">' . e($label)
        . ($required ? ' <span class="req">*</span>' : '') . '</label>'
        . '<select id="f_' . e($name) . '" name="' . e($name) . '"' . ($required ? ' required' : '') . '>';
    $html .= '<option value="">' . e($placeholder) . '</option>';
    foreach ($options as $k => $text) {
        $sel = ((string)$k === (string)$value) ? ' selected' : '';
        $html .= '<option value="' . e($k) . '"' . $sel . '>' . e($text) . '</option>';
    }
    return $html . '</select></div>';
}

function delete_form(string $action, int $id, string $confirm = 'Are you sure you want to delete this record?'): string
{
    return '<form method="POST" action="' . e($action) . '" class="inline-form" data-confirm="' . e($confirm) . '">'
        . csrf_field() . '<input type="hidden" name="id" value="' . $id . '">'
        . '<button type="submit" class="link-btn danger">Delete</button></form>';
}

function class_list(): array
{
    $rows = db_all("SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class <> '' ORDER BY class");
    return array_column($rows, 'class');
}


/* ---------- Student profile helpers ---------- */

define('STUDENT_PHOTO_DIR', __DIR__ . '/../uploads/students/');

/**
 * One-time, automatic upgrade for databases created before student profiles existed.
 * Adds the profile columns to `students` if they are missing.
 */
function ensure_schema(): void
{
    global $conn;
    if (!empty($_SESSION['schema_v']) && $_SESSION['schema_v'] >= 2) {
        return;
    }
    $r = @mysqli_query($conn, "SHOW COLUMNS FROM students LIKE 'photo'");
    if ($r === false) {
        return; // students table not installed yet
    }
    if (mysqli_num_rows($r) === 0) {
        mysqli_query($conn, "ALTER TABLE students
            ADD COLUMN blood_group VARCHAR(5) NULL,
            ADD COLUMN guardian_name VARCHAR(120) NULL,
            ADD COLUMN guardian_relation VARCHAR(40) NULL,
            ADD COLUMN guardian_phone VARCHAR(30) NULL,
            ADD COLUMN photo VARCHAR(60) NULL");
    }
    $_SESSION['schema_v'] = 2;
}
ensure_schema();

/** Saves an uploaded student photo. Returns the stored file name, or null (and sets $error if it failed). */
function upload_student_photo(string $field, string &$error)
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $error = 'Photo upload failed (error code ' . (int)$f['error'] . '). The file may be too large for the server.';
        return null;
    }
    if ($f['size'] > 2 * 1024 * 1024) {
        $error = 'Photo must be 2 MB or smaller.';
        return null;
    }
    $info = @getimagesize($f['tmp_name']);
    $map = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($map[$info[2]])) {
        $error = 'Photo must be a JPG, PNG or WebP image.';
        return null;
    }
    if (!is_dir(STUDENT_PHOTO_DIR)) {
        @mkdir(STUDENT_PHOTO_DIR, 0755, true);
    }
    $name = bin2hex(random_bytes(12)) . '.' . $map[$info[2]];
    if (!move_uploaded_file($f['tmp_name'], STUDENT_PHOTO_DIR . $name)) {
        $error = 'Could not save the photo. Check that the uploads/students folder is writable.';
        return null;
    }
    return $name;
}

function delete_student_photo($name): void
{
    if ($name && preg_match('/^[a-f0-9]{24}\.(jpg|png|webp)$/', (string)$name)) {
        @unlink(STUDENT_PHOTO_DIR . $name);
    }
}

/** Round photo, or coloured circle with initials when there is no photo. $size: sm | lg */
function avatar_html(array $s, string $base, string $size = 'sm'): string
{
    if (!empty($s['photo'])) {
        return '<img class="avatar avatar-' . e($size) . '" src="' . e($base . 'uploads/students/' . $s['photo']) . '" alt="">';
    }
    $parts = preg_split('/\s+/', trim((string)($s['full_name'] ?? '?')));
    $ini = mb_strtoupper(mb_substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $ini .= mb_strtoupper(mb_substr(end($parts), 0, 1));
    }
    return '<span class="avatar avatar-' . e($size) . '">' . e($ini) . '</span>';
}
