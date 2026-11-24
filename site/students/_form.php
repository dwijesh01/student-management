<?php
// Shared helpers for add.php and edit.php

function student_defaults(): array
{
    return [
        'student_id' => '', 'full_name' => '', 'gender' => '', 'date_of_birth' => '',
        'blood_group' => '', 'phone' => '', 'email' => '', 'address' => '',
        'class' => '', 'section' => '', 'admission_date' => date('Y-m-d'),
        'guardian_name' => '', 'guardian_relation' => '', 'guardian_phone' => '',
    ];
}

function student_from_post(): array
{
    $s = [];
    foreach (array_keys(student_defaults()) as $k) {
        $s[$k] = trim((string)($_POST[$k] ?? ''));
    }
    return $s;
}

function student_blood_groups(): array
{
    return ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
}

function student_validate(array $s): string
{
    if ($s['student_id'] === '' || $s['full_name'] === '' || $s['gender'] === '') {
        return 'Student ID, Full Name and Gender are required.';
    }
    if (!in_array($s['gender'], ['Male', 'Female', 'Other'], true)) {
        return 'Please choose a valid gender.';
    }
    if ($s['blood_group'] !== '' && !in_array($s['blood_group'], student_blood_groups(), true)) {
        return 'Please choose a valid blood group.';
    }
    if ($s['email'] !== '' && !filter_var($s['email'], FILTER_VALIDATE_EMAIL)) {
        return 'Please enter a valid email address.';
    }
    if (!valid_date($s['date_of_birth']) || !valid_date($s['admission_date'])) {
        return 'Please enter valid dates.';
    }
    if ($s['date_of_birth'] !== '' && $s['date_of_birth'] > date('Y-m-d')) {
        return 'Date of birth cannot be in the future.';
    }
    return '';
}

function student_form_render(array $s, string $button, int $id = 0): void
{
    $photo = $s['photo'] ?? '';
    $bg = array_combine(student_blood_groups(), student_blood_groups());
    ?>
    <form method="POST" class="form-grid" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php if ($id > 0): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

        <h3 class="form-section">Personal Information</h3>
        <?= input_field('Student ID', 'student_id', $s['student_id'], 'text', true, 'maxlength="30"') ?>
        <?= input_field('Full Name', 'full_name', $s['full_name'], 'text', true, 'maxlength="120"') ?>
        <?= select_field('Gender', 'gender', ['Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other'], $s['gender'], true) ?>
        <?= input_field('Date of Birth', 'date_of_birth', $s['date_of_birth'], 'date') ?>
        <?= select_field('Blood Group', 'blood_group', $bg, $s['blood_group']) ?>
        <?= input_field('Phone', 'phone', $s['phone'], 'text', false, 'maxlength="30"') ?>
        <?= input_field('Email', 'email', $s['email'], 'email', false, 'maxlength="120"') ?>
        <div class="form-group full">
            <label for="f_address">Address</label>
            <input id="f_address" type="text" name="address" value="<?= e($s['address']) ?>" maxlength="255">
        </div>

        <h3 class="form-section">Academic</h3>
        <?= input_field('Class', 'class', $s['class'], 'text', false, 'maxlength="50" list="class-options"') ?>
        <?= input_field('Section', 'section', $s['section'], 'text', false, 'maxlength="20"') ?>
        <?= input_field('Admission Date', 'admission_date', $s['admission_date'], 'date') ?>

        <h3 class="form-section">Guardian / Emergency Contact</h3>
        <?= input_field('Guardian Name', 'guardian_name', $s['guardian_name'], 'text', false, 'maxlength="120"') ?>
        <?= select_field('Relation', 'guardian_relation',
            ['Father' => 'Father', 'Mother' => 'Mother', 'Brother' => 'Brother', 'Sister' => 'Sister',
             'Uncle' => 'Uncle', 'Aunt' => 'Aunt', 'Guardian' => 'Guardian', 'Other' => 'Other'], $s['guardian_relation']) ?>
        <?= input_field('Guardian Phone', 'guardian_phone', $s['guardian_phone'], 'text', false, 'maxlength="30"') ?>

        <h3 class="form-section">Profile Photo</h3>
        <div class="form-group full">
            <?php if ($photo !== ''): ?>
                <div style="display:flex;align-items:center;gap:16px;margin-bottom:8px">
                    <?= avatar_html(['photo' => $photo, 'full_name' => $s['full_name']], '../', 'lg') ?>
                    <label style="font-weight:normal"><input type="checkbox" name="remove_photo" value="1"> Remove current photo</label>
                </div>
            <?php endif; ?>
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
            <span class="muted">JPG, PNG or WebP, up to 2 MB. Leave empty to keep <?= $photo !== '' ? 'the current photo' : 'initials as the avatar' ?>.</span>
        </div>

        <datalist id="class-options">
            <?php foreach (class_list() as $cl): ?><option value="<?= e($cl) ?>"><?php endforeach; ?>
        </datalist>

        <div class="form-actions">
            <button type="submit" class="btn"><?= e($button) ?></button>
            <a href="index.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
    <?php
}
