<?php

declare(strict_types=1);

/** @var string $error */
/** @var string $return_to */
/** @var array{version: string, title: string, text: string} $userAgreement */

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$userVer = $h($userAgreement['version'] ?? '1.0');
$userAgreementHtml = nl2br($h($userAgreement['text'] ?? ''));
$returnTo = $return_to ?? '/onboarding';
?>
<section class="card auth-register-box">
    <h1>Registrer deg</h1>
    <?php if ($error !== ''): ?>
        <div class="login-modal-error"><?= $h($error) ?></div>
    <?php endif; ?>
    <form method="post" action="/auth/register" class="auth-register-form" id="auth-register-form">
        <input type="hidden" name="return_to" value="<?= $h($returnTo) ?>">
        <div class="form-group">
            <label for="reg-first_name">Fornavn *</label>
            <input type="text" id="reg-first_name" name="first_name" required autocomplete="given-name"
                   value="<?= $h((string) ($_POST['first_name'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="reg-last_name">Etternavn *</label>
            <input type="text" id="reg-last_name" name="last_name" required autocomplete="family-name"
                   value="<?= $h((string) ($_POST['last_name'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="reg-phone">Telefonnummer *</label>
            <input type="tel" id="reg-phone" name="phone" required autocomplete="tel"
                   value="<?= $h((string) ($_POST['phone'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="reg-email">E-postadresse *</label>
            <input type="email" id="reg-email" name="email" required autocomplete="email"
                   value="<?= $h((string) ($_POST['email'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="reg-password">Passord (min. 8 tegn) *</label>
            <input type="password" id="reg-password" name="password" required minlength="8" autocomplete="new-password">
        </div>
        <div class="form-group">
            <label for="reg-password_confirm">Bekreft passord *</label>
            <input type="password" id="reg-password_confirm" name="password_confirm" required minlength="8" autocomplete="new-password">
        </div>

        <div class="agreement-block">
            <p class="agreement-version">Versjon <?= $userVer ?></p>
            <p class="agreement-link-row">
                <a href="#" class="agreement-open-modal" data-title="<?= $h($userAgreement['title'] ?? 'Brukeravtale') ?>">Les brukeravtalen</a>
            </p>
            <label class="agreement-checkbox">
                <input type="checkbox" name="accept_user_agreement" id="reg-accept-user" value="1" required disabled>
                <span>Jeg godtar <?= $h($userAgreement['title'] ?? 'brukeravtalen') ?> (versjon <?= $userVer ?>) – åpne avtalen over for å aktivere</span>
            </label>
            <input type="hidden" name="user_agreement_version" value="<?= $userVer ?>">
        </div>

        <button type="submit" class="login-modal-submit">Registrer bruker og gå videre</button>
    </form>
    <p class="auth-register-login-link"><a href="/auth/login?return_to=<?= rawurlencode($returnTo) ?>">Har du konto? Logg inn</a></p>
</section>

<div id="agreement-modal-overlay" class="agreement-modal-overlay" aria-hidden="true">
    <div class="agreement-modal" role="dialog" aria-modal="true" aria-labelledby="agreement-modal-title">
        <div class="agreement-modal-header">
            <h3 id="agreement-modal-title" class="agreement-modal-title"></h3>
            <button type="button" class="agreement-modal-close" aria-label="Lukk">&times;</button>
        </div>
        <div class="agreement-modal-body"></div>
    </div>
</div>
<script type="text/template" id="user-agreement-html"><?= $userAgreementHtml ?></script>

<style>
.auth-register-box { max-width: 440px; margin: 0 auto; }
.auth-register-form .form-group { margin-bottom: 0.85rem; }
.agreement-block { margin: 1rem 0; padding: 0.75rem; background: #f9f9f9; border-radius: 6px; border: 1px solid var(--line); }
.agreement-link-row { margin: 0 0 0.5rem; font-size: 0.92rem; }
.agreement-version { font-size: 0.85rem; color: var(--muted); margin: 0 0 0.5rem; }
.agreement-checkbox { display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer; font-size: 0.9rem; }
.agreement-checkbox input { flex-shrink: 0; margin-top: 0.15rem; }
.agreement-checkbox input:disabled + span { color: var(--muted); cursor: not-allowed; }
.auth-register-login-link { margin-top: 1rem; font-size: 0.92rem; }
.agreement-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 10050; align-items: center; justify-content: center; padding: 1rem; }
.agreement-modal-overlay[aria-hidden="false"] { display: flex; }
.agreement-modal { background: #fff; border-radius: 8px; max-width: 560px; width: 100%; max-height: 85vh; display: flex; flex-direction: column; box-shadow: 0 8px 30px rgba(0,0,0,0.2); }
.agreement-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid var(--line); }
.agreement-modal-title { margin: 0; font-size: 1.05rem; }
.agreement-modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--muted); }
.agreement-modal-body { padding: 1.25rem; overflow-y: auto; font-size: 0.9rem; color: #444; }
</style>
<script>
(function() {
    var acceptUser = document.getElementById('reg-accept-user');
    var overlay = document.getElementById('agreement-modal-overlay');
    if (!overlay) return;
    var modalBody = overlay.querySelector('.agreement-modal-body');
    var modalTitle = overlay.querySelector('.agreement-modal-title');
    var closeBtn = overlay.querySelector('.agreement-modal-close');
    function showModal(title, content) {
        if (!modalBody || !modalTitle) return;
        modalTitle.textContent = title;
        modalBody.innerHTML = content;
        overlay.setAttribute('aria-hidden', 'false');
    }
    function hideModal() {
        overlay.setAttribute('aria-hidden', 'true');
    }
    overlay.addEventListener('click', function(e) { if (e.target === overlay) hideModal(); });
    if (closeBtn) closeBtn.addEventListener('click', hideModal);
    document.querySelectorAll('.agreement-open-modal').forEach(function(a) {
        a.addEventListener('click', function(e) {
            e.preventDefault();
            var title = this.getAttribute('data-title') || 'Avtale';
            var tpl = document.getElementById('user-agreement-html');
            showModal(title, tpl ? tpl.textContent : '');
            if (acceptUser) acceptUser.disabled = false;
        });
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && overlay.getAttribute('aria-hidden') === 'false') hideModal();
    });
})();
</script>
