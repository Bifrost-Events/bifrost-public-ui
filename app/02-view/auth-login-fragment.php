<?php

declare(strict_types=1);

/** @var string $error */
/** @var string $return_to */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<div class="login-modal-form">
    <?php if ($error): ?>
        <div class="login-modal-error"><?= $h($error) ?></div>
    <?php endif; ?>
    <form method="post" action="/auth/login" id="login-modal-form" class="login-modal-form-inner">
        <input type="hidden" name="return_to" value="<?= $h($return_to) ?>">
        <div class="form-group">
            <label for="login-modal-email">E-post</label>
            <input type="email" id="login-modal-email" name="email" required autocomplete="email">
        </div>
        <div class="form-group">
            <label for="login-modal-password">Passord</label>
            <input type="password" id="login-modal-password" name="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="login-modal-submit">Logg inn</button>
    </form>
    <p class="login-modal-register-cta"><a href="/auth/register?return_to=<?= rawurlencode($return_to) ?>">Registrer deg</a></p>
</div>
