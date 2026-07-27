<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>peviitor API — Documentation</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Inter', -apple-system, sans-serif;
    background: linear-gradient(135deg, #fdf6f0 0%, #f5e6d3 50%, #f0dcc8 100%);
    color: #2d2a24;
    min-height: 100vh;
    line-height: 1.6;
  }
  .container { max-width: 960px; margin: 0 auto; padding: 2rem 1.5rem 4rem; }

  /* Header */
  header {
    text-align: center;
    padding: 2rem 0 2rem;
    position: relative;
  }
  .logo-link {
    display: inline-block;
    line-height: 0;
  }
  .logo-img {
    height: 32px;
    width: auto;
    transition: opacity 0.2s;
  }
  .logo-img:hover { opacity: 0.8; }
  header h1 {
    font-size: 1.6rem;
    font-weight: 700;
    color: #c44536;
    letter-spacing: -0.02em;
    margin: 0;
  }
  header p {
    color: #7d6b5a;
    font-size: 0.95rem;
    margin-top: 0.4rem;
  }
  header .base-url {
    display: inline-block;
    margin-top: 0.6rem;
    padding: 0.4rem 1rem;
    background: #e8d5c4;
    border-radius: 8px;
    font-family: 'Fira Code', monospace;
    font-size: 0.85rem;
    color: #5a4a3a;
  }

  /* Lang toggle */
  .lang-toggle {
    position: absolute;
    top: 0;
    right: 0;
    display: inline-flex;
    background: #e8d5c4;
    border-radius: 8px;
    overflow: hidden;
  }
  .lang-toggle button {
    border: none;
    background: transparent;
    padding: 0.35rem 0.75rem;
    font-family: 'Inter', sans-serif;
    font-size: 0.8rem;
    font-weight: 600;
    color: #7d6b5a;
    cursor: pointer;
    transition: all 0.2s;
  }
  .lang-toggle button.active {
    background: #c44536;
    color: #fff;
  }
  .lang-toggle button:not(.active):hover { color: #5a4a3a; }

  .test-badge-link {
    display: inline-block;
    margin-top: 0.6rem;
    text-decoration: none;
  }
  .test-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.3rem 0.75rem;
    border-radius: 8px;
    background: #2d2a24;
    color: #f4e9d8;
    transition: opacity 0.2s;
  }
  .test-badge:hover { opacity: 0.8; }

  /* Card */
  .card {
    background: #fffcf9;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(90, 60, 40, 0.10);
    overflow: hidden;
    margin-bottom: 1.5rem;
  }
  .card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f0e4d8;
    font-weight: 600;
    font-size: 0.95rem;
    color: #5a4a3a;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  .card-body { padding: 1.5rem; }

  /* Endpoint row */
  .endpoint-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, #f0faf3, #e8f5ec);
    border-bottom: 1px solid #d4e8db;
    cursor: pointer;
    user-select: none;
    transition: background 0.2s;
  }
  .endpoint-row:hover { opacity: 0.92; }
  .toggle-arrow {
    font-size: 0.75rem;
    color: #7d6b5a;
    transition: transform 0.25s ease;
    margin-left: auto;
  }
  .toggle-arrow.open { transform: rotate(90deg); }
  .endpoint-content { overflow: hidden; }
  .method-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-family: 'Fira Code', monospace;
    font-weight: 700;
    font-size: 0.8rem;
    padding: 0.3rem 0.7rem;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    min-width: 52px;
    background: #2e7d32;
    color: #fff;
    box-shadow: 0 2px 6px rgba(46, 125, 50, 0.3);
  }
  .endpoint-path {
    font-family: 'Fira Code', monospace;
    font-size: 1rem;
    font-weight: 500;
    color: #1b5e20;
  }
  .endpoint-desc {
    font-size: 0.85rem;
    color: #4a7c5a;
  }

  /* DELETE badge variant */
  .method-badge-delete {
    background: #c62828;
    box-shadow: 0 2px 6px rgba(198, 40, 40, 0.3);
  }
  .endpoint-row-delete {
    background: linear-gradient(135deg, #fef0f0, #fce4e4);
    border-bottom: 1px solid #f5cdcd;
    cursor: pointer;
    user-select: none;
  }
  .endpoint-row-delete:hover { opacity: 0.92; }
  .endpoint-row-delete .endpoint-path { color: #b71c1c; }
  .endpoint-row-delete .endpoint-desc { color: #b55a5a; }

  /* Warning banner */
  .warning-banner {
    background: #fff3e0;
    border-left: 4px solid #e65100;
    padding: 0.75rem 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    font-size: 0.85rem;
    color: #bf360c;
  }
  .warning-banner strong { font-weight: 700; }

  /* Properties table */
  .prop-table { width: 100%; border-collapse: collapse; }
  .prop-table th, .prop-table td {
    text-align: left;
    padding: 0.6rem 0.75rem;
    border-bottom: 1px solid #f0e4d8;
    font-size: 0.9rem;
  }
  .prop-table th {
    font-weight: 600;
    color: #5a4a3a;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding-top: 0;
  }
  .prop-table td:first-child {
    font-family: 'Fira Code', monospace;
    font-weight: 500;
    color: #c44536;
    white-space: nowrap;
  }
  .prop-table tr:last-child td { border-bottom: none; }
  .type-tag {
    display: inline-block;
    font-family: 'Fira Code', monospace;
    font-size: 0.75rem;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    background: #f0e4d8;
    color: #7d6b5a;
  }

  /* Status codes */
  .status-list { list-style: none; }
  .status-list li {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f5ede6;
    font-size: 0.9rem;
  }
  .status-list li:last-child { border-bottom: none; }
  .status-code {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-family: 'Fira Code', monospace;
    font-weight: 600;
    font-size: 0.78rem;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    min-width: 40px;
  }
  .sc-200 { background: #e8f5e9; color: #2e7d32; }
  .sc-401 { background:#ffebee;color:#c62828; }
  .sc-404 { background: #fff3e0; color: #e65100; }
  .sc-405 { background:#e8eaf6;color:#283593; }
  .sc-503 { background: #ffebee; color: #c62828; }

  /* Code blocks */
  pre {
    background: #2d2a24;
    color: #f4e9d8;
    font-family: 'Fira Code', monospace;
    font-size: 0.82rem;
    line-height: 1.5;
    padding: 1rem 1.25rem;
    border-radius: 10px;
    overflow-x: auto;
    tab-size: 2;
  }
  code { font-family: 'Fira Code', monospace; }
  pre .json-key { color: #f9a875; }
  pre .json-string { color: #b8d99c; }
  pre .json-number { color: #82c1e0; }
  pre .json-bool { color: #d4a0e8; }
  pre .json-null { color: #999; }

  /* curl example */
  .curl-box {
    background: #2d2a24;
    border-radius: 10px;
    overflow: hidden;
  }
  .curl-box .curl-label {
    padding: 0.5rem 1rem;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #a09080;
    background: #3a3530;
    border-bottom: 1px solid #4a4440;
  }
  .curl-box pre { border-radius: 0; background: transparent; }

  /* Section spacing */
  .section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #5a4a3a;
    margin-bottom: 0.75rem;
  }

  /* New sections */
  .context-box {
    background: #fffcf9;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 24px rgba(90, 60, 40, 0.10);
    font-size: 0.95rem;
    color: #5a4a3a;
    line-height: 1.7;
  }
  h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #c44536;
    margin: 2rem 0 0.75rem;
    letter-spacing: -0.01em;
  }
  h2:first-of-type { margin-top: 2rem; }
  .section-desc {
    color: #5a4a3a;
    font-size: 0.9rem;
    margin-bottom: 1.25rem;
    line-height: 1.7;
  }
  .future-list {
    list-style: none;
    padding: 0;
    margin: 0 0 1.25rem 0;
  }
  .future-list li {
    padding: 0.4rem 0 0.4rem 1.25rem;
    position: relative;
    color: #5a4a3a;
    font-size: 0.9rem;
    line-height: 1.6;
  }
  .future-list li::before {
    content: "—";
    position: absolute;
    left: 0;
    color: #c44536;
    font-weight: 600;
  }

  /* Footer */
  footer {
    text-align: center;
    padding: 2rem 0;
    color: #9a8a7a;
    font-size: 0.8rem;
  }
  footer a { color: #c44536; text-decoration: none; }
  footer a:hover { text-decoration: underline; }

  /* Responsive */
  @media (max-width: 600px) {
    .lang-toggle { position: static; margin-bottom: 1rem; }
    header { display: flex; flex-direction: column; align-items: center; }
    .endpoint-desc { display: none; }
  }
</style>
</head>
<body>
<div class="container">

  <header>
    <div class="lang-toggle">
      <button onclick="setLang('en')" id="lang-en">EN</button>
      <button onclick="setLang('ro')" id="lang-ro" class="active">RO</button>
    </div>
    <a href="https://peviitor.ro" target="_blank" class="logo-link">
      <img src="https://peviitor.ro/assets/logo-DV2JQkir.svg" alt="peviitor" class="logo-img">
    </a>
    <h1 data-i18n="brand">peviitor API</h1>
    <p data-i18n="subtitle">Platformă de descoperire a joburilor — documentație API publică</p>
     <div class="base-url">https://api.peviitor.ro</div>
    <a href="/tests/report.html" target="_blank" class="test-badge-link">
      <span class="test-badge">🧪 Test Report</span>
    </a>
  </header>

  <div class="context-box">
    <p data-i18n="contextParagraph">
      Această pagină expune endpoint-uri publice ale API-ului peviitor.ro, o platformă de descoperire a joburilor.
      Suntem în proces de revizuire și extindere a API-ului, iar documentația se va îmbunătăți treptat.
    </p>
  </div>

  <h2 data-i18n="availableEndpointsTitle">Endpoint-uri disponibile în prezent</h2>
  <p class="section-desc" data-i18n="availableEndpointsDesc">
    Endpoint-urile de mai jos sunt disponibile în acest moment și pot fi folosite pentru testare și explorare.
    API-ul este în curs de standardizare, iar pentru fiecare funcționalitate vom publica treptat endpoint-uri noi,
    împreună cu o documentație mai detaliată.
  </p>

  <!-- ============================================= -->
  <!-- RANDOM ENDPOINT -->
  <!-- ============================================= -->

  <!-- RANDOM ENDPOINT -->
  <div class="card">
    <div class="endpoint-row" onclick="toggleEndpoint('random')">
      <span class="method-badge">GET</span>
      <span class="endpoint-path">/v1/random/</span>
      <span class="endpoint-desc" data-i18n="endpointTag">Get a random job listing</span>
      <span class="toggle-arrow" id="arrow-random">&#9654;</span>
    </div>
  </div>

  <div id="random-content" class="endpoint-content" style="display:none">

    <div class="card">
      <div class="card-body">
        <p style="margin-bottom:1rem;color:#5a4a3a;" data-i18n="endpointDesc">
          Returns a single random job from the Solr job index. Useful for discovery widgets,
          "job of the day" features, or testing integrations.
        </p>

        <div class="section-title" data-i18n="howItWorksTitle">How it works</div>
        <ol style="margin:0 0 1.5rem 1.2rem;color:#5a4a3a;font-size:0.9rem;">
          <li data-i18n="how1">Queries Solr for the total number of indexed jobs</li>
          <li data-i18n="how2">Picks a random offset between 0 and total count</li>
          <li data-i18n="how3">Fetches exactly one document at that offset</li>
          <li data-i18n="how4">Returns the job mapped to the peviitor Job Model Schema</li>
        </ol>

        <div class="section-title" data-i18n="tryItTitle">Try it</div>
        <div class="curl-box">
          <div class="curl-label">curl</div>
          <pre>curl -X GET "https://api.peviitor.ro/v1/random/" \
  -H "Accept: application/json"</pre>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="respFieldsTitle">Response fields</div>
      <div class="card-body">
        <table class="prop-table">
          <thead><tr>
            <th>Field</th><th>Type</th><th data-i18n="description">Description</th>
          </tr></thead>
          <tbody>
            <tr><td>title</td><td><span class="type-tag">string</span></td><td data-i18n="descTitle">Exact job position title</td></tr>
            <tr><td>company</td><td><span class="type-tag">string</span></td><td data-i18n="descCompany">Hiring company name (uppercase)</td></tr>
            <tr><td>location</td><td><span class="type-tag">string[]</span></td><td data-i18n="descLocation">Array of cities or addresses</td></tr>
            <tr><td>workmode</td><td><span class="type-tag">string</span></td><td data-i18n="descWorkmode"><code>remote</code>, <code>on-site</code>, or <code>hybrid</code></td></tr>
            <tr><td>url</td><td><span class="type-tag">string</span></td><td data-i18n="descUrl">Full URL to the job detail page (unique key)</td></tr>
            <tr><td>salary</td><td><span class="type-tag">string</span></td><td data-i18n="descSalary">Salary interval with currency, e.g. <code>5000-8000 RON</code></td></tr>
            <tr><td>tags</td><td><span class="type-tag">string[]</span></td><td data-i18n="descTags">Skill tags (lowercase, max 20)</td></tr>
            <tr><td>cif</td><td><span class="type-tag">string</span></td><td data-i18n="descCif">CIF/CUI of the company</td></tr>
            <tr><td>date</td><td><span class="type-tag">string</span></td><td data-i18n="descDate">ISO8601 UTC timestamp of indexing</td></tr>
            <tr><td>status</td><td><span class="type-tag">string</span></td><td data-i18n="descStatus"><code>scraped</code>, <code>tested</code>, <code>published</code>, or <code>verified</code></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="successTitle">200 — Success</div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"title"</span>: <span class="json-string">"Inginer IT"</span>,
  <span class="json-key">"company"</span>: <span class="json-string">"COMPANY SRL"</span>,
  <span class="json-key">"location"</span>: [<span class="json-string">"Bucure\u0219ti"</span>, <span class="json-string">"Cluj-Napoca"</span>],
  <span class="json-key">"workmode"</span>: <span class="json-string">"remote"</span>,
  <span class="json-key">"url"</span>: <span class="json-string">"https://example.com/job/123"</span>,
  <span class="json-key">"salary"</span>: <span class="json-string">"5000-8000 RON"</span>,
  <span class="json-key">"tags"</span>: [<span class="json-string">"python"</span>, <span class="json-string">"java"</span>],
  <span class="json-key">"cif"</span>: <span class="json-string">"12345678"</span>,
  <span class="json-key">"date"</span>: <span class="json-string">"2026-06-15T10:00:00Z"</span>,
  <span class="json-key">"status"</span>: <span class="json-string">"published"</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">404 — <span data-i18n="notFoundTitle">No Jobs Found</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"No jobs found"</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">503 — <span data-i18n="unavailTitle">Service Unavailable</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Job core unavailable"</span>,
  <span class="json-key">"details"</span>: <span class="json-string">"SOLR_SERVER not set"</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span data-i18n="requirementsTitle">Requirements</span> &mdash; <span data-i18n="randomEndpoint">Random</span></div>
      <div class="card-body">
        <table class="prop-table">
          <thead><tr><th style="width:100px" data-i18n="item">Item</th><th data-i18n="details">Details</th></tr></thead>
          <tbody>
            <tr><td data-i18n="method">Method</td><td><code>GET</code> only</td></tr>
            <tr><td data-i18n="auth">Auth</td><td data-i18n="authVal">None (public endpoint)</td></tr>
            <tr><td data-i18n="params">Params</td><td data-i18n="paramsVal">None</td></tr>
            <tr><td data-i18n="contentType">Content-Type</td><td><code>application/json</code></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="statusCodesTitle">Status codes</div>
      <div class="card-body">
        <ul class="status-list">
          <li><span class="status-code sc-200">200</span><span data-i18n="status200">A random job was found and returned successfully</span></li>
          <li><span class="status-code sc-404">404</span><span data-i18n="status404">No jobs are currently indexed in the Solr core</span></li>
          <li><span class="status-code sc-503">503</span><span data-i18n="status503">Solr core is unavailable or environment not configured</span></li>
        </ul>
      </div>
    </div>

  </div>

  <!-- ============================================= -->
  <!-- TOTAL ENDPOINT -->
  <!-- ============================================= -->

  <div class="card">
    <div class="endpoint-row" onclick="toggleEndpoint('total')">
      <span class="method-badge">GET</span>
      <span class="endpoint-path">/v1/total/</span>
      <span class="endpoint-desc" data-i18n="totalTag">Get total jobs and companies count</span>
      <span class="toggle-arrow" id="arrow-total">&#9654;</span>
    </div>
  </div>

  <div id="total-content" class="endpoint-content" style="display:none">

    <div class="card">
      <div class="card-body">
        <p style="margin-bottom:1rem;color:#5a4a3a;" data-i18n="totalDesc">
          Returns the total number of job listings and companies currently indexed in the Solr cores.
          Useful for dashboard counters, statistics widgets, or monitoring integrations.
        </p>

        <div class="section-title" data-i18n="howItWorksTitle">How it works</div>
        <ol style="margin:0 0 1.5rem 1.2rem;color:#5a4a3a;font-size:0.9rem;">
          <li data-i18n="totalHow1">Queries the <code>job</code> Solr core for the total number of indexed jobs</li>
          <li data-i18n="totalHow2">Queries the <code>company</code> Solr core for the total number of indexed companies</li>
          <li data-i18n="totalHow3">Returns both counts in a single response object</li>
        </ol>

        <div class="section-title" data-i18n="tryItTitle">Try it</div>
        <div class="curl-box">
          <div class="curl-label">curl</div>
          <pre>curl -X GET "https://api.peviitor.ro/v1/total/" \
  -H "Accept: application/json"</pre>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="totalRespFieldsTitle">Response fields</div>
      <div class="card-body">
        <table class="prop-table">
          <thead><tr>
            <th>Field</th><th>Type</th><th data-i18n="description">Description</th>
          </tr></thead>
          <tbody>
            <tr><td>total</td><td><span class="type-tag">object</span></td><td data-i18n="totalDescTotal">Container object with count fields</td></tr>
            <tr><td>total.jobs</td><td><span class="type-tag">number</span></td><td data-i18n="totalDescJobs">Total number of job listings currently indexed</td></tr>
            <tr><td>total.companies</td><td><span class="type-tag">number</span></td><td data-i18n="totalDescCompanies">Total number of companies currently indexed</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="successTitle">200 — Success</div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"total"</span>: {
    <span class="json-key">"jobs"</span>: <span class="json-number">18452</span>,
    <span class="json-key">"companies"</span>: <span class="json-number">934</span>
  }
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">405 — <span data-i18n="methodNotAllowedTitle">Method Not Allowed</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Only GET method allowed"</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">503 — <span data-i18n="unavailTitle">Service Unavailable</span></div>
      <div class="card-body">
        <p style="margin-bottom:0.75rem;color:#5a4a3a;font-size:0.9rem;" data-i18n="totalErrorDesc">
          Triggered when Solr is unreachable, returns an invalid response, or the environment is not configured.
          The <code>details</code> field contains the underlying error message for debugging.
        </p>
        <pre style="margin-bottom:0.75rem">{
  <span class="json-key">"error"</span>: <span class="json-string">"Job core unavailable"</span>,
  <span class="json-key">"details"</span>: <span class="json-string">"SOLR_SERVER not set"</span>
}</pre>
        <pre style="margin-bottom:0.75rem">{
  <span class="json-key">"error"</span>: <span class="json-string">"Job core unavailable"</span>,
  <span class="json-key">"details"</span>: <span class="json-string">"FETCH FAILED: http://... | Connection timed out"</span>
}</pre>
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Job core unavailable"</span>,
  <span class="json-key">"details"</span>: <span class="json-string">"Invalid JSON response"</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span data-i18n="requirementsTitle">Requirements</span> &mdash; <span data-i18n="totalEndpoint">Total</span></div>
      <div class="card-body">
        <table class="prop-table">
          <thead><tr><th style="width:100px" data-i18n="item">Item</th><th data-i18n="details">Details</th></tr></thead>
          <tbody>
            <tr><td data-i18n="method">Method</td><td><code>GET</code> only</td></tr>
            <tr><td data-i18n="auth">Auth</td><td data-i18n="authVal">None (public endpoint)</td></tr>
            <tr><td data-i18n="params">Params</td><td data-i18n="paramsVal">None</td></tr>
            <tr><td data-i18n="contentType">Content-Type</td><td><code>application/json</code></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="statusCodesTitle">Status codes</div>
      <div class="card-body">
        <ul class="status-list">
          <li><span class="status-code sc-200">200</span><span data-i18n="totalStatus200">Counts for jobs and companies returned successfully</span></li>
          <li><span class="status-code sc-405">405</span><span data-i18n="totalStatus405">Request method is not GET</span></li>
          <li><span class="status-code sc-503">503</span><span data-i18n="totalStatus503">Solr core is unreachable, returned invalid JSON, or environment is not configured</span></li>
        </ul>
      </div>
    </div>

  </div>

  <!-- ============================================= -->
  <!-- CLEANJOBS ENDPOINT -->
  <!-- ============================================= -->

  <div class="card">
    <div class="endpoint-row endpoint-row-delete" onclick="toggleEndpoint('cleanjobs')">
      <span class="method-badge method-badge-delete">DELETE</span>
      <span class="endpoint-path">/v1/cleanjobs/</span>
      <span class="endpoint-desc" data-i18n="cleanjobsTag">Delete job records for a company</span>
      <span class="toggle-arrow" id="arrow-cleanjobs">&#9654;</span>
    </div>
  </div>

  <div id="cleanjobs-content" class="endpoint-content" style="display:none">

    <div class="card">
      <div class="card-body">
        <div class="warning-banner" data-i18n="cleanjobsWarning">
          <strong>Warning:</strong> This action permanently deletes ALL job records for the specified company from the Solr database. This cannot be undone.
        </div>

        <p style="margin-bottom:1rem;color:#5a4a3a;" data-i18n="cleanjobsDesc">
          Permanently deletes every job document matching the given company from the <code>job</code> Solr core.
          Designed for company website owners who want to remove their listings from the peviitor platform.
        </p>

        <div class="section-title" data-i18n="howItWorksTitle">How it works</div>
        <ol style="margin:0 0 1.5rem 1.2rem;color:#5a4a3a;font-size:0.9rem;">
          <li data-i18n="cleanjobsHow1">Identify the company by <code>company</code> name, <code>cif</code>, or <code>brand</code></li>
          <li data-i18n="cleanjobsHow2">Compute <code>X-Api-Key</code> as MD5 hash based on the provided identifiers</li>
          <li data-i18n="cleanjobsHow3">Send a DELETE request with confirmation body</li>
          <li data-i18n="cleanjobsHow4">All matching jobs are permanently removed from the index</li>
        </ol>

        <div class="section-title" data-i18n="authTitle">Authentication</div>
        <p style="margin-bottom:1rem;color:#5a4a3a;font-size:0.9rem;" data-i18n="cleanjobsAuthDesc">
          You must provide an <code>X-Api-Key</code> header computed as <code>md5()</code> of the identifiers you send.
          The key is generated from the same fields in the request body:
        </p>
        <table class="prop-table" style="margin-bottom:1.5rem;">
          <thead><tr><th>Body fields</th><th>X-Api-Key formula</th></tr></thead>
          <tbody>
            <tr><td><code>company</code> + <code>cif</code></td><td><code>md5(company + cif)</code> <span style="color:#4a7c5a;font-size:0.8rem;">(recommended)</span></td></tr>
            <tr><td><code>company</code> only</td><td><code>md5(company)</code></td></tr>
            <tr><td><code>brand</code> only</td><td><code>md5(brand)</code></td></tr>
          </tbody>
        </table>

        <div class="section-title" data-i18n="cleanjobsBodyTitle">Request body</div>
        <pre>{
  <span class="json-key">"company"</span>: <span class="json-string">"NUME SRL"</span>,
  <span class="json-key">"cif"</span>: <span class="json-string">"12345678"</span>,
  <span class="json-key">"confirmation"</span>: <span class="json-string">"CLEAN_COMPANY_JOBS"</span>
}</pre>
        <p style="margin:0.5rem 0 0;color:#7d6b5a;font-size:0.85rem;" data-i18n="cleanjobsBodyNote">
          At least one of <code>company</code>, <code>cif</code>, or <code>brand</code> is required.
          The <code>confirmation</code> field must be exactly <code>"CLEAN_COMPANY_JOBS"</code>.
        </p>

        <div class="section-title" data-i18n="tryItTitle">Try it</div>
        <div class="curl-box">
          <div class="curl-label">curl</div>
          <pre>COMPANY="NUME SRL"
CIF="12345678"
KEY=$(echo -n "${COMPANY}${CIF}" | md5sum | cut -d' ' -f1)

curl -X DELETE "https://api.peviitor.ro/v1/cleanjobs/" \
  -H "X-Api-Key: $KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "company": "'"$COMPANY"'",
    "cif": "'"$CIF"'",
    "confirmation": "CLEAN_COMPANY_JOBS"
  }'</pre>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="cleanjobsRespTitle">Response fields</div>
      <div class="card-body">
        <table class="prop-table">
          <thead><tr><th>Field</th><th>Type</th><th data-i18n="description">Description</th></tr></thead>
          <tbody>
            <tr><td>message</td><td><span class="type-tag">string</span></td><td data-i18n="cleanjobsRespMessage">Success confirmation message</td></tr>
            <tr><td>jobCount</td><td><span class="type-tag">number</span></td><td data-i18n="cleanjobsRespJobCount">Number of jobs deleted</td></tr>
            <tr><td>company</td><td><span class="type-tag">string</span></td><td data-i18n="cleanjobsRespCompany">Company name (if provided)</td></tr>
            <tr><td>cif</td><td><span class="type-tag">string</span></td><td data-i18n="cleanjobsRespCif">Company CIF (if provided)</td></tr>
            <tr><td>brand</td><td><span class="type-tag">string</span></td><td data-i18n="cleanjobsRespBrand">Company brand (if provided)</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="successTitle">200 — Success</div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"message"</span>: <span class="json-string">"Jobs deleted successfully"</span>,
  <span class="json-key">"jobCount"</span>: <span class="json-number">42</span>,
  <span class="json-key">"company"</span>: <span class="json-string">"NUME SRL"</span>,
  <span class="json-key">"cif"</span>: <span class="json-string">"12345678"</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">401 — <span data-i18n="unauthTitle">Unauthorized</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Unauthorized - invalid X-Api-Key"</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">404 — <span data-i18n="cleanjobsNotFoundTitle">No Jobs Found</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"No jobs found"</span>,
  <span class="json-key">"message"</span>: <span class="json-string">"No jobs found matching the given criteria"</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">405 — <span data-i18n="methodNotAllowedTitle">Method Not Allowed</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Only DELETE method allowed"</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">503 — <span data-i18n="unavailTitle">Service Unavailable</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Job core unavailable"</span>,
  <span class="json-key">"details"</span>: <span class="json-string">"SOLR_SERVER not set"</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span data-i18n="requirementsTitle">Requirements</span> &mdash; <span data-i18n="cleanjobsEndpoint">Cleanjobs</span></div>
      <div class="card-body">
        <table class="prop-table">
          <thead><tr><th style="width:120px" data-i18n="item">Item</th><th data-i18n="details">Details</th></tr></thead>
          <tbody>
            <tr><td data-i18n="method">Method</td><td><code>DELETE</code> only</td></tr>
            <tr><td data-i18n="auth">Auth</td><td><code>X-Api-Key: md5(company + cif)</code> (or <code>md5(company)</code> / <code>md5(brand)</code>)</td></tr>
            <tr><td data-i18n="params">Params</td><td data-i18n="cleanjobsParamsVal">Body: <code>{"company"?: "...", "cif"?: "...", "brand"?: "...", "confirmation": "CLEAN_COMPANY_JOBS"}</code></td></tr>
            <tr><td data-i18n="contentType">Content-Type</td><td><code>application/json</code></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="statusCodesTitle">Status codes</div>
      <div class="card-body">
        <ul class="status-list">
          <li><span class="status-code sc-200">200</span><span data-i18n="cleanjobsStatus200">Jobs were deleted successfully</span></li>
          <li><span class="status-code sc-401">401</span><span data-i18n="cleanjobsStatus401">Invalid or missing X-Api-Key header</span></li>
          <li><span class="status-code sc-404">404</span><span data-i18n="cleanjobsStatus404">No jobs found matching the given criteria</span></li>
          <li><span class="status-code sc-405">405</span><span data-i18n="cleanjobsStatus405">Only DELETE method is allowed</span></li>
          <li><span class="status-code sc-503">503</span><span data-i18n="cleanjobsStatus503">Solr core is unavailable or environment not configured</span></li>
        </ul>
      </div>
    </div>

  </div>

  <!-- ============================================= -->
  <!-- COMPANY SEARCH ENDPOINT -->
  <!-- ============================================= -->

  <div class="card">
    <div class="endpoint-row" onclick="toggleEndpoint('company-search')">
      <span class="method-badge">GET</span>
      <span class="endpoint-path">/v1/firme/company/</span>
      <span class="endpoint-desc" data-i18n="companySearchTag">Search companies by CIF or name</span>
      <span class="toggle-arrow" id="arrow-company-search">&#9654;</span>
    </div>
  </div>

  <div id="company-search-content" class="endpoint-content" style="display:none">

    <div class="card">
      <div class="card-body">
        <p style="margin-bottom:1rem;color:#5a4a3a;" data-i18n="companySearchDesc">
          Searches the Solr <code>company</code> core using edismax and returns matching company documents.
          You can search by exact CIF (8-digit number) or by company name (full-text search).
        </p>

        <div class="section-title" data-i18n="howItWorksTitle">How it works</div>
        <ol style="margin:0 0 1.5rem 1.2rem;color:#5a4a3a;font-size:0.9rem;">
          <li data-i18n="companySearchHow1">Provide either <code>cif</code> (exact match) or <code>name</code> (full-text search) as a query parameter</li>
          <li data-i18n="companySearchHow2">The endpoint builds an edismax query and sends it to the Solr <code>company</code> core</li>
          <li data-i18n="companySearchHow3">Returns matching documents with fields: id, company, brand, group, status, location, website, career, lastScraped, scraperFile</li>
        </ol>

        <div class="section-title" data-i18n="queryParamsTitle">Query parameters</div>
        <table class="prop-table" style="margin-bottom:1.5rem;">
          <thead><tr><th>Param</th><th>Type</th><th data-i18n="description">Description</th></tr></thead>
          <tbody>
            <tr><td>cif</td><td><span class="type-tag">string</span></td><td data-i18n="companySearchParamCif">8-digit CIF/CUI (exact match). Non-digit characters are stripped.</td></tr>
            <tr><td>name</td><td><span class="type-tag">string</span></td><td data-i18n="companySearchParamName">Company name for full-text search via edismax</td></tr>
            <tr><td>rows</td><td><span class="type-tag">number</span></td><td data-i18n="companySearchParamRows">Max results to return (default: 10, max: 50)</td></tr>
            <tr><td>start</td><td><span class="type-tag">number</span></td><td data-i18n="companySearchParamStart">Offset for pagination (default: 0)</td></tr>
          </tbody>
        </table>
        <p style="margin:0 0 1rem;color:#7d6b5a;font-size:0.85rem;" data-i18n="companySearchParamNote">
          At least one of <code>cif</code> or <code>name</code> is required.
        </p>

        <div class="section-title" data-i18n="tryItTitle">Try it</div>
        <div class="curl-box">
          <div class="curl-label">curl</div>
          <pre>curl -X GET "https://api.peviitor.ro/v1/firme/company/?cif=24415960" \
  -H "Accept: application/json"

curl -X GET "https://api.peviitor.ro/v1/firme/company/?name=Google&rows=5" \
  -H "Accept: application/json"</pre>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="companySearchRespTitle">Response fields</div>
      <div class="card-body">
        <table class="prop-table">
          <thead><tr><th>Field</th><th>Type</th><th data-i18n="description">Description</th></tr></thead>
          <tbody>
            <tr><td>success</td><td><span class="type-tag">boolean</span></td><td data-i18n="companySearchRespSuccess">Always <code>true</code> on success</td></tr>
            <tr><td>total</td><td><span class="type-tag">number</span></td><td data-i18n="companySearchRespTotal">Total number of matching documents in Solr</td></tr>
            <tr><td>count</td><td><span class="type-tag">number</span></td><td data-i18n="companySearchRespCount">Number of documents returned in this response</td></tr>
            <tr><td>data</td><td><span class="type-tag">object[]</span></td><td data-i18n="companySearchRespData">Array of company documents</td></tr>
            <tr><td>data[].id</td><td><span class="type-tag">string</span></td><td data-i18n="companySearchRespId">CIF/CUI (8 digits)</td></tr>
            <tr><td>data[].company</td><td><span class="type-tag">string</span></td><td data-i18n="companySearchRespCompany">Company legal name</td></tr>
            <tr><td>data[].brand</td><td><span class="type-tag">string</span></td><td data-i18n="companySearchRespBrand">Brand name (if set)</td></tr>
            <tr><td>data[].group</td><td><span class="type-tag">string</span></td><td data-i18n="companySearchRespGroup">Company group (if set)</td></tr>
            <tr><td>data[].status</td><td><span class="type-tag">string</span></td><td data-i18n="companySearchRespStatus"><code>activ</code>, <code>suspendat</code>, <code>inactiv</code>, or <code>radiat</code></td></tr>
            <tr><td>data[].location</td><td><span class="type-tag">string[]</span></td><td data-i18n="companySearchRespLocation">Array of locations</td></tr>
            <tr><td>data[].website</td><td><span class="type-tag">string[]</span></td><td data-i18n="companySearchRespWebsite">Array of website URLs</td></tr>
            <tr><td>data[].career</td><td><span class="type-tag">string[]</span></td><td data-i18n="companySearchRespCareer">Array of career page URLs</td></tr>
            <tr><td>data[].lastScraped</td><td><span class="type-tag">string</span></td><td data-i18n="companySearchRespLastScraped">ISO8601 timestamp of last scrape</td></tr>
            <tr><td>data[].scraperFile</td><td><span class="type-tag">string</span></td><td data-i18n="companySearchRespScraperFile">Scraper file name</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="successTitle">200 — Success</div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"success"</span>: <span class="json-bool">true</span>,
  <span class="json-key">"total"</span>: <span class="json-number">1</span>,
  <span class="json-key">"count"</span>: <span class="json-number">1</span>,
  <span class="json-key">"data"</span>: [{
    <span class="json-key">"id"</span>: <span class="json-string">"24415960"</span>,
    <span class="json-key">"company"</span>: <span class="json-string">"GOOGLE ROMANIA SRL"</span>,
    <span class="json-key">"brand"</span>: <span class="json-string">"Google"</span>,
    <span class="json-key">"status"</span>: <span class="json-string">"activ"</span>,
    <span class="json-key">"location"</span>: [<span class="json-string">"București"</span>],
    <span class="json-key">"website"</span>: [<span class="json-string">"https://google.ro"</span>],
    <span class="json-key">"career"</span>: [<span class="json-string">"https://careers.google.com"</span>]
  }]
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">400 — <span data-i18n="badRequestTitle">Bad Request</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Missing query parameter: cif or name"</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">503 — <span data-i18n="unavailTitle">Service Unavailable</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Company search unavailable"</span>,
  <span class="json-key">"details"</span>: <span class="json-string">"SOLR_SERVER not set"</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span data-i18n="requirementsTitle">Requirements</span> &mdash; <span data-i18n="companySearchEndpoint">Company Search</span></div>
      <div class="card-body">
        <table class="prop-table">
          <thead><tr><th style="width:100px" data-i18n="item">Item</th><th data-i18n="details">Details</th></tr></thead>
          <tbody>
            <tr><td data-i18n="method">Method</td><td><code>GET</code> only</td></tr>
            <tr><td data-i18n="auth">Auth</td><td data-i18n="authVal">None (public endpoint)</td></tr>
            <tr><td data-i18n="params">Params</td><td data-i18n="companySearchParamsVal">Query: <code>cif</code> (string) or <code>name</code> (string), optional <code>rows</code> (int, max 50), <code>start</code> (int)</td></tr>
            <tr><td data-i18n="contentType">Content-Type</td><td><code>application/json</code></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="statusCodesTitle">Status codes</div>
      <div class="card-body">
        <ul class="status-list">
          <li><span class="status-code sc-200">200</span><span data-i18n="companySearchStatus200">Companies found and returned successfully</span></li>
          <li><span class="status-code sc-400">400</span><span data-i18n="companySearchStatus400">Missing required query parameter (cif or name)</span></li>
          <li><span class="status-code sc-405">405</span><span data-i18n="companySearchStatus405">Only GET method is allowed</span></li>
          <li><span class="status-code sc-503">503</span><span data-i18n="companySearchStatus503">Solr core is unavailable or environment not configured</span></li>
        </ul>
      </div>
    </div>

  </div>

  <!-- ============================================= -->
  <!-- COMPANY ADD ENDPOINT -->
  <!-- ============================================= -->

  <div class="card">
    <div class="endpoint-row" onclick="toggleEndpoint('company-add')">
      <span class="method-badge" style="background:#1565c0;box-shadow:0 2px 6px rgba(21,101,192,0.3);">PUT</span>
      <span class="endpoint-path">/v1/firme/company/add/</span>
      <span class="endpoint-desc" data-i18n="companyAddTag">Add or update a company in the Solr index</span>
      <span class="toggle-arrow" id="arrow-company-add">&#9654;</span>
    </div>
  </div>

  <div id="company-add-content" class="endpoint-content" style="display:none">

    <div class="card">
      <div class="card-body">
        <p style="margin-bottom:1rem;color:#5a4a3a;" data-i18n="companyAddDesc">
          Upserts a company document into the Solr <code>company</code> core. If a document with the same CIF (<code>id</code>) already exists, it is replaced.
          Designed for scrapers and company integrations to keep the company index up to date.
        </p>

        <div class="section-title" data-i18n="howItWorksTitle">How it works</div>
        <ol style="margin:0 0 1.5rem 1.2rem;color:#5a4a3a;font-size:0.9rem;">
          <li data-i18n="companyAddHow1">Validates required fields (<code>id</code> as 8-digit CIF, <code>company</code> name)</li>
          <li data-i18n="companyAddHow2">Sanitizes string fields with <code>htmlspecialchars</code> to prevent XSS</li>
          <li data-i18n="companyAddHow3">Normalizes array fields (<code>location</code>, <code>website</code>, <code>career</code>) — accepts string or array</li>
          <li data-i18n="companyAddHow4">Validates URLs in <code>website</code> and <code>career</code> fields</li>
          <li data-i18n="companyAddHow5">Sends the document to Solr with <code>commitWithin=1000</code> and <code>overwrite=true</code></li>
        </ol>

        <div class="section-title" data-i18n="authTitle">Authentication</div>
        <p style="margin-bottom:1rem;color:#5a4a3a;font-size:0.9rem;" data-i18n="companyAddAuthDesc">
          This endpoint uses Solr Basic Auth via environment variables <code>SOLR_USER</code> and <code>SOLR_PASS</code> configured in <code>api.env</code>.
          No client-side authentication is required.
        </p>

        <div class="section-title" data-i18n="companyAddBodyTitle">Request body (JSON)</div>
        <table class="prop-table" style="margin-bottom:1.5rem;">
          <thead><tr><th>Field</th><th>Type</th><th>Required</th><th data-i18n="description">Description</th></tr></thead>
          <tbody>
            <tr><td>id</td><td><span class="type-tag">string</span></td><td>Yes</td><td data-i18n="companyAddFieldId">8-digit CIF/CUI (e.g. <code>"24415960"</code>)</td></tr>
            <tr><td>company</td><td><span class="type-tag">string</span></td><td>Yes</td><td data-i18n="companyAddFieldCompany">Company legal name</td></tr>
            <tr><td>brand</td><td><span class="type-tag">string</span></td><td>No</td><td data-i18n="companyAddFieldBrand">Brand / trade name</td></tr>
            <tr><td>group</td><td><span class="type-tag">string</span></td><td>No</td><td data-i18n="companyAddFieldGroup">Company group / parent</td></tr>
            <tr><td>status</td><td><span class="type-tag">string</span></td><td>No</td><td data-i18n="companyAddFieldStatus">One of: <code>activ</code>, <code>suspendat</code>, <code>inactiv</code>, <code>radiat</code></td></tr>
            <tr><td>location</td><td><span class="type-tag">string|string[]</span></td><td>No</td><td data-i18n="companyAddFieldLocation">City or array of cities</td></tr>
            <tr><td>website</td><td><span class="type-tag">string|string[]</span></td><td>No</td><td data-i18n="companyAddFieldWebsite">Website URL(s) — must be valid URLs</td></tr>
            <tr><td>career</td><td><span class="type-tag">string|string[]</span></td><td>No</td><td data-i18n="companyAddFieldCareer">Career page URL(s) — must be valid URLs</td></tr>
            <tr><td>lastScraped</td><td><span class="type-tag">string</span></td><td>No</td><td data-i18n="companyAddFieldLastScraped">ISO8601 timestamp of last scrape</td></tr>
            <tr><td>scraperFile</td><td><span class="type-tag">string</span></td><td>No</td><td data-i18n="companyAddFieldScraperFile">Name of the scraper file</td></tr>
          </tbody>
        </table>

        <div class="section-title" data-i18n="tryItTitle">Try it</div>
        <div class="curl-box">
          <div class="curl-label">curl</div>
          <pre>curl -X PUT "https://api.peviitor.ro/v1/firme/company/add/" \
  -H "Content-Type: application/json" \
  -d '{
    "id": "24415960",
    "company": "GOOGLE ROMANIA SRL",
    "brand": "Google",
    "status": "activ",
    "location": ["București"],
    "website": ["https://google.ro"],
    "career": ["https://careers.google.com"]
  }'</pre>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="companyAddRespTitle">Response fields</div>
      <div class="card-body">
        <table class="prop-table">
          <thead><tr><th>Field</th><th>Type</th><th data-i18n="description">Description</th></tr></thead>
          <tbody>
            <tr><td>success</td><td><span class="type-tag">boolean</span></td><td data-i18n="companyAddRespSuccess">Always <code>true</code> on success</td></tr>
            <tr><td>id</td><td><span class="type-tag">string</span></td><td data-i18n="companyAddRespId">The CIF of the upserted company</td></tr>
            <tr><td>message</td><td><span class="type-tag">string</span></td><td data-i18n="companyAddRespMessage">Confirmation message</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="successTitle">200 — Success</div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"success"</span>: <span class="json-bool">true</span>,
  <span class="json-key">"id"</span>: <span class="json-string">"24415960"</span>,
  <span class="json-key">"message"</span>: <span class="json-string">"Company 'GOOGLE ROMANIA SRL' (24415960) upserted to company core"</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">400 — <span data-i18n="badRequestTitle">Bad Request</span></div>
      <div class="card-body">
        <p style="margin-bottom:0.75rem;color:#5a4a3a;font-size:0.9rem;" data-i18n="companyAdd400Desc">
          Returned when the request body is invalid or required fields are missing/invalid.
        </p>
        <pre style="margin-bottom:0.75rem">{
  <span class="json-key">"error"</span>: <span class="json-string">"Missing required fields: id, company"</span>,
  <span class="json-key">"code"</span>: <span class="json-number">400</span>
}</pre>
        <pre style="margin-bottom:0.75rem">{
  <span class="json-key">"error"</span>: <span class="json-string">"Field 'id' must be an 8-digit CIF/CUI string (e.g. '24415960')"</span>,
  <span class="json-key">"code"</span>: <span class="json-number">400</span>
}</pre>
        <pre style="margin-bottom:0.75rem">{
  <span class="json-key">"error"</span>: <span class="json-string">"Field 'status' must be one of: activ, suspendat, inactiv, radiat"</span>,
  <span class="json-key">"code"</span>: <span class="json-number">400</span>
}</pre>
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Invalid website URL: not-a-url"</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">405 — <span data-i18n="methodNotAllowedTitle">Method Not Allowed</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Only PUT method allowed"</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">503 — <span data-i18n="unavailTitle">Service Unavailable</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Company core unavailable"</span>,
  <span class="json-key">"details"</span>: <span class="json-string">"FETCH FAILED: http://... | Connection timed out"</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span data-i18n="requirementsTitle">Requirements</span> &mdash; <span data-i18n="companyAddEndpoint">Company Add</span></div>
      <div class="card-body">
        <table class="prop-table">
          <thead><tr><th style="width:100px" data-i18n="item">Item</th><th data-i18n="details">Details</th></tr></thead>
          <tbody>
            <tr><td data-i18n="method">Method</td><td><code>PUT</code> only</td></tr>
            <tr><td data-i18n="auth">Auth</td><td data-i18n="companyAddAuthVal">Solr Basic Auth (server-side, via <code>api.env</code>)</td></tr>
            <tr><td data-i18n="params">Params</td><td data-i18n="companyAddParamsVal">Body: <code>{"id": "...", "company": "...", ...}</code></td></tr>
            <tr><td data-i18n="contentType">Content-Type</td><td><code>application/json</code></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="statusCodesTitle">Status codes</div>
      <div class="card-body">
        <ul class="status-list">
          <li><span class="status-code sc-200">200</span><span data-i18n="companyAddStatus200">Company was upserted successfully</span></li>
          <li><span class="status-code sc-400">400</span><span data-i18n="companyAddStatus400">Invalid body, missing required fields, or validation failed</span></li>
          <li><span class="status-code sc-405">405</span><span data-i18n="companyAddStatus405">Only PUT method is allowed</span></li>
          <li><span class="status-code sc-503">503</span><span data-i18n="companyAddStatus503">Solr core is unavailable or environment not configured</span></li>
        </ul>
      </div>
    </div>

  </div>

  <!-- ============================================= -->
  <!-- SCRAPER JOBS UPLOAD ENDPOINT -->
  <!-- ============================================= -->

  <div class="card">
    <div class="endpoint-row" onclick="toggleEndpoint('scraper-jobs-upload')">
      <span class="method-badge" style="background:#e65100;box-shadow:0 2px 6px rgba(230,81,0,0.3);">POST</span>
      <span class="endpoint-path">/v1/scraper/jobs/upload/</span>
      <span class="endpoint-desc" data-i18n="scraperUploadTag">Upload jobs from scrapers to Solr</span>
      <span class="toggle-arrow" id="arrow-scraper-jobs-upload">&#9654;</span>
    </div>
  </div>

  <div id="scraper-jobs-upload-content" class="endpoint-content" style="display:none">

    <div class="card">
      <div class="card-body">
        <p style="margin-bottom:1rem;color:#5a4a3a;" data-i18n="scraperUploadDesc">
          Accepts a JSON array of job listings and upserts them directly into the Solr <code>job</code> core.
          Designed for automated scrapers that need to upload jobs in bulk.
          Automatically fixes diacritics in city names and lowercases tags.
          Existing jobs (same <code>url</code>) are overwritten.
        </p>

        <div class="section-title" data-i18n="howItWorksTitle">How it works</div>
        <ol style="margin:0 0 1.5rem 1.2rem;color:#5a4a3a;font-size:0.9rem;">
          <li data-i18n="scraperUploadHow1">Receives a JSON array of job objects (or <code>{"jobs": [...]}</code>)</li>
          <li data-i18n="scraperUploadHow2">Skips entries missing required fields (<code>url</code>, <code>title</code>, <code>company</code>)</li>
          <li data-i18n="scraperUploadHow3">Normalizes <code>location</code> (fixes diacritics, accepts string or array)</li>
          <li data-i18n="scraperUploadHow4">Lowercases <code>tags</code> and filters empty values</li>
          <li data-i18n="scraperUploadHow5">Sends to Solr with <code>commitWithin=1000</code> and <code>overwrite=true</code></li>
        </ol>

        <div class="section-title" data-i18n="authTitle">Authentication</div>
        <p style="margin-bottom:1rem;color:#5a4a3a;font-size:0.9rem;" data-i18n="scraperUploadAuthDesc">
          This endpoint uses Solr Basic Auth via environment variables <code>SOLR_USER</code> and <code>SOLR_PASS</code> configured in <code>api.env</code>.
          No client-side authentication is required.
        </p>

        <div class="section-title" data-i18n="scraperUploadBodyTitle">Request body (JSON)</div>
        <p style="margin-bottom:0.75rem;color:#7d6b5a;font-size:0.85rem;" data-i18n="scraperUploadBodyNote">
          Accepts either a plain array <code>[{...}, ...]</code> or an object <code>{"jobs": [{...}, ...]}</code>.
        </p>
        <table class="prop-table" style="margin-bottom:1.5rem;">
          <thead><tr><th>Field</th><th>Type</th><th>Required</th><th data-i18n="description">Description</th></tr></thead>
          <tbody>
            <tr><td>url</td><td><span class="type-tag">string</span></td><td>Yes</td><td data-i18n="scraperUploadFieldUrl">Full URL to the job detail page (unique key)</td></tr>
            <tr><td>title</td><td><span class="type-tag">string</span></td><td>Yes</td><td data-i18n="scraperUploadFieldTitle">Exact job position title</td></tr>
            <tr><td>company</td><td><span class="type-tag">string</span></td><td>Yes</td><td data-i18n="scraperUploadFieldCompany">Hiring company name</td></tr>
            <tr><td>cif</td><td><span class="type-tag">string</span></td><td>No</td><td data-i18n="scraperUploadFieldCif">CIF/CUI of the company</td></tr>
            <tr><td>location</td><td><span class="type-tag">string|string[]</span></td><td>No</td><td data-i18n="scraperUploadFieldLocation">City or array of cities (diacritics auto-fixed)</td></tr>
            <tr><td>tags</td><td><span class="type-tag">string[]</span></td><td>No</td><td data-i18n="scraperUploadFieldTags">Skill tags (auto-lowercased)</td></tr>
            <tr><td>workmode</td><td><span class="type-tag">string</span></td><td>No</td><td data-i18n="scraperUploadFieldWorkmode"><code>remote</code>, <code>on-site</code>, or <code>hybrid</code></td></tr>
            <tr><td>date</td><td><span class="type-tag">string</span></td><td>No</td><td data-i18n="scraperUploadFieldDate">ISO8601 UTC timestamp</td></tr>
            <tr><td>status</td><td><span class="type-tag">string</span></td><td>No</td><td data-i18n="scraperUploadFieldStatus"><code>scraped</code>, <code>tested</code>, <code>published</code>, or <code>verified</code></td></tr>
          </tbody>
        </table>

        <div class="section-title" data-i18n="tryItTitle">Try it</div>
        <div class="curl-box">
          <div class="curl-label">curl</div>
          <pre>curl -X POST "https://api.peviitor.ro/v1/scraper/jobs/upload/" \
  -H "Content-Type: application/json" \
  -d '[
    {
      "url": "https://example.com/job/123",
      "title": "Inginer IT",
      "company": "COMPANY SRL",
      "cif": "12345678",
      "location": ["București", "Cluj-Napoca"],
      "tags": ["javascript", "react"],
      "workmode": "remote",
      "date": "2026-07-24T10:00:00Z",
      "status": "scraped"
    }
  ]'</pre>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="scraperUploadRespTitle">Response fields</div>
      <div class="card-body">
        <table class="prop-table">
          <thead><tr><th>Field</th><th>Type</th><th data-i18n="description">Description</th></tr></thead>
          <tbody>
            <tr><td>success</td><td><span class="type-tag">string</span></td><td data-i18n="scraperUploadRespSuccess">Confirmation message</td></tr>
            <tr><td>count</td><td><span class="type-tag">number</span></td><td data-i18n="scraperUploadRespCount">Number of jobs sent to Solr</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="successTitle">200 — Success</div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"success"</span>: <span class="json-string">"Jobs successfully uploaded to Solr"</span>,
  <span class="json-key">"count"</span>: <span class="json-number">29</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">400 — <span data-i18n="badRequestTitle">Bad Request</span></div>
      <div class="card-body">
        <pre style="margin-bottom:0.75rem">{
  <span class="json-key">"error"</span>: <span class="json-string">"Payload must be a non-empty JSON array of jobs"</span>,
  <span class="json-key">"code"</span>: <span class="json-number">400</span>
}</pre>
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"No valid jobs found in payload (url, title, company required)"</span>,
  <span class="json-key">"code"</span>: <span class="json-number">400</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">405 — <span data-i18n="methodNotAllowedTitle">Method Not Allowed</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Only POST method is allowed"</span>,
  <span class="json-key">"code"</span>: <span class="json-number">405</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">415 — <span data-i18n="unsupportedMediaTypeTitle">Unsupported Media Type</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Content-Type must be application/json"</span>,
  <span class="json-key">"code"</span>: <span class="json-number">415</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">503 — <span data-i18n="unavailTitle">Service Unavailable</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Job core unavailable"</span>,
  <span class="json-key">"details"</span>: <span class="json-string">"SOLR_SERVER not set"</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span data-i18n="requirementsTitle">Requirements</span> &mdash; <span data-i18n="scraperUploadEndpoint">Scraper Jobs Upload</span></div>
      <div class="card-body">
        <table class="prop-table">
          <thead><tr><th style="width:100px" data-i18n="item">Item</th><th data-i18n="details">Details</th></tr></thead>
          <tbody>
            <tr><td data-i18n="method">Method</td><td><code>POST</code> only</td></tr>
            <tr><td data-i18n="auth">Auth</td><td data-i18n="scraperUploadAuthVal">Solr Basic Auth (server-side, via <code>api.env</code>)</td></tr>
            <tr><td data-i18n="params">Params</td><td data-i18n="scraperUploadParamsVal">Body: <code>[{"url": "...", "title": "...", "company": "...", ...}]</code></td></tr>
            <tr><td data-i18n="contentType">Content-Type</td><td><code>application/json</code></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="statusCodesTitle">Status codes</div>
      <div class="card-body">
        <ul class="status-list">
          <li><span class="status-code sc-200">200</span><span data-i18n="scraperUploadStatus200">Jobs were uploaded to Solr successfully</span></li>
          <li><span class="status-code sc-400">400</span><span data-i18n="scraperUploadStatus400">Empty payload, missing required fields, or invalid JSON</span></li>
          <li><span class="status-code sc-405">405</span><span data-i18n="scraperUploadStatus405">Only POST method is allowed</span></li>
          <li><span class="status-code sc-415">415</span><span data-i18n="scraperUploadStatus415">Content-Type is not application/json</span></li>
          <li><span class="status-code sc-503">503</span><span data-i18n="scraperUploadStatus503">Solr core is unavailable or environment not configured</span></li>
        </ul>
      </div>
    </div>

  </div>

  <!-- ============================================= -->
  <!-- SCRAPER JOBS QUERY ENDPOINT -->
  <!-- ============================================= -->

  <div class="card">
    <div class="endpoint-row" onclick="toggleEndpoint('scraper-jobs-query')">
      <span class="method-badge">GET</span>
      <span class="endpoint-path">/v1/scraper/jobs/</span>
      <span class="endpoint-desc" data-i18n="scraperQueryTag">Query jobs by company CIF</span>
      <span class="toggle-arrow" id="arrow-scraper-jobs-query">&#9654;</span>
    </div>
  </div>

  <div id="scraper-jobs-query-content" class="endpoint-content" style="display:none">

    <div class="card">
      <div class="card-body">
        <p style="margin-bottom:1rem;color:#5a4a3a;" data-i18n="scraperQueryDesc">
          Queries the Solr <code>job</code> core for all jobs matching a company CIF.
          Returns paginated results with standard job fields.
          Designed for scrapers that need to read existing jobs before re-scraping.
        </p>

        <div class="section-title" data-i18n="howItWorksTitle">How it works</div>
        <ol style="margin:0 0 1.5rem 1.2rem;color:#5a4a3a;font-size:0.9rem;">
          <li data-i18n="scraperQueryHow1">Validates the <code>cif</code> parameter (must be exactly 8 digits)</li>
          <li data-i18n="scraperQueryHow2">Escapes special Solr characters to prevent query injection</li>
          <li data-i18n="scraperQueryHow3">Queries the Solr <code>job</code> core with pagination support</li>
          <li data-i18n="scraperQueryHow4">Returns matching documents with standard job fields</li>
        </ol>

        <div class="section-title" data-i18n="queryParamsTitle">Query parameters</div>
        <table class="prop-table" style="margin-bottom:1.5rem;">
          <thead><tr><th>Param</th><th>Type</th><th>Required</th><th data-i18n="description">Description</th></tr></thead>
          <tbody>
            <tr><td>cif</td><td><span class="type-tag">string</span></td><td>Yes</td><td data-i18n="scraperQueryParamCif">8-digit CIF/CUI (exact match)</td></tr>
            <tr><td>rows</td><td><span class="type-tag">number</span></td><td>No</td><td data-i18n="scraperQueryParamRows">Max results (default: 100, max: 500)</td></tr>
            <tr><td>start</td><td><span class="type-tag">number</span></td><td>No</td><td data-i18n="scraperQueryParamStart">Offset for pagination (default: 0)</td></tr>
          </tbody>
        </table>

        <div class="section-title" data-i18n="tryItTitle">Try it</div>
        <div class="curl-box">
          <div class="curl-label">curl</div>
          <pre>curl -X GET "https://api.peviitor.ro/v1/scraper/jobs/?cif=24415960" \
  -H "Accept: application/json"

curl -X GET "https://api.peviitor.ro/v1/scraper/jobs/?cif=24415960&rows=10&start=0" \
  -H "Accept: application/json"</pre>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="scraperQueryRespTitle">Response fields</div>
      <div class="card-body">
        <table class="prop-table">
          <thead><tr><th>Field</th><th>Type</th><th data-i18n="description">Description</th></tr></thead>
          <tbody>
            <tr><td>success</td><td><span class="type-tag">boolean</span></td><td data-i18n="scraperQueryRespSuccess">Always <code>true</code> on success</td></tr>
            <tr><td>total</td><td><span class="type-tag">number</span></td><td data-i18n="scraperQueryRespTotal">Total number of matching jobs in Solr</td></tr>
            <tr><td>count</td><td><span class="type-tag">number</span></td><td data-i18n="scraperQueryRespCount">Number of jobs returned in this response</td></tr>
            <tr><td>data</td><td><span class="type-tag">object[]</span></td><td data-i18n="scraperQueryRespData">Array of job documents</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="successTitle">200 — Success</div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"success"</span>: <span class="json-bool">true</span>,
  <span class="json-key">"total"</span>: <span class="json-number">32</span>,
  <span class="json-key">"count"</span>: <span class="json-number">32</span>,
  <span class="json-key">"data"</span>: [
    {
      <span class="json-key">"url"</span>: <span class="json-string">"https://example.com/job/123"</span>,
      <span class="json-key">"title"</span>: <span class="json-string">"Senior Developer"</span>,
      <span class="json-key">"company"</span>: <span class="json-string">"COMPANY SRL"</span>,
      <span class="json-key">"cif"</span>: <span class="json-string">"24415960"</span>,
      <span class="json-key">"location"</span>: [<span class="json-string">"București"</span>],
      <span class="json-key">"tags"</span>: [<span class="json-string">"javascript"</span>],
      <span class="json-key">"workmode"</span>: <span class="json-string">"remote"</span>,
      <span class="json-key">"date"</span>: <span class="json-string">"2026-07-26T10:00:00Z"</span>,
      <span class="json-key">"status"</span>: <span class="json-string">"scraped"</span>
    }
  ]
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">400 — <span data-i18n="badRequestTitle">Bad Request</span></div>
      <div class="card-body">
        <pre style="margin-bottom:0.75rem">{
  <span class="json-key">"error"</span>: <span class="json-string">"Missing required query parameter: cif"</span>,
  <span class="json-key">"code"</span>: <span class="json-number">400</span>
}</pre>
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"CIF must be exactly 8 digits"</span>,
  <span class="json-key">"code"</span>: <span class="json-number">400</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">405 — <span data-i18n="methodNotAllowedTitle">Method Not Allowed</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Only GET method is allowed"</span>,
  <span class="json-key">"code"</span>: <span class="json-number">405</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">503 — <span data-i18n="unavailTitle">Service Unavailable</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Job core unavailable"</span>,
  <span class="json-key">"details"</span>: <span class="json-string">"SOLR_SERVER not set"</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span data-i18n="requirementsTitle">Requirements</span> &mdash; <span data-i18n="scraperQueryEndpoint">Scraper Jobs Query</span></div>
      <div class="card-body">
        <table class="prop-table">
          <thead><tr><th style="width:100px" data-i18n="item">Item</th><th data-i18n="details">Details</th></tr></thead>
          <tbody>
            <tr><td data-i18n="method">Method</td><td><code>GET</code> only</td></tr>
            <tr><td data-i18n="auth">Auth</td><td data-i18n="scraperQueryAuthVal">Solr Basic Auth (server-side, via <code>api.env</code>)</td></tr>
            <tr><td data-i18n="params">Params</td><td data-i18n="scraperQueryParamsVal">Query: <code>cif</code> (string, required), optional <code>rows</code> (int, max 500), <code>start</code> (int)</td></tr>
            <tr><td data-i18n="contentType">Content-Type</td><td><code>application/json</code></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="statusCodesTitle">Status codes</div>
      <div class="card-body">
        <ul class="status-list">
          <li><span class="status-code sc-200">200</span><span data-i18n="scraperQueryStatus200">Jobs found and returned successfully</span></li>
          <li><span class="status-code sc-400">400</span><span data-i18n="scraperQueryStatus400">Missing or invalid CIF parameter</span></li>
          <li><span class="status-code sc-405">405</span><span data-i18n="scraperQueryStatus405">Only GET method is allowed</span></li>
          <li><span class="status-code sc-503">503</span><span data-i18n="scraperQueryStatus503">Solr core is unavailable or environment not configured</span></li>
        </ul>
      </div>
    </div>

  </div>

  <!-- ============================================= -->
  <!-- SCRAPER JOBS DELETE ENDPOINT -->
  <!-- ============================================= -->

  <div class="card">
    <div class="endpoint-row endpoint-row-delete" onclick="toggleEndpoint('scraper-jobs-delete')">
      <span class="method-badge method-badge-delete">DELETE</span>
      <span class="endpoint-path">/v1/scraper/jobs/delete/</span>
      <span class="endpoint-desc" data-i18n="scraperDeleteTag">Delete jobs by CIF or URL</span>
      <span class="toggle-arrow" id="arrow-scraper-jobs-delete">&#9654;</span>
    </div>
  </div>

  <div id="scraper-jobs-delete-content" class="endpoint-content" style="display:none">

    <div class="card">
      <div class="card-body">
        <div class="warning-banner" data-i18n="scraperDeleteWarning">
          <strong>Warning:</strong> This action permanently deletes matching job records from the Solr database. This cannot be undone.
        </div>

        <p style="margin-bottom:1rem;color:#5a4a3a;" data-i18n="scraperDeleteDesc">
          Deletes jobs from the Solr <code>job</code> core by company CIF (all jobs) or by URL (single job).
          Designed for internal scraper use — when a company becomes inactive in ANAF or when expired job URLs are cleaned up.
        </p>

        <div class="section-title" data-i18n="howItWorksTitle">How it works</div>
        <ol style="margin:0 0 1.5rem 1.2rem;color:#5a4a3a;font-size:0.9rem;">
          <li data-i18n="scraperDeleteHow1">Accepts a JSON body with <code>cif</code> or <code>url</code></li>
          <li data-i18n="scraperDeleteHow2">Counts matching documents before deletion</li>
          <li data-i18n="scraperDeleteHow3">Returns 404 if no matching jobs found</li>
          <li data-i18n="scraperDeleteHow4">Deletes all matching jobs with <code>commit=true</code></li>
        </ol>

        <div class="section-title" data-i18n="scraperDeleteBodyTitle">Request body (JSON)</div>
        <table class="prop-table" style="margin-bottom:1.5rem;">
          <thead><tr><th>Field</th><th>Type</th><th>Required</th><th data-i18n="description">Description</th></tr></thead>
          <tbody>
            <tr><td>cif</td><td><span class="type-tag">string</span></td><td>Conditional</td><td data-i18n="scraperDeleteFieldCif">Delete all jobs for this 8-digit CIF</td></tr>
            <tr><td>url</td><td><span class="type-tag">string</span></td><td>Conditional</td><td data-i18n="scraperDeleteFieldUrl">Delete a single job by its URL</td></tr>
          </tbody>
        </table>
        <p style="margin:0 0 1rem;color:#7d6b5a;font-size:0.85rem;" data-i18n="scraperDeleteBodyNote">
          Exactly one of <code>cif</code> or <code>url</code> is required. Providing both returns an error.
        </p>

        <div class="section-title" data-i18n="tryItTitle">Try it</div>
        <div class="curl-box">
          <div class="curl-label">curl</div>
          <pre>curl -X DELETE "https://api.peviitor.ro/v1/scraper/jobs/delete/" \
  -H "Content-Type: application/json" \
  -d '{"cif": "24415960"}'

curl -X DELETE "https://api.peviitor.ro/v1/scraper/jobs/delete/" \
  -H "Content-Type: application/json" \
  -d '{"url": "https://example.com/job/123"}'</pre>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="scraperDeleteRespTitle">Response fields</div>
      <div class="card-body">
        <table class="prop-table">
          <thead><tr><th>Field</th><th>Type</th><th data-i18n="description">Description</th></tr></thead>
          <tbody>
            <tr><td>success</td><td><span class="type-tag">boolean</span></td><td data-i18n="scraperDeleteRespSuccess">Always <code>true</code> on success</td></tr>
            <tr><td>message</td><td><span class="type-tag">string</span></td><td data-i18n="scraperDeleteRespMessage">Confirmation message</td></tr>
            <tr><td>count</td><td><span class="type-tag">number</span></td><td data-i18n="scraperDeleteRespCount">Number of jobs deleted</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="successTitle">200 — Success</div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"success"</span>: <span class="json-bool">true</span>,
  <span class="json-key">"message"</span>: <span class="json-string">"Jobs deleted successfully"</span>,
  <span class="json-key">"count"</span>: <span class="json-number">32</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">400 — <span data-i18n="badRequestTitle">Bad Request</span></div>
      <div class="card-body">
        <pre style="margin-bottom:0.75rem">{
  <span class="json-key">"error"</span>: <span class="json-string">"Request body must be a JSON object, not an array"</span>,
  <span class="json-key">"code"</span>: <span class="json-number">400</span>
}</pre>
        <pre style="margin-bottom:0.75rem">{
  <span class="json-key">"error"</span>: <span class="json-string">"At least one of 'cif' or 'url' is required"</span>,
  <span class="json-key">"code"</span>: <span class="json-number">400</span>
}</pre>
        <pre style="margin-bottom:0.75rem">{
  <span class="json-key">"error"</span>: <span class="json-string">"Provide only 'cif' or 'url', not both"</span>,
  <span class="json-key">"code"</span>: <span class="json-number">400</span>
}</pre>
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"CIF must be exactly 8 digits"</span>,
  <span class="json-key">"code"</span>: <span class="json-number">400</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">404 — <span data-i18n="notFoundTitle">No Jobs Found</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"No jobs found"</span>,
  <span class="json-key">"message"</span>: <span class="json-string">"No jobs found matching cif:24415960"</span>,
  <span class="json-key">"count"</span>: <span class="json-number">0</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">405 — <span data-i18n="methodNotAllowedTitle">Method Not Allowed</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Only DELETE method is allowed"</span>,
  <span class="json-key">"code"</span>: <span class="json-number">405</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">415 — <span data-i18n="unsupportedMediaTypeTitle">Unsupported Media Type</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Content-Type must be application/json"</span>,
  <span class="json-key">"code"</span>: <span class="json-number">415</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header">503 — <span data-i18n="unavailTitle">Service Unavailable</span></div>
      <div class="card-body">
        <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Job core unavailable"</span>,
  <span class="json-key">"details"</span>: <span class="json-string">"SOLR_SERVER not set"</span>
}</pre>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span data-i18n="requirementsTitle">Requirements</span> &mdash; <span data-i18n="scraperDeleteEndpoint">Scraper Jobs Delete</span></div>
      <div class="card-body">
        <table class="prop-table">
          <thead><tr><th style="width:100px" data-i18n="item">Item</th><th data-i18n="details">Details</th></tr></thead>
          <tbody>
            <tr><td data-i18n="method">Method</td><td><code>DELETE</code> only</td></tr>
            <tr><td data-i18n="auth">Auth</td><td data-i18n="scraperDeleteAuthVal">Solr Basic Auth (server-side, via <code>api.env</code>)</td></tr>
            <tr><td data-i18n="params">Params</td><td data-i18n="scraperDeleteParamsVal">Body: <code>{"cif": "..."}</code> or <code>{"url": "..."}</code></td></tr>
            <tr><td data-i18n="contentType">Content-Type</td><td><code>application/json</code></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header" data-i18n="statusCodesTitle">Status codes</div>
      <div class="card-body">
        <ul class="status-list">
          <li><span class="status-code sc-200">200</span><span data-i18n="scraperDeleteStatus200">Jobs were deleted successfully</span></li>
          <li><span class="status-code sc-400">400</span><span data-i18n="scraperDeleteStatus400">Missing required fields, invalid CIF, or invalid JSON</span></li>
          <li><span class="status-code sc-404">404</span><span data-i18n="scraperDeleteStatus404">No jobs found matching the criteria</span></li>
          <li><span class="status-code sc-405">405</span><span data-i18n="scraperDeleteStatus405">Only DELETE method is allowed</span></li>
          <li><span class="status-code sc-415">415</span><span data-i18n="scraperDeleteStatus415">Content-Type is not application/json</span></li>
          <li><span class="status-code sc-503">503</span><span data-i18n="scraperDeleteStatus503">Solr core is unavailable or environment not configured</span></li>
        </ul>
      </div>
    </div>

  </div>

  <h2 data-i18n="statusTitle">Stare curentă a API-ului</h2>
  <p class="section-desc" data-i18n="statusDesc">Lucrăm la:</p>
  <ul class="future-list">
    <li data-i18n="statusItem1">documentarea completă a API-ului pe baza unei specificații publice (de tip OpenAPI/Swagger)</li>
    <li data-i18n="statusItem2">introducerea unui mecanism de validare a domeniilor (prin înregistrări DNS TXT) pentru a lega cheile API de website-ul companiei</li>
    <li data-i18n="statusItem3">definirea unor endpoint-uri dedicate pentru gestionarea joburilor (creare, actualizare, închidere) într-un mod standardizat</li>
  </ul>
  <p class="section-desc" data-i18n="statusFooter">Pe măsură ce noile endpoint-uri vor deveni disponibile, le vom publica aici împreună cu exemple de request/response și recomandări de integrare.</p>

  <h2 data-i18n="integrationTitle">Cum vei putea integra website-ul tău (în curând)</h2>
  <p class="section-desc" data-i18n="integrationDesc">
    Obiectivul nostru este să oferim un mod simplu și automat prin care website-urile de companii să trimită joburi către peviitor.ro prin API.
    Designul urmărit este:
  </p>
  <ul class="future-list">
    <li data-i18n="integrationItem1">cheile de API vor fi asociate unui domeniu de website (de exemplu, <code>exemplu.com</code>)</li>
    <li data-i18n="integrationItem2">domeniul va putea fi dovedit printr-o înregistrare DNS TXT, astfel încât doar proprietarul domeniului să poată publica joburi în numele acelei companii</li>
    <li data-i18n="integrationItem3">endpoint-uri dedicate pentru creare, actualizare și închidere joburi vor fi documentate public, cu exemple clare de integrare</li>
  </ul>
  <p class="section-desc" data-i18n="integrationFooter">Pe măsură ce aceste componente vor fi gata, le vom documenta pe această pagină.</p>

  <footer>
    Powered by <a href="https://peviitor.ro" target="_blank">peviitor.ro</a> &middot;
    <a href="https://github.com/peviitor-ro/api" target="_blank">GitHub</a>
  </footer>

</div>

<script>
const i18n = {
  en: {
    brand: "peviitor API",
    subtitle: "Job discovery platform \u2014 public API documentation",
    endpointTag: "Get a random job listing",
    endpointDesc: "Returns a single random job from the Solr job index. Useful for discovery widgets, \u201Cjob of the day\u201D features, or testing integrations.",
    howItWorksTitle: "How it works",
    how1: "Queries Solr for the total number of indexed jobs",
    how2: "Picks a random offset between 0 and total count",
    how3: "Fetches exactly one document at that offset",
    how4: "Returns the job mapped to the peviitor Job Model Schema",
    tryItTitle: "Try it",
    respFieldsTitle: "Response fields",
    description: "Description",
    descTitle: "Exact job position title",
    descCompany: "Hiring company name (uppercase)",
    descLocation: "Array of cities or addresses",
    descWorkmode: "<code>remote</code>, <code>on-site</code>, or <code>hybrid</code>",
    descUrl: "Full URL to the job detail page (unique key)",
    descSalary: "Salary interval with currency, e.g. <code>5000-8000 RON</code>",
    descTags: "Skill tags (lowercase, max 20)",
    descCif: "CIF/CUI of the company",
    descDate: "ISO8601 UTC timestamp of indexing",
    descStatus: "<code>scraped</code>, <code>tested</code>, <code>published</code>, or <code>verified</code>",
    successTitle: "200 \u2014 Success",
    notFoundTitle: "No Jobs Found",
    unavailTitle: "Service Unavailable",
    statusCodesTitle: "Status codes",
    status200: "A random job was found and returned successfully",
    status404: "No jobs are currently indexed in the Solr core",
    status503: "Solr core is unavailable or environment not configured",
    requirementsTitle: "Requirements",
    item: "Item",
    details: "Details",
    method: "Method",
    auth: "Auth",
    authVal: "None (public endpoint)",
    params: "Params",
    paramsVal: "None",
    contentType: "Content-Type",

    authTitle: "Authentication",
    methodNotAllowedTitle: "Method Not Allowed",
    randomEndpoint: "Random endpoint",

    cleanjobsTag: "Delete job records for a company",
    cleanjobsWarning: "<strong>Warning:</strong> This action permanently deletes ALL job records for the specified company from the Solr database. This cannot be undone.",
    cleanjobsDesc: "Permanently deletes every job document matching the given company from the <code>job</code> Solr core. Designed for company website owners who want to remove their listings from the peviitor platform.",
    cleanjobsHow1: "Identify the company by <code>company</code> name, <code>cif</code>, or <code>brand</code>",
    cleanjobsHow2: "Compute <code>X-Api-Key</code> as MD5 hash based on the provided identifiers",
    cleanjobsHow3: "Send a DELETE request with confirmation body",
    cleanjobsHow4: "All matching jobs are permanently removed from the index",
    cleanjobsAuthDesc: "You must provide an <code>X-Api-Key</code> header computed as <code>md5()</code> of the identifiers you send. The key is generated from the same fields in the request body:",
    cleanjobsBodyTitle: "Request body",
    cleanjobsBodyNote: "At least one of <code>company</code>, <code>cif</code>, or <code>brand</code> is required. The <code>confirmation</code> field must be exactly <code>\"CLEAN_COMPANY_JOBS\"</code>.",
    cleanjobsRespTitle: "Response fields",
    cleanjobsRespMessage: "Success confirmation message",
    cleanjobsRespJobCount: "Number of jobs deleted",
    cleanjobsRespCompany: "Company name (if provided)",
    cleanjobsRespCif: "Company CIF (if provided)",
    cleanjobsRespBrand: "Company brand (if provided)",
    cleanjobsNotFoundTitle: "No Jobs Found",
    cleanjobsEndpoint: "Cleanjobs",
    cleanjobsParamsVal: "Body: <code>{\"company\"?: \"...\", \"cif\"?: \"...\", \"brand\"?: \"...\", \"confirmation\": \"CLEAN_COMPANY_JOBS\"}</code>",
    cleanjobsStatus200: "Jobs were deleted successfully",
    cleanjobsStatus401: "Invalid or missing X-Api-Key header",
    cleanjobsStatus404: "No jobs found matching the given criteria",
    cleanjobsStatus405: "Only DELETE method is allowed",
    cleanjobsStatus503: "Solr core is unavailable or environment not configured",
    unauthTitle: "Unauthorized",

    companySearchTag: "Search companies by CIF or name",
    companySearchDesc: "Searches the Solr <code>company</code> core using edismax and returns matching company documents. You can search by exact CIF (8-digit number) or by company name (full-text search).",
    companySearchHow1: "Provide either <code>cif</code> (exact match) or <code>name</code> (full-text search) as a query parameter",
    companySearchHow2: "The endpoint builds an edismax query and sends it to the Solr <code>company</code> core",
    companySearchHow3: "Returns matching documents with fields: id, company, brand, group, status, location, website, career, lastScraped, scraperFile",
    queryParamsTitle: "Query parameters",
    companySearchParamCif: "8-digit CIF/CUI (exact match). Non-digit characters are stripped.",
    companySearchParamName: "Company name for full-text search via edismax",
    companySearchParamRows: "Max results to return (default: 10, max: 50)",
    companySearchParamStart: "Offset for pagination (default: 0)",
    companySearchParamNote: "At least one of <code>cif</code> or <code>name</code> is required.",
    companySearchRespTitle: "Response fields",
    companySearchRespSuccess: "Always <code>true</code> on success",
    companySearchRespTotal: "Total number of matching documents in Solr",
    companySearchRespCount: "Number of documents returned in this response",
    companySearchRespData: "Array of company documents",
    companySearchRespId: "CIF/CUI (8 digits)",
    companySearchRespCompany: "Company legal name",
    companySearchRespBrand: "Brand name (if set)",
    companySearchRespGroup: "Company group (if set)",
    companySearchRespStatus: "<code>activ</code>, <code>suspendat</code>, <code>inactiv</code>, or <code>radiat</code>",
    companySearchRespLocation: "Array of locations",
    companySearchRespWebsite: "Array of website URLs",
    companySearchRespCareer: "Array of career page URLs",
    companySearchRespLastScraped: "ISO8601 timestamp of last scrape",
    companySearchRespScraperFile: "Scraper file name",
    badRequestTitle: "Bad Request",
    companySearchEndpoint: "Company Search",
    companySearchParamsVal: "Query: <code>cif</code> (string) or <code>name</code> (string), optional <code>rows</code> (int, max 50), <code>start</code> (int)",
    companySearchStatus200: "Companies found and returned successfully",
    companySearchStatus400: "Missing required query parameter (cif or name)",
    companySearchStatus405: "Only GET method is allowed",
    companySearchStatus503: "Solr core is unavailable or environment not configured",

    companyAddTag: "Add or update a company in the Solr index",
    companyAddDesc: "Upserts a company document into the Solr <code>company</code> core. If a document with the same CIF (<code>id</code>) already exists, it is replaced. Designed for scrapers and company integrations to keep the company index up to date.",
    companyAddHow1: "Validates required fields (<code>id</code> as 8-digit CIF, <code>company</code> name)",
    companyAddHow2: "Sanitizes string fields with <code>htmlspecialchars</code> to prevent XSS",
    companyAddHow3: "Normalizes array fields (<code>location</code>, <code>website</code>, <code>career</code>) — accepts string or array",
    companyAddHow4: "Validates URLs in <code>website</code> and <code>career</code> fields",
    companyAddHow5: "Sends the document to Solr with <code>commitWithin=1000</code> and <code>overwrite=true</code>",
    companyAddAuthDesc: "This endpoint uses Solr Basic Auth via environment variables <code>SOLR_USER</code> and <code>SOLR_PASS</code> configured in <code>api.env</code>. No client-side authentication is required.",
    companyAddBodyTitle: "Request body (JSON)",
    companyAddFieldId: "8-digit CIF/CUI (e.g. <code>\"24415960\"</code>)",
    companyAddFieldCompany: "Company legal name",
    companyAddFieldBrand: "Brand / trade name",
    companyAddFieldGroup: "Company group / parent",
    companyAddFieldStatus: "One of: <code>activ</code>, <code>suspendat</code>, <code>inactiv</code>, <code>radiat</code>",
    companyAddFieldLocation: "City or array of cities",
    companyAddFieldWebsite: "Website URL(s) — must be valid URLs",
    companyAddFieldCareer: "Career page URL(s) — must be valid URLs",
    companyAddFieldLastScraped: "ISO8601 timestamp of last scrape",
    companyAddFieldScraperFile: "Name of the scraper file",
    companyAddRespTitle: "Response fields",
    companyAddRespSuccess: "Always <code>true</code> on success",
    companyAddRespId: "The CIF of the upserted company",
    companyAddRespMessage: "Confirmation message",
    companyAdd400Desc: "Returned when the request body is invalid or required fields are missing/invalid.",
    companyAddEndpoint: "Company Add",
    companyAddAuthVal: "Solr Basic Auth (server-side, via <code>api.env</code>)",
    companyAddParamsVal: "Body: <code>{\"id\": \"...\", \"company\": \"...\", ...}</code>",
    companyAddStatus200: "Company was upserted successfully",
    companyAddStatus400: "Invalid body, missing required fields, or validation failed",
    companyAddStatus405: "Only PUT method is allowed",
    companyAddStatus503: "Solr core is unavailable or environment not configured",

    scraperUploadTag: "Upload jobs from scrapers to Solr",
    scraperUploadDesc: "Accepts a JSON array of job listings and upserts them directly into the Solr <code>job</code> core. Designed for automated scrapers that need to upload jobs in bulk. Automatically fixes diacritics in city names and lowercases tags. Existing jobs (same <code>url</code>) are overwritten.",
    scraperUploadHow1: "Receives a JSON array of job objects (or <code>{\"jobs\": [...]}</code>)",
    scraperUploadHow2: "Skips entries missing required fields (<code>url</code>, <code>title</code>, <code>company</code>)",
    scraperUploadHow3: "Normalizes <code>location</code> (fixes diacritics, accepts string or array)",
    scraperUploadHow4: "Lowercases <code>tags</code> and filters empty values",
    scraperUploadHow5: "Sends to Solr with <code>commitWithin=1000</code> and <code>overwrite=true</code>",
    scraperUploadAuthDesc: "This endpoint uses Solr Basic Auth via environment variables <code>SOLR_USER</code> and <code>SOLR_PASS</code> configured in <code>api.env</code>. No client-side authentication is required.",
    scraperUploadBodyTitle: "Request body (JSON)",
    scraperUploadBodyNote: "Accepts either a plain array <code>[{...}, ...]</code> or an object <code>{\"jobs\": [{...}, ...]}</code>.",
    scraperUploadFieldUrl: "Full URL to the job detail page (unique key)",
    scraperUploadFieldTitle: "Exact job position title",
    scraperUploadFieldCompany: "Hiring company name",
    scraperUploadFieldCif: "CIF/CUI of the company",
    scraperUploadFieldLocation: "City or array of cities (diacritics auto-fixed)",
    scraperUploadFieldTags: "Skill tags (auto-lowercased)",
    scraperUploadFieldWorkmode: "<code>remote</code>, <code>on-site</code>, or <code>hybrid</code>",
    scraperUploadFieldDate: "ISO8601 UTC timestamp",
    scraperUploadFieldStatus: "<code>scraped</code>, <code>tested</code>, <code>published</code>, or <code>verified</code>",
    scraperUploadRespTitle: "Response fields",
    scraperUploadRespSuccess: "Confirmation message",
    scraperUploadRespCount: "Number of jobs sent to Solr",
    scraperUploadEndpoint: "Scraper Jobs Upload",
    scraperUploadAuthVal: "Solr Basic Auth (server-side, via <code>api.env</code>)",
    scraperUploadParamsVal: "Body: <code>[{\"url\": \"...\", \"title\": \"...\", \"company\": \"...\", ...}]</code>",
    scraperUploadStatus200: "Jobs were uploaded to Solr successfully",
    scraperUploadStatus400: "Empty payload, missing required fields, or invalid JSON",
    scraperUploadStatus405: "Only POST method is allowed",
    scraperUploadStatus415: "Content-Type is not application/json",
    scraperUploadStatus503: "Solr core is unavailable or environment not configured",
    unsupportedMediaTypeTitle: "Unsupported Media Type",

    scraperQueryTag: "Query jobs by company CIF",
    scraperQueryDesc: "Queries the Solr <code>job</code> core for all jobs matching a company CIF. Returns paginated results with standard job fields. Designed for scrapers that need to read existing jobs before re-scraping.",
    scraperQueryHow1: "Validates the <code>cif</code> parameter (must be exactly 8 digits)",
    scraperQueryHow2: "Escapes special Solr characters to prevent query injection",
    scraperQueryHow3: "Queries the Solr <code>job</code> core with pagination support",
    scraperQueryHow4: "Returns matching documents with standard job fields",
    scraperQueryParamCif: "8-digit CIF/CUI (exact match)",
    scraperQueryParamRows: "Max results (default: 100, max: 500)",
    scraperQueryParamStart: "Offset for pagination (default: 0)",
    scraperQueryRespTitle: "Response fields",
    scraperQueryRespSuccess: "Always <code>true</code> on success",
    scraperQueryRespTotal: "Total number of matching jobs in Solr",
    scraperQueryRespCount: "Number of jobs returned in this response",
    scraperQueryRespData: "Array of job documents",
    scraperQueryEndpoint: "Scraper Jobs Query",
    scraperQueryAuthVal: "Solr Basic Auth (server-side, via <code>api.env</code>)",
    scraperQueryParamsVal: "Query: <code>cif</code> (string, required), optional <code>rows</code> (int, max 500), <code>start</code> (int)",
    scraperQueryStatus200: "Jobs found and returned successfully",
    scraperQueryStatus400: "Missing or invalid CIF parameter",
    scraperQueryStatus405: "Only GET method is allowed",
    scraperQueryStatus503: "Solr core is unavailable or environment not configured",

    scraperDeleteTag: "Delete jobs by CIF or URL",
    scraperDeleteWarning: "<strong>Warning:</strong> This action permanently deletes matching job records from the Solr database. This cannot be undone.",
    scraperDeleteDesc: "Deletes jobs from the Solr <code>job</code> core by company CIF (all jobs) or by URL (single job). Designed for internal scraper use — when a company becomes inactive in ANAF or when expired job URLs are cleaned up.",
    scraperDeleteHow1: "Accepts a JSON body with <code>cif</code> or <code>url</code>",
    scraperDeleteHow2: "Counts matching documents before deletion",
    scraperDeleteHow3: "Returns 404 if no matching jobs found",
    scraperDeleteHow4: "Deletes all matching jobs with <code>commit=true</code>",
    scraperDeleteBodyTitle: "Request body (JSON)",
    scraperDeleteFieldCif: "Delete all jobs for this 8-digit CIF",
    scraperDeleteFieldUrl: "Delete a single job by its URL",
    scraperDeleteBodyNote: "Exactly one of <code>cif</code> or <code>url</code> is required. Providing both returns an error.",
    scraperDeleteRespTitle: "Response fields",
    scraperDeleteRespSuccess: "Always <code>true</code> on success",
    scraperDeleteRespMessage: "Confirmation message",
    scraperDeleteRespCount: "Number of jobs deleted",
    scraperDeleteEndpoint: "Scraper Jobs Delete",
    scraperDeleteAuthVal: "Solr Basic Auth (server-side, via <code>api.env</code>)",
    scraperDeleteParamsVal: "Body: <code>{\"cif\": \"...\"}</code> or <code>{\"url\": \"...\"}</code>",
    scraperDeleteStatus200: "Jobs were deleted successfully",
    scraperDeleteStatus400: "Missing required fields, invalid CIF, or invalid JSON",
    scraperDeleteStatus404: "No jobs found matching the criteria",
    scraperDeleteStatus405: "Only DELETE method is allowed",
    scraperDeleteStatus415: "Content-Type is not application/json",
    scraperDeleteStatus503: "Solr core is unavailable or environment not configured",

    contextParagraph: "This page exposes public endpoints of the peviitor.ro API, a job discovery platform. We are in the process of reviewing and expanding the API, and the documentation will gradually improve.",
    availableEndpointsTitle: "Currently available endpoints",
    availableEndpointsDesc: "The endpoints below are available right now and can be used for testing and exploration. The API is being standardized, and we will gradually publish new endpoints along with more detailed documentation.",
    statusTitle: "Current API Status",
    statusDesc: "We are working on:",
    statusItem1: "full API documentation based on a public specification (OpenAPI/Swagger)",
    statusItem2: "introducing a domain validation mechanism (via DNS TXT records) to link API keys to company websites",
    statusItem3: "defining dedicated endpoints for job management (create, update, close) in a standardized way",
    statusFooter: "As new endpoints become available, we will publish them here along with request/response examples and integration recommendations.",
    integrationTitle: "How you will be able to integrate your website (coming soon)",
    integrationDesc: "Our goal is to provide a simple, automated way for company websites to submit jobs to peviitor.ro via API. The intended design is:",
    integrationItem1: "API keys will be associated with a website domain (e.g. <code>example.com</code>)",
    integrationItem2: "the domain can be proven via a DNS TXT record, so only the domain owner can publish jobs on behalf of that company",
    integrationItem3: "dedicated endpoints for creating, updating, and closing jobs will be publicly documented with clear integration examples",
    integrationFooter: "As these components are ready, we will document them on this page.",
  },
  ro: {
    brand: "peviitor API",
    subtitle: "Platform\u0103 de descoperire a joburilor \u2014 documenta\u021bie API public\u0103",
    endpointTag: "Ob\u021Bine un job aleator",
    endpointDesc: "Returneaz\u0103 un singur job aleator din indexul Solr. Util pentru widget-uri de descoperire, func\u021Bii de \u201Cjobul zilei\u201D sau testarea integr\u0103rilor.",
    howItWorksTitle: "Cum func\u021Bioneaz\u0103",
    how1: "Interogheaz\u0103 Solr pentru num\u0103rul total de jobs indexate",
    how2: "Alege un offset aleator \u00Eentre 0 \u0219i num\u0103rul total",
    how3: "Preia exact un document la acel offset",
    how4: "Returneaz\u0103 jobul mapat conform Job Model Schema peviitor",
    tryItTitle: "\u00CEncearc\u0103",
    respFieldsTitle: "C\u00E2mpurile r\u0103spunsului",
    description: "Descriere",
    descTitle: "Titlul exact al pozi\u021Biei",
    descCompany: "Numele companiei angajatoare (majuscule)",
    descLocation: "List\u0103 de ora\u0219e sau adrese",
    descWorkmode: "<code>remote</code>, <code>on-site</code> sau <code>hybrid</code>",
    descUrl: "URL complet c\u0103tre pagina jobului (cheie unic\u0103)",
    descSalary: "Interval salarial cu moned\u0103, ex. <code>5000-8000 RON</code>",
    descTags: "Tag-uri de skills (lowercase, max 20)",
    descCif: "CIF/CUI al companiei",
    descDate: "Timestamp ISO8601 UTC al index\u0103rii",
    descStatus: "<code>scraped</code>, <code>tested</code>, <code>published</code> sau <code>verified</code>",
    successTitle: "200 \u2014 Succes",
    notFoundTitle: "Niciun job g\u0103sit",
    unavailTitle: "Serviciu indisponibil",
    statusCodesTitle: "Coduri de stare",
    status200: "Un job aleator a fost g\u0103sit \u0219i returnat cu succes",
    status404: "Nu exist\u0103 joburi indexate \u00EEn core-ul Solr",
    status503: "Core-ul Solr este indisponibil sau mediul nu este configurat",
    requirementsTitle: "Cerin\u021Be",
    item: "Element",
    details: "Detalii",
    method: "Metod\u0103",
    auth: "Autentificare",
    authVal: "Niciuna (endpoint public)",
    params: "Parametri",
    paramsVal: "Niciunul",
    contentType: "Content-Type",

    authTitle: "Autentificare",
    methodNotAllowedTitle: "Metodă nepermisă",
    randomEndpoint: "Endpoint aleator",

    cleanjobsTag: "\u0218terge \u00EEnregistr\u0103rile de joburi pentru o companie",
    cleanjobsWarning: "<strong>Aten\u021Bie:</strong> Aceast\u0103 ac\u021Biune \u0219terge PERMANENT toate joburile pentru compania specificat\u0103 din baza de date Solr. Nu poate fi anulat\u0103.",
    cleanjobsDesc: "\u0218terge permanent toate documentele de job care corespund companiei date din core-ul Solr <code>job</code>. Conceput pentru proprietarii de website-uri de companii care doresc s\u0103 \u00EE\u0219i elimine list\u0103rile de pe platforma peviitor.",
    cleanjobsHow1: "Identific\u0103 compania dup\u0103 numele <code>company</code>, <code>cif</code> sau <code>brand</code>",
    cleanjobsHow2: "Calculeaz\u0103 <code>X-Api-Key</code> ca hash MD5 pe baza identificatorilor furniza\u021Bi",
    cleanjobsHow3: "Trimite un request DELETE cu corpul de confirmare",
    cleanjobsHow4: "Toate joburile corespunz\u0103toare sunt eliminate permanent din index",
    cleanjobsAuthDesc: "Trebuie s\u0103 furnizezi un header <code>X-Api-Key</code> calculat ca <code>md5()</code> al identificatorilor trimi\u0219i. Cheia se genereaz\u0103 din acelea\u0219i c\u00E2mpuri din corpul requestului:",
    cleanjobsBodyTitle: "Corpul requestului",
    cleanjobsBodyNote: "Cel pu\u021Bin unul dintre <code>company</code>, <code>cif</code> sau <code>brand</code> este obligatoriu. C\u00E2mpul <code>confirmation</code> trebuie s\u0103 fie exact <code>\"CLEAN_COMPANY_JOBS\"</code>.",
    cleanjobsRespTitle: "C\u00E2mpurile r\u0103spunsului",
    cleanjobsRespMessage: "Mesaj de confirmare a succesului",
    cleanjobsRespJobCount: "Num\u0103rul de joburi \u0219terse",
    cleanjobsRespCompany: "Numele companiei (dac\u0103 a fost furnizat)",
    cleanjobsRespCif: "CIF-ul companiei (dac\u0103 a fost furnizat)",
    cleanjobsRespBrand: "Brandul companiei (dac\u0103 a fost furnizat)",
    cleanjobsNotFoundTitle: "Niciun job g\u0103sit",
    cleanjobsEndpoint: "Endpoint cur\u0103\u021Bare joburi",
    cleanjobsParamsVal: "Body: <code>{\"company\"?: \"...\", \"cif\"?: \"...\", \"brand\"?: \"...\", \"confirmation\": \"CLEAN_COMPANY_JOBS\"}</code>",
    cleanjobsStatus200: "Joburile au fost \u0219terse cu succes",
    cleanjobsStatus401: "Header X-Api-Key invalid sau lips\u0103",
    cleanjobsStatus404: "Nu s-au g\u0103sit joburi care s\u0103 corespund\u0103 criteriilor date",
    cleanjobsStatus405: "Doar metoda DELETE este permis\u0103",
    cleanjobsStatus503: "Core-ul Solr este indisponibil sau mediul nu este configurat",
    unauthTitle: "Neautorizat",

    companySearchTag: "Caută companii după CIF sau nume",
    companySearchDesc: "Interoghează core-ul Solr <code>company</code> folosind edismax și returnează documentele companiilor găsite. Poți căuta după CIF exact (număr de 8 cifre) sau după numele companiei (căutare full-text).",
    companySearchHow1: "Furnizează fie <code>cif</code> (potrivire exactă) fie <code>name</code> (căutare full-text) ca parametru de query",
    companySearchHow2: "Endpoint-ul construiește o interogare edismax și o trimite la core-ul Solr <code>company</code>",
    companySearchHow3: "Returnează documentele găsite cu câmpurile: id, company, brand, group, status, location, website, career, lastScraped, scraperFile",
    queryParamsTitle: "Parametri de query",
    companySearchParamCif: "CIF/CUI de 8 cifre (potrivire exactă). Caracterele non-cifre sunt eliminate.",
    companySearchParamName: "Numele companiei pentru căutare full-text prin edismax",
    companySearchParamRows: "Număr maxim de rezultate (implicit: 10, maxim: 50)",
    companySearchParamStart: "Offset pentru paginare (implicit: 0)",
    companySearchParamNote: "Cel puțin unul dintre <code>cif</code> sau <code>name</code> este obligatoriu.",
    companySearchRespTitle: "Câmpurile răspunsului",
    companySearchRespSuccess: "Întotdeauna <code>true</code> la succes",
    companySearchRespTotal: "Numărul total de documente potrivite în Solr",
    companySearchRespCount: "Numărul de documente returnate în acest răspuns",
    companySearchRespData: "Array de documente companie",
    companySearchRespId: "CIF/CUI (8 cifre)",
    companySearchRespCompany: "Numele legal al companiei",
    companySearchRespBrand: "Numele brandului (dacă este setat)",
    companySearchRespGroup: "Grupul companiei (dacă este setat)",
    companySearchRespStatus: "<code>activ</code>, <code>suspendat</code>, <code>inactiv</code> sau <code>radiat</code>",
    companySearchRespLocation: "Array de locații",
    companySearchRespWebsite: "Array de URL-uri website",
    companySearchRespCareer: "Array de URL-uri pagini carieră",
    companySearchRespLastScraped: "Timestamp ISO8601 al ultimului scrap",
    companySearchRespScraperFile: "Numele fișierului scraper",
    badRequestTitle: "Cerere invalidă",
    companySearchEndpoint: "Căutare companii",
    companySearchParamsVal: "Query: <code>cif</code> (string) sau <code>name</code> (string), opțional <code>rows</code> (int, max 50), <code>start</code> (int)",
    companySearchStatus200: "Companiile au fost găsite și returnate cu succes",
    companySearchStatus400: "Lipsește parametrul de query obligatoriu (cif sau name)",
    companySearchStatus405: "Doar metoda GET este permisă",
    companySearchStatus503: "Core-ul Solr este indisponibil sau mediul nu este configurat",

    companyAddTag: "Adaugă sau actualizează o companie în indexul Solr",
    companyAddDesc: "Inserează sau actualizează un document companie în core-ul Solr <code>company</code>. Dacă există deja un document cu același CIF (<code>id</code>), acesta este înlocuit. Conceput pentru scrapers și integrări de companii pentru a menține indexul de companii actualizat.",
    companyAddHow1: "Validează câmpurile obligatorii (<code>id</code> ca CIF de 8 cifre, numele <code>company</code>)",
    companyAddHow2: "Sanitizează câmpurile de text cu <code>htmlspecialchars</code> pentru a preveni XSS",
    companyAddHow3: "Normalizează câmpurile de tip array (<code>location</code>, <code>website</code>, <code>career</code>) — acceptă string sau array",
    companyAddHow4: "Validează URL-urile din câmpurile <code>website</code> și <code>career</code>",
    companyAddHow5: "Trimite documentul la Solr cu <code>commitWithin=1000</code> și <code>overwrite=true</code>",
    companyAddAuthDesc: "Acest endpoint folosește Solr Basic Auth prin variabilele de mediu <code>SOLR_USER</code> și <code>SOLR_PASS</code> configurate în <code>api.env</code>. Nu este necesară autentificarea client-side.",
    companyAddBodyTitle: "Corpul requestului (JSON)",
    companyAddFieldId: "CIF/CUI de 8 cifre (ex. <code>\"24415960\"</code>)",
    companyAddFieldCompany: "Numele legal al companiei",
    companyAddFieldBrand: "Numele brandului / comercial",
    companyAddFieldGroup: "Grupul / compania părinte",
    companyAddFieldStatus: "Unul dintre: <code>activ</code>, <code>suspendat</code>, <code>inactiv</code>, <code>radiat</code>",
    companyAddFieldLocation: "Oraș sau array de orașe",
    companyAddFieldWebsite: "URL(uri) website — trebuie să fie URL-uri valide",
    companyAddFieldCareer: "URL(uri) pagini carieră — trebuie să fie URL-uri valide",
    companyAddFieldLastScraped: "Timestamp ISO8601 al ultimului scrap",
    companyAddFieldScraperFile: "Numele fișierului scraper",
    companyAddRespTitle: "Câmpurile răspunsului",
    companyAddRespSuccess: "Întotdeauna <code>true</code> la succes",
    companyAddRespId: "CIF-ul companiei upsertate",
    companyAddRespMessage: "Mesaj de confirmare",
    companyAdd400Desc: "Returnat când corpul requestului este invalid sau câmpurile obligatorii lipsesc/sunt invalide.",
    companyAddEndpoint: "Adăugare companii",
    companyAddAuthVal: "Solr Basic Auth (server-side, prin <code>api.env</code>)",
    companyAddParamsVal: "Body: <code>{\"id\": \"...\", \"company\": \"...\", ...}</code>",
    companyAddStatus200: "Compania a fost upsertată cu succes",
    companyAddStatus400: "Body invalid, câmpuri obligatorii lipsă sau validare eșuată",
    companyAddStatus405: "Doar metoda PUT este permisă",
    companyAddStatus503: "Core-ul Solr este indisponibil sau mediul nu este configurat",

    scraperUploadTag: "Încarcă joburi de la scrapers în Solr",
    scraperUploadDesc: "Acceptă un array JSON de joburi și le inserează/actualizează direct în core-ul Solr <code>job</code>. Conceput pentru scrapers automate care trebuie să încarce joburi în volum. Corectează automat diacriticele din numele orașelor și transformă tag-urile în lowercase. Joburile existente (același <code>url</code>) sunt suprascrise.",
    scraperUploadHow1: "Primește un array JSON de obiecte job (sau <code>{\"jobs\": [...]}</code>)",
    scraperUploadHow2: "Omite intrările fără câmpurile obligatorii (<code>url</code>, <code>title</code>, <code>company</code>)",
    scraperUploadHow3: "Normalizează <code>location</code> (corectează diacriticele, acceptă string sau array)",
    scraperUploadHow4: "Transformă <code>tags</code>-urile în lowercase și filtrează valorile goale",
    scraperUploadHow5: "Trimite la Solr cu <code>commitWithin=1000</code> și <code>overwrite=true</code>",
    scraperUploadAuthDesc: "Acest endpoint folosește Solr Basic Auth prin variabilele de mediu <code>SOLR_USER</code> și <code>SOLR_PASS</code> configurate în <code>api.env</code>. Nu este necesară autentificarea client-side.",
    scraperUploadBodyTitle: "Corpul requestului (JSON)",
    scraperUploadBodyNote: "Acceptă fie un array simplu <code>[{...}, ...]</code>, fie un obiect <code>{\"jobs\": [{...}, ...]}</code>.",
    scraperUploadFieldUrl: "URL complet către pagina jobului (cheie unică)",
    scraperUploadFieldTitle: "Titlul exact al poziției",
    scraperUploadFieldCompany: "Numele companiei angajatoare",
    scraperUploadFieldCif: "CIF/CUI al companiei",
    scraperUploadFieldLocation: "Oraș sau array de orașe (diacritice auto-corectate)",
    scraperUploadFieldTags: "Tag-uri de skills (auto-lowercase)",
    scraperUploadFieldWorkmode: "<code>remote</code>, <code>on-site</code> sau <code>hybrid</code>",
    scraperUploadFieldDate: "Timestamp ISO8601 UTC",
    scraperUploadFieldStatus: "<code>scraped</code>, <code>tested</code>, <code>published</code> sau <code>verified</code>",
    scraperUploadRespTitle: "Câmpurile răspunsului",
    scraperUploadRespSuccess: "Mesaj de confirmare",
    scraperUploadRespCount: "Numărul de joburi trimise la Solr",
    scraperUploadEndpoint: "Încărcare joburi scrapers",
    scraperUploadAuthVal: "Solr Basic Auth (server-side, prin <code>api.env</code>)",
    scraperUploadParamsVal: "Body: <code>[{\"url\": \"...\", \"title\": \"...\", \"company\": \"...\", ...}]</code>",
    scraperUploadStatus200: "Joburile au fost încărcate cu succes în Solr",
    scraperUploadStatus400: "Payload gol, câmpuri obligatorii lipsă sau JSON invalid",
    scraperUploadStatus405: "Doar metoda POST este permisă",
    scraperUploadStatus415: "Content-Type nu este application/json",
    scraperUploadStatus503: "Core-ul Solr este indisponibil sau mediul nu este configurat",
    unsupportedMediaTypeTitle: "Tip de media nesuportat",

    scraperQueryTag: "Interoghează joburi după CIF companie",
    scraperQueryDesc: "Interoghează core-ul Solr <code>job</code> pentru toate joburile care corespund CIF-ului unei companii. Returnează rezultate paginate cu câmpurile standard de job. Conceput pentru scrapers care trebuie să citească joburile existente înainte de re-scraping.",
    scraperQueryHow1: "Validează parametrul <code>cif</code> (trebuie să aibă exact 8 cifre)",
    scraperQueryHow2: "Escapează caracterele speciale Solr pentru a preveni injectarea de query",
    scraperQueryHow3: "Interoghează core-ul Solr <code>job</code> cu suport de paginare",
    scraperQueryHow4: "Returnează documentele potrivite cu câmpurile standard de job",
    scraperQueryParamCif: "CIF/CUI de 8 cifre (potrivire exactă)",
    scraperQueryParamRows: "Număr maxim de rezultate (implicit: 100, maxim: 500)",
    scraperQueryParamStart: "Offset pentru paginare (implicit: 0)",
    scraperQueryRespTitle: "Câmpurile răspunsului",
    scraperQueryRespSuccess: "Întotdeauna <code>true</code> la succes",
    scraperQueryRespTotal: "Numărul total de joburi potrivite în Solr",
    scraperQueryRespCount: "Numărul de joburi returnate în acest răspuns",
    scraperQueryRespData: "Array de documente job",
    scraperQueryEndpoint: "Interogare joburi scrapers",
    scraperQueryAuthVal: "Solr Basic Auth (server-side, prin <code>api.env</code>)",
    scraperQueryParamsVal: "Query: <code>cif</code> (string, obligatoriu), opțional <code>rows</code> (int, max 500), <code>start</code> (int)",
    scraperQueryStatus200: "Joburile au fost găsite și returnate cu succes",
    scraperQueryStatus400: "CIF lipsă sau invalid",
    scraperQueryStatus405: "Doar metoda GET este permisă",
    scraperQueryStatus503: "Core-ul Solr este indisponibil sau mediul nu este configurat",

    scraperDeleteTag: "Ștergere joburi după CIF sau URL",
    scraperDeleteWarning: "<strong>Atenție:</strong> Această acțiune șterge permanent din baza de date Solr joburile care corespund. Operația nu poate fi anulată.",
    scraperDeleteDesc: "Șterge joburi din core-ul Solr <code>job</code> după CIF (toate joburile) sau URL (un singur job). Destinat uzului intern al scraper-ului — când o companie devine inactivă în ANAF sau când URL-urile expirate sunt curățate.",
    scraperDeleteHow1: "Acceptă un corp JSON cu <code>cif</code> sau <code>url</code>",
    scraperDeleteHow2: "Numără documentele înainte de ștergere",
    scraperDeleteHow3: "Returnează 404 dacă nu găsește joburi",
    scraperDeleteHow4: "Șterge toate joburile cu <code>commit=true</code>",
    scraperDeleteBodyTitle: "Corp cerere (JSON)",
    scraperDeleteFieldCif: "Șterge toate joburile pentru acest CIF de 8 cifre",
    scraperDeleteFieldUrl: "Șterge un singur job după URL",
    scraperDeleteBodyNote: "Exact unul dintre <code>cif</code> sau <code>url</code> este obligatoriu. Furnizarea ambelor returnează o eroare.",
    scraperDeleteRespTitle: "Câmpuri răspuns",
    scraperDeleteRespSuccess: "Întotdeauna <code>true</code> la succes",
    scraperDeleteRespMessage: "Mesaj de confirmare",
    scraperDeleteRespCount: "Numărul de joburi șterse",
    scraperDeleteEndpoint: "Ștergere Joburi Scraper",
    scraperDeleteAuthVal: "Solr Basic Auth (server-side, prin <code>api.env</code>)",
    scraperDeleteParamsVal: "Corp: <code>{\"cif\": \"...\"}</code> sau <code>{\"url\": \"...\"}</code>",
    scraperDeleteStatus200: "Joburile au fost șterse cu succes",
    scraperDeleteStatus400: "Câmpuri obligatorii lipsă, CIF invalid sau JSON invalid",
    scraperDeleteStatus404: "Nu s-au găsit joburi care să corespundă criteriilor",
    scraperDeleteStatus405: "Doar metoda DELETE este permisă",
    scraperDeleteStatus415: "Content-Type nu este application/json",
    scraperDeleteStatus503: "Core-ul Solr este indisponibil sau mediul nu este configurat",

    contextParagraph: "Această pagină expune endpoint-uri publice ale API-ului peviitor.ro, o platformă de descoperire a joburilor. Suntem în proces de revizuire și extindere a API-ului, iar documentația se va îmbunătăți treptat.",
    availableEndpointsTitle: "Endpoint-uri disponibile în prezent",
    availableEndpointsDesc: "Endpoint-urile de mai jos sunt disponibile în acest moment și pot fi folosite pentru testare și explorare. API-ul este în curs de standardizare, iar pentru fiecare funcționalitate vom publica treptat endpoint-uri noi, împreună cu o documentație mai detaliată.",
    statusTitle: "Stare curentă a API-ului",
    statusDesc: "Lucrăm la:",
    statusItem1: "documentarea completă a API-ului pe baza unei specificații publice (de tip OpenAPI/Swagger)",
    statusItem2: "introducerea unui mecanism de validare a domeniilor (prin înregistrări DNS TXT) pentru a lega cheile API de website-ul companiei",
    statusItem3: "definirea unor endpoint-uri dedicate pentru gestionarea joburilor (creare, actualizare, închidere) într-un mod standardizat",
    statusFooter: "Pe măsură ce noile endpoint-uri vor deveni disponibile, le vom publica aici împreună cu exemple de request/response și recomandări de integrare.",
    integrationTitle: "Cum vei putea integra website-ul tău (în curând)",
    integrationDesc: "Obiectivul nostru este să oferim un mod simplu și automat prin care website-urile de companii să trimită joburi către peviitor.ro prin API. Designul urmărit este:",
    integrationItem1: "cheile de API vor fi asociate unui domeniu de website (de exemplu, <code>exemplu.com</code>)",
    integrationItem2: "domeniul va putea fi dovedit printr-o înregistrare DNS TXT, astfel încât doar proprietarul domeniului să poată publica joburi în numele acelei companii",
    integrationItem3: "endpoint-uri dedicate pentru creare, actualizare și închidere joburi vor fi documentate public, cu exemple clare de integrare",
    integrationFooter: "Pe măsură ce aceste componente vor fi gata, le vom documenta pe această pagină.",
  }
};

function setLang(lang) {
  document.querySelectorAll('[data-i18n]').forEach(el => {
    const key = el.getAttribute('data-i18n');
    if (i18n[lang] && i18n[lang][key] !== undefined) {
      el.innerHTML = i18n[lang][key];
    }
  });
  document.querySelectorAll('.lang-toggle button').forEach(btn => btn.classList.remove('active'));
  document.getElementById('lang-' + lang).classList.add('active');
  document.documentElement.lang = lang;
  localStorage.setItem('peviitor-lang', lang);
}

function toggleEndpoint(name) {
  const content = document.getElementById(name + '-content');
  const arrow = document.getElementById('arrow-' + name);
  const isOpen = content.style.display !== 'none';
  content.style.display = isOpen ? 'none' : 'block';
  if (arrow) arrow.classList.toggle('open', !isOpen);
}

const saved = localStorage.getItem('peviitor-lang');
if (saved === 'en') setLang('en');
else setLang('ro');
</script>
</body>
</html>
