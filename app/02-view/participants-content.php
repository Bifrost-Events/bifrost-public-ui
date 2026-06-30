<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $participants */
/** @var list<array<string, mixed>> $classes */
/** @var list<string> $club_suggestions */
/** @var string|null $error */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$formatJaktfeltId = static function (?string $id) use ($h): string {
    if ($id === null || $id === '') {
        return '–';
    }
    if (preg_match('/^\d{6}$/', $id)) {
        return 'JC-' . $id;
    }

    return $id;
};
?>
<section class="card">
    <h1>Mine deltakere</h1>
    <p class="muted">Registrer barn eller andre deltakere som du melder på stevner. Hver deltaker får en unik Jaktfelt-ID.</p>

    <?php if ($error): ?>
        <p class="status-bad"><?= $h($error) ?></p>
    <?php endif; ?>

    <?php if ($participants !== []): ?>
        <div style="display:flex; justify-content:space-between; align-items:center; margin: 1rem 0;">
            <h2 style="margin:0; font-size:1.1rem;">Dine deltakere</h2>
            <button type="button" class="btn btn-primary" data-modal-open="participant-modal">Lag ny</button>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Navn</th>
                    <th>Jaktfelt-ID</th>
                    <th>Klasse</th>
                    <th>Fødselsdato</th>
                    <th>Telefon</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($participants as $p): ?>
                    <?php if (!is_array($p)) { continue; } ?>
                    <tr>
                        <td><?= $h(trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''))) ?></td>
                        <td><code><?= $h($formatJaktfeltId(isset($p['jaktfelt_id']) ? (string) $p['jaktfelt_id'] : null)) ?></code></td>
                        <td><?= $h((string) ($p['class_name'] ?? '–')) ?></td>
                        <td><?= !empty($p['date_of_birth']) ? $h((string) $p['date_of_birth']) : '–' ?></td>
                        <td><?= !empty($p['phone']) ? $h((string) $p['phone']) : '–' ?></td>
                        <td>
                            <button type="button" class="btn btn-sm" data-modal-open="participant-modal"
                                    data-id="<?= (int) ($p['id'] ?? 0) ?>"
                                    data-first-name="<?= $h((string) ($p['first_name'] ?? '')) ?>"
                                    data-last-name="<?= $h((string) ($p['last_name'] ?? '')) ?>"
                                    data-class-id="<?= (int) ($p['class_id'] ?? 0) ?>"
                                    data-club="<?= $h((string) ($p['club'] ?? '')) ?>"
                                    data-date-of-birth="<?= $h((string) ($p['date_of_birth'] ?? '')) ?>"
                                    data-phone="<?= $h((string) ($p['phone'] ?? '')) ?>">Rediger</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="muted"><em>Du har ikke registrert noen deltakere ennå.</em></p>
        <button type="button" class="btn btn-primary" data-modal-open="participant-modal">Lag ny deltaker</button>
    <?php endif; ?>
</section>

<div id="participant-modal" class="modal-overlay" aria-hidden="true">
    <div class="modal">
        <div class="modal-header">
            <h3 id="participant-modal-title">Opprett ny deltaker</h3>
            <button type="button" class="modal-close" data-modal-close="participant-modal" aria-label="Lukk">&times;</button>
        </div>
        <form id="participant-form" method="post" action="/min-side/deltakere">
            <input type="hidden" name="participant_id" id="participant_id" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label for="first_name">Fornavn *</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Etternavn *</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                <div class="form-group">
                    <label for="date_of_birth">Fødselsdato (valgfritt)</label>
                    <input type="date" id="date_of_birth" name="date_of_birth">
                </div>
                <div class="form-group">
                    <label for="phone">Telefon (valgfritt)</label>
                    <input type="tel" id="phone" name="phone" placeholder="f.eks. 12345678">
                </div>
                <div class="form-group">
                    <label for="class_id">Klasse *</label>
                    <select id="class_id" name="class_id" required>
                        <option value="">Velg klasse</option>
                        <?php foreach ($classes as $c): ?>
                            <?php if (!is_array($c)) { continue; } ?>
                            <option value="<?= (int) ($c['id'] ?? 0) ?>"><?= $h((string) ($c['name'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="club">Klubb / skytterlag (valgfritt)</label>
                    <input type="text" id="club" name="club" maxlength="200" list="club-suggestions" placeholder="Valgfritt">
                    <datalist id="club-suggestions">
                        <?php foreach ($club_suggestions as $cs): ?>
                            <?php $v = trim((string) $cs); if ($v === '') { continue; } ?>
                            <option value="<?= $h($v) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-modal-close="participant-modal">Avbryt</button>
                <button type="submit" class="btn btn-primary">Lagre</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 1000; align-items: center; justify-content: center; padding: 1rem; }
.modal-overlay[aria-hidden="false"] { display: flex; }
.modal { background: #fff; border-radius: 8px; max-width: 420px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 8px 30px rgba(0,0,0,0.2); }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid var(--line); }
.modal-header h3 { margin: 0; font-size: 1.05rem; }
.modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--muted); line-height: 1; }
.modal-body { padding: 1.25rem; }
.modal-footer { padding: 1rem 1.25rem; border-top: 1px solid var(--line); display: flex; gap: 0.5rem; justify-content: flex-end; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.35rem; font-weight: 600; font-size: 0.92rem; }
.form-group input, .form-group select { width: 100%; padding: 0.55rem 0.65rem; border: 1px solid var(--line); border-radius: 6px; font: inherit; }
.btn-sm { padding: 0.35rem 0.65rem; font-size: 0.88rem; }
</style>
<script>
(function () {
    const modal = document.getElementById('participant-modal');
    const form = document.getElementById('participant-form');
    const title = document.getElementById('participant-modal-title');
    const idField = document.getElementById('participant_id');

    function openModal(btn) {
        const isEdit = btn && btn.dataset.id;
        title.textContent = isEdit ? 'Rediger deltaker' : 'Opprett ny deltaker';
        form.action = isEdit ? '/min-side/deltakere/' + btn.dataset.id : '/min-side/deltakere';
        idField.value = isEdit ? btn.dataset.id : '';
        document.getElementById('first_name').value = btn?.dataset.firstName || '';
        document.getElementById('last_name').value = btn?.dataset.lastName || '';
        document.getElementById('class_id').value = btn?.dataset.classId || '';
        document.getElementById('club').value = btn?.dataset.club || '';
        document.getElementById('date_of_birth').value = btn?.dataset.dateOfBirth || '';
        document.getElementById('phone').value = btn?.dataset.phone || '';
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        modal.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('[data-modal-open="participant-modal"]').forEach(function (btn) {
        btn.addEventListener('click', function () { openModal(btn); });
    });
    document.querySelectorAll('[data-modal-close="participant-modal"]').forEach(function (btn) {
        btn.addEventListener('click', closeModal);
    });
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
})();
</script>
