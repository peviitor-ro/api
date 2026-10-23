<?php
/**
 * /v7/panel/login.php?token=... - step 2: the login link.
 * GET only shows a button; the token is consumed on POST, so link scanners in
 * email clients (which follow links with GET) cannot use it up.
 */

require_once __DIR__ . '/bootstrap.php';

$token = trim((string)($_POST['token'] ?? $_GET['token'] ?? ''));
$invalid = !isValidLoginTokenFormat($token);

if (!$invalid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    panelRequireCsrf();
    try {
        $auth = consumeLoginToken($SOLR_BASE, $SOLR_USER, $SOLR_PASS, $token);
    } catch (Exception $ex) {
        solrUnavailable($ex);
    }
    if ($auth) {
        panelLogin($auth);
        error_log("PANEL LOGIN: email={$auth['email']} cif={$auth['cif']}");
        redirect('keys.php');
    }
    $invalid = true;
}

if ($invalid) {
    http_response_code(400);
}

ob_start();
?>
<div class="card">
  <div class="card-header">Link de acces</div>
  <div class="card-body">
    <?php if ($invalid): ?>
      <p class="msg msg-error">Linkul este invalid, expirat sau a fost deja folosit.</p>
      <p><a class="btn btn-secondary" href="./">Cere un link nou</a></p>
    <?php else: ?>
      <p>Apasă butonul pentru a intra în panoul companiei.</p>
      <form method="post" action="login.php" style="margin-top:1rem">
        <?= csrfField() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <button class="btn" type="submit">Intră în panou</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php
renderPage('Link de acces', ob_get_clean());
