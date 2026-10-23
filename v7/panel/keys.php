<?php
/**
 * /v7/panel/keys.php - step 3: the company's API keys (list, generate, revoke).
 * The company always comes from the session, never from the form.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../lib/examples.php';

try {
    $user = panelCurrentUser($SOLR_BASE, $SOLR_USER, $SOLR_PASS);
} catch (Exception $ex) {
    solrUnavailable($ex);
}
if (!$user) {
    redirect('./');
}
$cif = $user['cif'];

$newKey = null;
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    panelRequireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'logout') {
        panelLogout();
        redirect('./');
    }

    try {
        if ($action === 'generate') {
            if (countActiveApiKeys(listApiKeys($SOLR_BASE, $SOLR_USER, $SOLR_PASS, $cif)) >= MAX_ACTIVE_KEYS_PER_COMPANY) {
                $error = 'Firma are deja o cheie activă. Revoc-o înainte de a genera alta.';
            } else {
                $newKey = createApiKey($SOLR_BASE, $SOLR_USER, $SOLR_PASS, $cif, $user['company'] ?: $cif, $user['email']);
                error_log("PANEL KEY CREATED: email={$user['email']} cif=$cif prefix=" . apiKeyDisplayPrefix($newKey));
            }
        } elseif ($action === 'revoke') {
            try {
                $doc = revokeApiKey($SOLR_BASE, $SOLR_USER, $SOLR_PASS, (string)($_POST['prefix'] ?? ''), $cif);
                $message = 'Cheia ' . solrScalar($doc['prefix']) . ' a fost revocată. Aplicațiile care o folosesc primesc acum 401.';
                error_log("PANEL KEY REVOKED: email={$user['email']} cif=$cif prefix=" . solrScalar($doc['prefix']));
            } catch (InvalidArgumentException) {
                $error = 'Cheia nu a fost găsită.';
            }
        } else {
            $error = 'Acțiune necunoscută.';
        }
    } catch (Exception $ex) {
        solrUnavailable($ex);
    }
}

try {
    $keys = listApiKeys($SOLR_BASE, $SOLR_USER, $SOLR_PASS, $cif);
} catch (Exception $ex) {
    solrUnavailable($ex);
}
$activeCount = countActiveApiKeys($keys);
$jobsUrl = panelJobsUrl();

ob_start();
?>
<div class="card">
  <div class="card-header">
    <span><?= e($user['company']) ?> <span class="muted">· CIF <?= e($cif) ?></span></span>
    <form method="post" class="inline">
      <?= csrfField() ?>
      <span class="muted"><?= e($user['email']) ?></span>
      <button class="btn btn-secondary btn-small" name="action" value="logout">Ieșire</button>
    </form>
  </div>
  <div class="card-body">
    <?php if ($message): ?><p class="msg msg-ok"><?= e($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="msg msg-error"><?= e($error) ?></p><?php endif; ?>

    <?php if ($newKey): ?>
      <p class="msg msg-ok"><strong>Cheia a fost creată.</strong></p>
      <div class="key-box">
        <code id="new-key"><?= e($newKey) ?></code>
        <button class="btn btn-small" type="button" data-copy="new-key">Copiază cheia</button>
      </div>
      <p class="warn"><strong>Salvează cheia acum, într-un loc sigur</strong>
        (de exemplu un manager de parole sau variabilele de mediu ale aplicației).
        După ce părăsești sau reîncarci pagina nu o mai poți vedea și nici copia.</p>
    <?php endif; ?>

    <div class="row" style="justify-content:space-between;align-items:center;margin:0 0 1rem">
      <span class="muted"><?= $activeCount >= MAX_ACTIVE_KEYS_PER_COMPANY
          ? 'Firma are o cheie activă. Pentru o cheie nouă, revoc-o întâi pe cea existentă.'
          : 'Firma nu are nicio cheie activă.' ?></span>
      <form method="post" class="inline">
        <?= csrfField() ?>
        <button class="btn" name="action" value="generate" <?= $activeCount >= MAX_ACTIVE_KEYS_PER_COMPANY ? 'disabled style="opacity:.5;cursor:not-allowed"' : '' ?>>Generează cheie nouă</button>
      </form>
    </div>

    <?php if (!$keys): ?>
      <p class="muted">Firma nu are încă nicio cheie API.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Cheie</th><th>Stare</th><th>Creată</th><th>De</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($keys as $k): $active = isApiKeyDocUsable($k); $prefix = (string)solrScalar($k['prefix'] ?? ''); ?>
            <tr>
              <td><code><?= e($prefix) ?>…</code></td>
              <td>
                <?php if ($active): ?><span class="badge badge-active">activă</span>
                <?php else: ?><span class="badge badge-revoked">revocată <?= e(substr((string)solrScalar($k['revoked_at'] ?? ''), 0, 10)) ?></span><?php endif; ?>
              </td>
              <td><?= e(substr((string)solrScalar($k['created_at'] ?? ''), 0, 10)) ?></td>
              <td class="muted"><?= e(solrScalar($k['created_by'] ?? '') ?: 'admin') ?></td>
              <td>
                <?php if ($active): ?>
                  <form method="post" class="inline" data-confirm-revoke="<?= e($prefix) ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="prefix" value="<?= e($prefix) ?>">
                    <button class="btn btn-danger btn-small" name="action" value="revoke">Revocă</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="muted" style="margin-top:.75rem">Din motive de securitate păstrăm doar începutul cheilor, deci o cheie se poate copia numai la generare. Dacă ai pierdut-o, revoc-o și generează alta.</p>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-header">Cum folosești cheia</div>
  <div class="card-body">
    <p>Trimite cheia în headerul <code>X-Api-Key</code> la <code id="jobs-url"><?= e($jobsUrl) ?></code>
      <button class="btn btn-secondary btn-small" type="button" data-copy="jobs-url">Copiază</button></p>
    <p class="muted"><code>GET</code> joburile publicate de firmă · <code>POST</code> adaugă (max 100 odată) · <code>PUT</code> modifică · <code>DELETE</code> șterge (<code>{"url": …}</code> în body)</p>
    <p class="muted">Câmpuri: <code>url</code> și <code>title</code> și <code>location</code> obligatorii, <code>workmode</code> (on-site, remote, hybrid) și <code>tags</code> opționale. Numele firmei și CIF-ul se iau din cheie.</p>

    <div class="row" style="justify-content:space-between;align-items:center;margin-top:1.5rem">
      <strong style="color:#5a4a3a">Exemple</strong>
      <a class="btn btn-secondary btn-small" href="postman.php" download>Descarcă colecția Postman</a>
    </div>
    <p class="muted" style="margin:.5rem 0 1rem">Exemplele citesc cheia din variabila <code>PEVIITOR_API_KEY</code>, ca să nu o scrii în comenzi:
      <code>export PEVIITOR_API_KEY=pv_live_…</code>. În Postman, pune cheia în variabila de colecție <code>apiKey</code>.</p>
    <?php foreach (companyApiExamples() as $i => $ex): ?>
      <div class="row" style="justify-content:space-between;align-items:center;margin:1rem 0 .4rem">
        <span class="muted"><code><?= e($ex['method']) ?></code> <?= e($ex['name']) ?></span>
        <button class="btn btn-secondary btn-small" type="button" data-copy="example-<?= $i ?>">Copiază</button>
      </div>
      <pre id="example-<?= $i ?>"><?= e(exampleCurl($ex, $jobsUrl)) ?></pre>
    <?php endforeach; ?>
  </div>
</div>

<dialog id="confirm-revoke">
  <p class="dialog-title">Revoci cheia <code id="confirm-prefix"></code>?</p>
  <p class="muted">Aplicațiile care o folosesc nu vor mai putea publica joburi. Acțiunea nu poate fi anulată.</p>
  <div class="dialog-actions">
    <button class="btn btn-secondary" type="button" value="cancel">Anulează</button>
    <button class="btn btn-danger" type="button" value="ok">Revocă</button>
  </div>
</dialog>

<script>
// Revoke confirmation in a <dialog> instead of the browser's confirm()
const dialog = document.getElementById('confirm-revoke');
let pendingForm = null;
document.querySelectorAll('[data-confirm-revoke]').forEach(form => {
  form.addEventListener('submit', ev => {
    if (form.dataset.confirmed) {
      return;
    }
    ev.preventDefault();
    pendingForm = form;
    document.getElementById('confirm-prefix').textContent = form.dataset.confirmRevoke + '…';
    dialog.showModal();
    dialog.querySelector('[value=cancel]').focus();
  });
});
dialog.querySelectorAll('button').forEach(btn => btn.addEventListener('click', () => {
  dialog.close();
  if (btn.value === 'ok' && pendingForm) {
    pendingForm.dataset.confirmed = '1';
    // requestSubmit keeps the clicked button's name/value (action=revoke) in the POST
    pendingForm.requestSubmit(pendingForm.querySelector('[name=action]'));
  }
}));
// Click on the backdrop closes it, like Escape
dialog.addEventListener('click', ev => {
  if (ev.target === dialog) {
    dialog.close();
  }
});

// Copy buttons: data-copy="<id of the element whose text is copied>".
// navigator.clipboard needs HTTPS (or localhost); otherwise fall back to a hidden textarea.
document.querySelectorAll('[data-copy]').forEach(btn => {
  const label = btn.textContent;
  btn.addEventListener('click', async () => {
    const text = document.getElementById(btn.dataset.copy).textContent;
    let copied = false;
    try {
      await navigator.clipboard.writeText(text);
      copied = true;
    } catch (e) {
      const area = document.createElement('textarea');
      area.value = text;
      area.setAttribute('readonly', '');
      area.style.position = 'fixed';
      area.style.opacity = '0';
      document.body.appendChild(area);
      area.select();
      copied = document.execCommand('copy');
      area.remove();
    }
    if (!copied) {
      // Copying is blocked: select the text so Ctrl+C is enough
      const range = document.createRange();
      range.selectNodeContents(document.getElementById(btn.dataset.copy));
      getSelection().removeAllRanges();
      getSelection().addRange(range);
    }
    btn.textContent = copied ? 'Copiat ✓' : 'Selectat, apasă Ctrl+C';
    setTimeout(() => btn.textContent = label, copied ? 2000 : 4000);
  });
});
</script>
<?php
renderPage('Chei API', ob_get_clean());
