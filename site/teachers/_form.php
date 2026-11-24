<?php
function teacher_defaults(): array
{
    return [
        'teacher_id' => '', 'full_name' => '', 'phone' => '', 'email' => '',
        'address' => '', 'subject' => '', 'created_at' => date('Y-m-d'),
    ];
}

function teacher_from_post(): array
{
    $t = [];
    foreach (array_keys(teacher_defaults()) as $k) {
        $t[$k] = trim((string)($_POST[$k] ?? ''));
    }
    return $t;
}

function teacher_validate(array $t): string
{
    if ($t['teacher_id'] === '' || $t['full_name'] === '') {
        return 'Teacher ID and Full Name are required.';
    }
    if ($t['email'] !== '' && !filter_var($t['email'], FILTER_VALIDATE_EMAIL)) {
        return 'Please enter a valid email address.';
    }
    if (!valid_date($t['created_at'])) {
        return 'Please enter a valid joined date.';
    }
    return '';
}

function teacher_form_render(array $t, string $button, int $id = 0): void
{
    ?>
    <form method="POST" class="form-grid">
        <?= csrf_field() ?>
        <?php if ($id > 0): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

        <?= input_field('Teacher ID', 'teacher_id', $t['teacher_id'], 'text', true, 'maxlength="30"') ?>
        <?= input_field('Full Name', 'full_name', $t['full_name'], 'text', true, 'maxlength="120"') ?>
        <?= input_field('Phone', 'phone', $t['phone'], 'text', false, 'maxlength="30"') ?>
        <?= input_field('Email', 'email', $t['email'], 'email', false, 'maxlength="120"') ?>
        <?= input_field('Main Subject / Specialization', 'subject', $t['subject'], 'text', false, 'maxlength="100"') ?>
        <?= input_field('Joined Date', 'created_at', $t['created_at'], 'date') ?>
        <div class="form-group full">
            <label for="f_address">Address</label>
            <input id="f_address" type="text" name="address" value="<?= e($t['address']) ?>" maxlength="255">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn"><?= e($button) ?></button>
            <a href="index.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
    <?php
}
