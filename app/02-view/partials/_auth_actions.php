<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $user */
/** @var string $userName */
/** @var list<array<string, mixed>> $userMenuItems */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<?php if (is_array($user)): ?>
    <details class="user-menu">
        <summary class="user-menu-trigger"><?= $h($userName) ?></summary>
        <div class="user-menu-panel">
            <?php foreach ($userMenuItems as $item): ?>
                <?php if (!is_array($item)) { continue; } ?>
                <a href="<?= $h((string) ($item['url'] ?? '#')) ?>"
                   class="<?= $h((string) ($item['class'] ?? '')) ?>"><?= $h((string) ($item['label'] ?? '')) ?></a>
            <?php endforeach; ?>
        </div>
    </details>
<?php else: ?>
    <button type="button" class="btn btn-primary" id="register-modal-trigger">Registrer deg</button>
    <button type="button" class="btn" id="login-modal-trigger">Logg inn</button>
<?php endif; ?>
