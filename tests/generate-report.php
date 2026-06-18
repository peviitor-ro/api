<?php
$resultsFile = $argv[1] ?? __DIR__ . '/results.json';
$results = file_exists($resultsFile) ? json_decode(file_get_contents($resultsFile), true) : [];

$allTests = [];
$total = 0;
$passed = 0;
$failed = 0;

foreach ($results as $suite) {
    foreach ($suite['tests'] as $t) {
        $allTests[] = $t + ['file' => $suite['file'] ?? ''];
        $total++;
        if ($t['pass']) $passed++; else $failed++;
    }
}

$duration = array_sum(array_column($allTests, 'time'));
$pct = $total > 0 ? round($passed / $total * 100, 1) : 0;

$statusColor = $failed > 0 ? '#dc3545' : '#28a745';
$statusText = $failed > 0 ? "FAILED ($failed)" : "ALL PASSED";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Test Report — peviitor API</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Inter', -apple-system, sans-serif;
    background: #f5f0eb;
    color: #2d2a24;
    line-height: 1.6;
    padding: 2rem;
  }
  .container { max-width: 900px; margin: 0 auto; }
  h1 { font-size: 1.5rem; font-weight: 700; color: #c44536; margin-bottom: 0.5rem; }
  .subtitle { color: #7d6b5a; font-size: 0.9rem; margin-bottom: 2rem; }
  .summary {
    background: #fffcf9;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 12px rgba(90,60,40,0.08);
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
  }
  .summary-item { text-align: center; }
  .summary-item .num { font-size: 2rem; font-weight: 700; }
  .summary-item .label { font-size: 0.8rem; color: #7d6b5a; text-transform: uppercase; letter-spacing: 0.04em; }
  .summary .status { font-weight: 600; font-size: 1.2rem; color: <?= $statusColor ?>; display: flex; align-items: center; gap: 0.5rem; }
  .test-table { width: 100%; border-collapse: collapse; background: #fffcf9; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(90,60,40,0.08); }
  .test-table th { text-align: left; padding: 0.75rem 1rem; background: #f0e4d8; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; color: #5a4a3a; font-weight: 600; }
  .test-table td { padding: 0.65rem 1rem; border-bottom: 1px solid #f0e4d8; font-size: 0.9rem; }
  .test-table tr:last-child td { border-bottom: none; }
  .pass { color: #28a745; font-weight: 600; }
  .fail { color: #dc3545; font-weight: 600; }
  .error-msg { color: #dc3545; font-size: 0.82rem; font-family: 'Fira Code', monospace; background: #fff0f0; padding: 0.4rem 0.6rem; border-radius: 4px; margin-top: 0.3rem; display: inline-block; }
  .file-tag { font-size: 0.75rem; color: #9a8a7a; font-family: 'Fira Code', monospace; }
  .time-tag { font-size: 0.75rem; color: #9a8a7a; }
  .badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
  .badge-unit { background: #e8f5e9; color: #2e7d32; }
  .badge-int { background: #e3f2fd; color: #1565c0; }
  .badge-e2e { background: #fff3e0; color: #e65100; }
  footer { margin-top: 2rem; text-align: center; color: #9a8a7a; font-size: 0.8rem; }
</style>
</head>
<body>
<div class="container">
  <h1>🧪 Test Report</h1>
  <p class="subtitle">peviitor API v1 — <?= date('Y-m-d H:i:s') ?></p>

  <div class="summary">
    <div class="summary-item"><div class="num"><?= $total ?></div><div class="label">Total</div></div>
    <div class="summary-item"><div class="num" style="color:#28a745"><?= $passed ?></div><div class="label">Passed</div></div>
    <div class="summary-item"><div class="num" style="color:#dc3545"><?= $failed ?></div><div class="label">Failed</div></div>
    <div class="summary-item"><div class="num"><?= $pct ?>%</div><div class="label">Pass rate</div></div>
    <div class="summary-item"><div class="num"><?= round($duration, 1) ?>ms</div><div class="label">Duration</div></div>
    <div class="status">● <?= $statusText ?></div>
  </div>

  <table class="test-table">
    <thead><tr><th>Status</th><th>Test</th><th>File</th><th>Time</th></tr></thead>
    <tbody>
<?php foreach ($allTests as $t): ?>
      <tr>
        <td><span class="<?= $t['pass'] ? 'pass' : 'fail' ?>"><?= $t['pass'] ? '✓' : '✗' ?></span></td>
        <td>
          <?= htmlspecialchars($t['name']) ?>
          <?php if (!empty($t['error'])): ?>
            <div class="error-msg"><?= htmlspecialchars($t['error']) ?></div>
          <?php endif; ?>
        </td>
        <td class="file-tag">
          <?php
            $f = $t['file'];
            if (str_contains($f, 'TestSolr')) echo '<span class="badge badge-unit">unit</span>';
            elseif (str_contains($f, 'TestAuth')) echo '<span class="badge badge-unit">unit</span>';
            elseif (str_contains($f, 'TestValidation')) echo '<span class="badge badge-unit">unit</span>';
            elseif (str_contains($f, 'TestQueryBuild')) echo '<span class="badge badge-unit">unit</span>';
            elseif (str_contains($f, 'TestRandom')) echo '<span class="badge badge-int">int</span>';
            elseif (str_contains($f, 'TestEmpty')) echo '<span class="badge badge-int">int</span>';
            elseif (str_contains($f, 'TestCleanjobs')) echo '<span class="badge badge-int">int</span>';
            elseif (str_contains($f, 'TestSmoke')) echo '<span class="badge badge-e2e">e2e</span>';
            else echo htmlspecialchars($f);
          ?>
        </td>
        <td class="time-tag"><?= $t['time'] ?>ms</td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>

  <footer>Generated by peviitor API test suite</footer>
</div>
</body>
</html>
