<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>peviitor API — Random Job Endpoint</title>
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
    padding: 3rem 0 2rem;
  }
  header h1 {
    font-size: 2rem;
    font-weight: 700;
    color: #c44536;
    letter-spacing: -0.02em;
  }
  header p {
    color: #7d6b5a;
    font-size: 1rem;
    margin-top: 0.4rem;
  }
  header .base-url {
    display: inline-block;
    margin-top: 0.8rem;
    padding: 0.4rem 1rem;
    background: #e8d5c4;
    border-radius: 8px;
    font-family: 'Fira Code', monospace;
    font-size: 0.85rem;
    color: #5a4a3a;
  }

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
  .card-body:empty { display: none; }

  /* Endpoint row */
  .endpoint-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, #f0faf3, #e8f5ec);
    border-bottom: 1px solid #d4e8db;
  }
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
    margin-left: auto;
    font-size: 0.85rem;
    color: #4a7c5a;
  }

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
  .required-tag {
    display: inline-block;
    font-size: 0.68rem;
    font-weight: 600;
    padding: 0.1rem 0.4rem;
    border-radius: 4px;
    background: #ffcdd2;
    color: #b71c1c;
    margin-left: 0.3rem;
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
  .sc-404 { background: #fff3e0; color: #e65100; }
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

  /* Footer */
  footer {
    text-align: center;
    padding: 2rem 0;
    color: #9a8a7a;
    font-size: 0.8rem;
  }
  footer a { color: #c44536; text-decoration: none; }
  footer a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="container">

  <header>
    <h1>peviitor API</h1>
    <p>Job discovery platform — public API documentation</p>
    <div class="base-url">https://api.peviitor.ro</div>
  </header>

  <!-- Endpoint -->
  <div class="card">
    <div class="endpoint-row">
      <span class="method-badge">GET</span>
      <span class="endpoint-path">/v1/random/</span>
      <span class="endpoint-desc">Get a random job listing</span>
    </div>

    <div class="card-body">
      <p style="margin-bottom:1rem;color:#5a4a3a;">
        Returns a single random job from the Solr job index. Useful for discovery widgets,
        "job of the day" features, or testing integrations.
      </p>

      <div class="section-title">How it works</div>
      <ol style="margin:0 0 1.5rem 1.2rem;color:#5a4a3a;font-size:0.9rem;">
        <li>Queries Solr for the total number of indexed jobs</li>
        <li>Picks a random offset between 0 and total count</li>
        <li>Fetches exactly one document at that offset</li>
        <li>Returns the job mapped to the peviitor Job Model Schema</li>
      </ol>

      <!-- cURL example -->
      <div class="section-title">Try it</div>
      <div class="curl-box">
        <div class="curl-label">curl</div>
        <pre>curl -X GET "https://api.peviitor.ro/v1/random/" \
  -H "Accept: application/json"</pre>
      </div>
    </div>
  </div>

  <!-- Response fields -->
  <div class="card">
    <div class="card-header">Response fields</div>
    <div class="card-body">
      <table class="prop-table">
        <thead><tr>
          <th>Field</th><th>Type</th><th>Description</th>
        </tr></thead>
        <tbody>
          <tr><td>title</td><td><span class="type-tag">string</span></td><td>Exact job position title</td></tr>
          <tr><td>company</td><td><span class="type-tag">string</span></td><td>Hiring company name (uppercase)</td></tr>
          <tr><td>location</td><td><span class="type-tag">string[]</span></td><td>Array of cities or addresses</td></tr>
          <tr><td>workmode</td><td><span class="type-tag">string</span></td><td><code>remote</code>, <code>on-site</code>, or <code>hybrid</code></td></tr>
          <tr><td>url</td><td><span class="type-tag">string</span></td><td>Full URL to the job detail page (unique key)</td></tr>
          <tr><td>salary</td><td><span class="type-tag">string</span></td><td>Salary interval with currency, e.g. <code>5000-8000 RON</code></td></tr>
          <tr><td>tags</td><td><span class="type-tag">string[]</span></td><td>Skill tags (lowercase, max 20)</td></tr>
          <tr><td>cif</td><td><span class="type-tag">string</span></td><td>CIF/CUI of the company</td></tr>
          <tr><td>date</td><td><span class="type-tag">string</span></td><td>ISO8601 UTC timestamp of indexing</td></tr>
          <tr><td>status</td><td><span class="type-tag">string</span></td><td><code>scraped</code>, <code>tested</code>, <code>published</code>, or <code>verified</code></td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Response example -->
  <div class="card">
    <div class="card-header">200 — Success</div>
    <div class="card-body">
      <pre>{
  <span class="json-key">"title"</span>: <span class="json-string">"Inginer IT"</span>,
  <span class="json-key">"company"</span>: <span class="json-string">"COMPANY SRL"</span>,
  <span class="json-key">"location"</span>: [<span class="json-string">"București"</span>, <span class="json-string">"Cluj-Napoca"</span>],
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

  <!-- Error 404 -->
  <div class="card">
    <div class="card-header">404 — No Jobs Found</div>
    <div class="card-body">
      <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"No jobs found"</span>
}</pre>
    </div>
  </div>

  <!-- Error 503 -->
  <div class="card">
    <div class="card-header">503 — Service Unavailable</div>
    <div class="card-body">
      <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Job core unavailable"</span>,
  <span class="json-key">"details"</span>: <span class="json-string">"PROD_SERVER not set"</span>
}</pre>
    </div>
  </div>

  <!-- Status codes -->
  <div class="card">
    <div class="card-header">Status codes</div>
    <div class="card-body">
      <ul class="status-list">
        <li>
          <span class="status-code sc-200">200</span>
          A random job was found and returned successfully
        </li>
        <li>
          <span class="status-code sc-404">404</span>
          No jobs are currently indexed in the Solr core
        </li>
        <li>
          <span class="status-code sc-503">503</span>
          Solr core is unavailable or environment not configured
        </li>
      </ul>
    </div>
  </div>

  <!-- Requirements -->
  <div class="card">
    <div class="card-header">Requirements</div>
    <div class="card-body">
      <table class="prop-table">
        <thead><tr><th style="width:100px">Item</th><th>Details</th></tr></thead>
        <tbody>
          <tr><td>Method</td><td><code>GET</code> only</td></tr>
          <tr><td>Auth</td><td>None (public endpoint)</td></tr>
          <tr><td>Params</td><td>None</td></tr>
          <tr><td>Content-Type</td><td><code>application/json</code></td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <footer>
    Powered by <a href="https://peviitor.ro" target="_blank">peviitor.ro</a> &middot;
    <a href="https://github.com/peviitor-ro/api" target="_blank">GitHub</a>
  </footer>

</div>
</body>
</html>
