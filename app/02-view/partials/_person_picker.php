<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $people */
/** @var int|null $selectedId */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section class="card person-picker" data-component="person-picker">
    <h2>Personvelger</h2>
    <p class="muted">Velg hvem du handler på vegne av. Brukes senere til V3-påmelding.</p>
    <?php if ($people === []): ?>
        <p class="muted">Ingen personer tilgjengelig.</p>
    <?php else: ?>
        <form method="post" action="/min-side/personvelger">
            <input type="hidden" name="return_to" value="/min-side/profil">
            <ul class="person-picker-list">
                <?php foreach ($people as $person): ?>
                    <?php if (!is_array($person)) { continue; } ?>
                    <?php $pid = (int) ($person['person_id'] ?? 0); ?>
                    <li>
                        <label>
                            <input type="radio" name="person_id" value="<?= $pid ?>"
                                <?= $selectedId === $pid ? 'checked' : '' ?>>
                            <?= $h((string) ($person['label'] ?? $person['display_name'] ?? ('Person #' . $pid))) ?>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
            <button type="submit" class="button">Bytt person</button>
        </form>
    <?php endif; ?>
</section>
