<?php
/**
 * Shared setup for the panel pages: env, security headers, session, Solr config,
 * and the HTML layout (same look as the API documentation page, /index.php).
 */

require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');
require_once __DIR__ . '/../lib/panel.php';

header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
// The login token is in the url of login.php: never send it to other sites
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');

panelStartSession();
[$SOLR_BASE, $SOLR_USER, $SOLR_PASS] = panelSolrConfig();

function e(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $page): never {
    header("Location: $page", true, 303);
    exit;
}

function csrfField(): string {
    return '<input type="hidden" name="csrf" value="' . e(panelCsrfToken()) . '">';
}

function solrUnavailable(Exception $ex): never {
    error_log("PANEL SOLR FAILED: " . $ex->getMessage());
    http_response_code(503);
    renderPage('Indisponibil', '<div class="card"><div class="card-body"><p class="msg msg-error">Serviciul este momentan indisponibil. Încearcă din nou în câteva minute.</p></div></div>');
    exit;
}

function renderPage(string $title, string $body): void {
    $title = e($title);
    echo <<<HTML
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>$title — peviitor pentru companii</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Inter', -apple-system, sans-serif;
    background: linear-gradient(135deg, #fdf6f0 0%, #f5e6d3 50%, #f0dcc8 100%);
    color: #2d2a24; min-height: 100vh; line-height: 1.6;
  }
  .container { max-width: 760px; margin: 0 auto; padding: 2rem 1rem 4rem; }
  header { text-align: center; padding: 2rem 0; }
  .logo-img { height: 32px; width: auto; }
  header h1 { font-size: 1.6rem; font-weight: 700; color: #c44536; letter-spacing: -0.02em; margin-top: .5rem; }
  header p { color: #7d6b5a; font-size: 0.95rem; margin-top: 0.4rem; }
  .card { background: #fffcf9; border-radius: 16px; box-shadow: 0 4px 24px rgba(90, 60, 40, 0.10); overflow: hidden; margin-bottom: 1.5rem; }
  .card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid #f0e4d8; font-weight: 600; font-size: 0.95rem; color: #5a4a3a; display: flex; align-items: center; justify-content: space-between; gap: .5rem; flex-wrap: wrap; }
  .card-body { padding: 1.5rem; }
  .card-body p + p { margin-top: .75rem; }
  label { display: block; font-weight: 600; font-size: .9rem; color: #5a4a3a; margin-bottom: .4rem; }
  input[type=email] { width: 100%; padding: .7rem .9rem; border: 1px solid #e8d5c4; border-radius: 8px; font: inherit; background: #fff; }
  input[type=email]:focus { outline: 2px solid #c44536; border-color: transparent; }
  .btn { display: inline-block; border: none; border-radius: 8px; padding: .65rem 1.2rem; font: inherit; font-weight: 600; cursor: pointer; background: #c44536; color: #fff; text-decoration: none; transition: opacity .2s; }
  .btn:hover { opacity: .88; }
  .btn-secondary { background: #e8d5c4; color: #5a4a3a; }
  .btn-small { padding: .3rem .7rem; font-size: .8rem; }
  .btn-danger { background: #c62828; }
  form.inline { display: inline; }
  .row { display: flex; gap: .75rem; align-items: end; flex-wrap: wrap; margin-top: 1rem; }
  .msg { padding: .75rem 1rem; border-radius: 8px; font-size: .9rem; margin-bottom: 1rem; }
  .msg-ok { background: #f0faf3; border-left: 4px solid #2e7d32; }
  .msg-error { background: #fef0f0; border-left: 4px solid #c62828; }
  .stub { background: #fff3e0; border-left: 4px solid #e65100; padding: .75rem 1rem; border-radius: 6px; font-size: .85rem; margin-top: 1rem; word-break: break-all; }
  .stub strong { display: block; margin-bottom: .3rem; }
  .warn { background: #fff3e0; border-left: 4px solid #e65100; padding: .75rem 1rem; border-radius: 6px; font-size: .85rem; margin: 0 0 1.25rem; }
  .warn strong { display: block; margin-bottom: .2rem; }
  code, pre { font-family: 'Fira Code', monospace; font-size: .82rem; }
  pre { background: #2d2a24; color: #f4e9d8; padding: 1rem; border-radius: 8px; overflow-x: auto; white-space: pre-wrap; word-break: break-all; }
  .key-box { display: flex; gap: .5rem; align-items: center; background: #f0faf3; border: 1px solid #d4e8db; border-radius: 8px; padding: .75rem; margin: .75rem 0; }
  .key-box code { flex: 1; word-break: break-all; color: #1b5e20; font-weight: 500; }
  table { width: 100%; border-collapse: collapse; font-size: .85rem; }
  th, td { text-align: left; padding: .6rem .5rem; border-bottom: 1px solid #f0e4d8; vertical-align: middle; }
  th { color: #7d6b5a; font-weight: 600; font-size: .75rem; text-transform: uppercase; letter-spacing: .03em; }
  .table-wrap { overflow-x: auto; }
  .badge { display: inline-block; font-size: .72rem; font-weight: 600; padding: .15rem .5rem; border-radius: 6px; }
  .badge-active { background: #e8f5ec; color: #1b5e20; }
  .badge-revoked { background: #f0e4d8; color: #7d6b5a; }
  .muted { color: #7d6b5a; font-size: .85rem; }
  dialog { margin: auto; border: none; border-radius: 16px; padding: 1.5rem; max-width: 420px; width: calc(100% - 2rem); background: #fffcf9; color: #2d2a24; box-shadow: 0 12px 48px rgba(90, 60, 40, 0.25); }
  dialog::backdrop { background: rgba(45, 42, 36, 0.35); backdrop-filter: blur(2px); }
  .dialog-title { font-weight: 600; color: #5a4a3a; margin-bottom: .5rem; }
  .dialog-actions { display: flex; justify-content: flex-end; gap: .5rem; margin-top: 1.25rem; }
  footer { text-align: center; color: #7d6b5a; font-size: .8rem; margin-top: 2rem; }
  footer a, .card a { color: #c44536; }
</style>
</head>
<body>
<div class="container">
  <header>
    <a href="https://peviitor.ro" class="logo-link"><img src="https://peviitor.ro/assets/logo-DV2JQkir.svg" alt="peviitor" class="logo-img"></a>
    <h1>peviitor pentru companii</h1>
    <p>Publică joburile firmei tale pe peviitor.ro prin API</p>
  </header>
  $body
  <footer>peviitor.ro · proiect de voluntariat</footer>
</div>
</body>
</html>
HTML;
}
