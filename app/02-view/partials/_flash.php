<?php

declare(strict_types=1);

/** @var array{type: string, message: string, errors: array<string, string>}|null $flash */
$flash = $flash ?? null;
if ($flash === null || trim((string) ($flash['message'] ?? '')) === '') {
    return;
}

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$type = (string) ($flash['type'] ?? 'info');
$class = match ($type) {
    'error' => 'flash-error',
    'success' => 'flash-success',
    default => 'flash-info',
};
?>
<div class="flash <?= $h($class) ?>" role="status">
    <p><?= $h((string) $flash['message']) ?></p>
</div>
