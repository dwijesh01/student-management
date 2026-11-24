# Student Management System (PHP + MySQL)

Modules: Dashboard, Students (with full profile page: photo, guardian info, attendance, results, fees), Teachers, Subjects, Attendance (daily marking + monthly report),
Results (marks, grades, printable report cards), Fees (billing, payments, printable receipts),
Admin login and change-password.

## Requirements
PHP 7.4+ (8.x recommended) with mysqli + mbstring, MySQL 5.7+/MariaDB 10.3+ (InnoDB). XAMPP/WAMP/Laragon all work.

## Install
1. Copy this folder to your web root (e.g. `htdocs/project`).
2. Edit `config/config.php` if your MySQL user/password differ from `root` / empty.
3. Open `http://localhost/project/setup.php`, choose an admin username + password, click **Install**.
   This creates the database, all tables and your admin account.
4. **Delete `setup.php`**, then log in at `http://localhost/project/login.php`.

(You can also import `database/schema.sql` in phpMyAdmin, but you would still need setup.php to create the admin.)

## Already have an old database?
`schema.sql` only creates tables that don't exist, and keeps your existing `students`/`teachers` data.
New tables use foreign keys to `students.id` / `teachers.id`, which must be `INT` (signed) InnoDB.
If setup reports a foreign-key error, install into a fresh database name (change `DB_NAME` in
`config/config.php` and the two `student_management` lines at the top of `database/schema.sql`).

## Structure
- `includes/functions.php` — DB helpers (prepared statements), CSRF, flash messages, validation, form helpers
- `includes/header.php` / `footer.php` — shared layout + sidebar
- `assets/css/app.css`, `assets/js/script.js` — styles and small UI scripts
- `students/ teachers/ subjects/ attendance/ results/ fees/` — one folder per module

## Security notes
All pages except login/setup require login; every write (including delete) is POST + CSRF-protected;
all SQL uses prepared statements; output is escaped; passwords use `password_hash`.

## Student profile
Open any student from the Students list (click the name or **Profile**). The profile page shows photo/initials,
class, age, blood group, guardian contact, attendance %, overall result, fee balance, and full attendance / results / fees
history, and can be printed. Photos are uploaded on the Add/Edit student form into `uploads/students/`
(that folder must be writable by the web server). Databases from earlier versions are upgraded automatically on first load
(or run `database/upgrade_student_profile.sql` manually).
