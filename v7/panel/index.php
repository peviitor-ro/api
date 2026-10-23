<?php
/**
 * /v7/panel/ - step 1: the company user enters the email authorized for their company
 * and gets a single-use login link (see v7/lib/panel.php).
 */

require_once __DIR__ . '/bootstrap.php';

if (!empty($_SESSION['user'])) {
    redirect('keys.php');
}

$error = null;
$sent = false;
$stubLink = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    panelRequireCsrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $normalized = normalizePanelEmail($email);

    if ($normalized === null) {
        $error = 'Introdu o adresă de email validă.';
    } else {
        try {
            $overLimit = isOverLinkRateLimit(
                countRecentTokens($SOLR_BASE, $SOLR_USER, $SOLR_PASS, 'email', $normalized),
                countRecentTokens($SOLR_BASE, $SOLR_USER, $SOLR_PASS, 'ip', clientIp())
            );
            if ($overLimit) {
                error_log("PANEL LINK RATE LIMITED: email=$normalized ip=" . clientIp());
            } else {
                $auth = findAuthorizedEmail($SOLR_BASE, $SOLR_USER, $SOLR_PASS, $normalized);
                if ($auth) {
                    $token = createLoginToken($SOLR_BASE, $SOLR_USER, $SOLR_PASS, $normalized, clientIp());
                    $stubLink = deliverLoginLink($normalized, panelUrl('login.php?token=' . $token));
                } else {
                    error_log("PANEL LINK REFUSED (email not authorized): email=$normalized ip=" . clientIp());
                }
            }
            // Same answer whether or not the email is authorized or rate limited
            $sent = true;
        } catch (Exception $ex) {
            solrUnavailable($ex);
        }
    }
}

ob_start();
?>
<div class="card">
  <div class="card-header">Intră în panoul companiei</div>
  <div class="card-body">
    <?php if ($sent): ?>
      <p class="msg msg-ok">Dacă adresa <strong><?= e($email) ?></strong> este autorizată pentru o companie, vei primi pe email un link de acces, valabil 15 minute.</p>
      <?php if ($stubLink): ?>
        <div class="stub">
          <strong>STUB — în producție acest link se trimite pe email</strong>
          <a href="<?= e($stubLink) ?>"><?= e($stubLink) ?></a>
        </div>
      <?php endif; ?>
      <p style="margin-top:1rem"><a href="./">Încearcă alt email</a></p>
    <?php else: ?>
      <?php if ($error): ?><p class="msg msg-error"><?= e($error) ?></p><?php endif; ?>
      <p class="muted">Folosește adresa de email cu care firma ta a fost înregistrată. Îți trimitem un link prin care intri în panou, de unde poți genera cheile API pentru publicarea joburilor.</p>
      <form method="post" action="./">
        <?= csrfField() ?>
        <div class="row">
          <div style="flex:1;min-width:220px">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required maxlength="254" autocomplete="email" value="<?= e($email) ?>">
          </div>
          <button class="btn" type="submit">Trimite link de acces</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php
renderPage('Autentificare', ob_get_clean());
