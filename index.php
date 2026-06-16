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

  /* DELETE badge variant */
  .method-badge-delete {
    background: #c62828;
    box-shadow: 0 2px 6px rgba(198, 40, 40, 0.3);
  }
  .endpoint-row-delete {
    background: linear-gradient(135deg, #fef0f0, #fce4e4);
    border-bottom: 1px solid #f5cdcd;
  }
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
      <button onclick="setLang('en')" id="lang-en" class="active">EN</button>
      <button onclick="setLang('ro')" id="lang-ro">RO</button>
    </div>
    <h1 data-i18n="brand">peviitor API</h1>
    <p data-i18n="subtitle">Job discovery platform — public API documentation</p>
    <div class="base-url">https://api.peviitor.ro</div>
  </header>

  <!-- Endpoint -->
  <div class="card">
    <div class="endpoint-row">
      <span class="method-badge">GET</span>
      <span class="endpoint-path">/v1/random/</span>
      <span class="endpoint-desc" data-i18n="endpointTag">Get a random job listing</span>
    </div>

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

  <!-- ============================================= -->
  <!-- Empty Endpoint -->
  <!-- ============================================= -->
  <div class="card">
    <div class="endpoint-row endpoint-row-delete">
      <span class="method-badge method-badge-delete">DELETE</span>
      <span class="endpoint-path">/v1/empty/</span>
      <span class="endpoint-desc" data-i18n="emptyTag">Delete all job records</span>
    </div>

    <div class="card-body">
      <div class="warning-banner" data-i18n="emptyWarning">
        <strong>Warning:</strong> This action permanently deletes ALL job records from the Solr database.
        This cannot be undone.
      </div>

      <p style="margin-bottom:1rem;color:#5a4a3a;" data-i18n="emptyDesc">
        Permanently deletes every job document from the <code>job</code> Solr core.
        In production, requires valid API credentials.
      </p>

      <div class="section-title" data-i18n="authTitle">Authentication</div>
      <p style="margin-bottom:1rem;color:#5a4a3a;font-size:0.9rem;" data-i18n="emptyAuthDesc">
        In <strong>production</strong> mode (<code>NODE_ENV=production</code>), you must provide
        <code>X-API-Key</code> and <code>X-Cleanup-Secret</code> headers matching
        <code>api.env</code>. In any other environment (<strong>test</strong>, <strong>dev</strong>,
        <strong>staging</strong>, etc.), these headers are <em>not checked</em> and any value is
        accepted.
      </p>

      <div class="section-title" data-i18n="requestHeadersTitle">Request headers</div>
      <table class="prop-table" style="margin-bottom:1.5rem;">
        <thead><tr><th>Header</th><th>Required</th><th data-i18n="description">Description</th></tr></thead>
        <tbody>
          <tr><td>X-API-Key</td><td data-i18n="emptyApiKeyReq">Production only</td><td data-i18n="emptyApiKeyDesc">API key from <code>CLEANUP_API_KEY</code> in api.env</td></tr>
          <tr><td>X-Cleanup-Secret</td><td data-i18n="emptySecretReq">Production only</td><td data-i18n="emptySecretDesc">Secret from <code>CLEANUP_SECRET</code> in api.env</td></tr>
          <tr><td>Content-Type</td><td data-i18n="yes">Yes</td><td><code>application/json</code></td></tr>
        </tbody>
      </table>

      <div class="section-title" data-i18n="requestBodyTitle">Request body</div>
      <pre>{
  <span class="json-key">"confirmation"</span>: <span class="json-string">"DELETE_ALL_DATA"</span>
}</pre>

      <div class="section-title" data-i18n="tryItTitle">Try it</div>
      <div class="curl-box" style="margin-bottom:0.75rem;">
        <div class="curl-label">curl — production</div>
        <pre>curl -X DELETE "https://api.peviitor.ro/v1/empty/" \
  -H "X-API-Key: abc123xyz789" \
  -H "X-Cleanup-Secret: secret456def012" \
  -H "Content-Type: application/json" \
  -d '{"confirmation": "DELETE_ALL_DATA"}'</pre>
      </div>
      <div class="curl-box">
        <div class="curl-label">curl — test / dev</div>
        <pre>curl -X DELETE "https://test.peviitor.ro/api/v1/empty/" \
  -H "Content-Type: application/json" \
  -d '{"confirmation": "DELETE_ALL_DATA"}'</pre>
      </div>
    </div>
  </div>

  <!-- Empty: Response example -->
  <div class="card">
    <div class="card-header" data-i18n="emptySuccessTitle">200 — Jobs Deleted</div>
    <div class="card-body">
      <pre>{
  <span class="json-key">"message"</span>: <span class="json-string">"Jobs deleted successfully"</span>,
  <span class="json-key">"jobsDeleted"</span>: <span class="json-number">42</span>,
  <span class="json-key">"companiesDeleted"</span>: <span class="json-number">10</span>
}</pre>
    </div>
  </div>

  <!-- Empty: Error 401 -->
  <div class="card">
    <div class="card-header">401 — <span data-i18n="emptyUnauthTitle">Unauthorized</span></div>
    <div class="card-body">
      <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Unauthorized - invalid credentials"</span>
}</pre>
    </div>
  </div>

  <!-- Empty: Error 405 -->
  <div class="card">
    <div class="card-header">405 — <span data-i18n="methodNotAllowedTitle">Method Not Allowed</span></div>
    <div class="card-body">
      <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Only DELETE method allowed"</span>
}</pre>
    </div>
  </div>

  <!-- Response fields -->
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

  <!-- Response example -->
  <div class="card">
    <div class="card-header" data-i18n="successTitle">200 — Success</div>
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
    <div class="card-header">404 — <span data-i18n="notFoundTitle">No Jobs Found</span></div>
    <div class="card-body">
      <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"No jobs found"</span>
}</pre>
    </div>
  </div>

  <!-- Error 503 -->
  <div class="card">
    <div class="card-header">503 — <span data-i18n="unavailTitle">Service Unavailable</span></div>
    <div class="card-body">
      <pre>{
  <span class="json-key">"error"</span>: <span class="json-string">"Job core unavailable"</span>,
  <span class="json-key">"details"</span>: <span class="json-string">"PROD_SERVER not set"</span>
}</pre>
    </div>
  </div>

  <!-- Status codes -->
  <div class="card">
    <div class="card-header" data-i18n="statusCodesTitle">Status codes</div>
    <div class="card-body">
      <div style="font-size:0.8rem;font-weight:600;color:#5a4a3a;margin-bottom:0.5rem;text-transform:uppercase;letter-spacing:0.04em;" data-i18n="randomEndpoint">Random endpoint</div>
      <ul class="status-list" style="margin-bottom:1rem;">
        <li>
          <span class="status-code sc-200">200</span>
          <span data-i18n="status200">A random job was found and returned successfully</span>
        </li>
        <li>
          <span class="status-code sc-404">404</span>
          <span data-i18n="status404">No jobs are currently indexed in the Solr core</span>
        </li>
        <li>
          <span class="status-code sc-503">503</span>
          <span data-i18n="status503">Solr core is unavailable or environment not configured</span>
        </li>
      </ul>

      <div style="font-size:0.8rem;font-weight:600;color:#5a4a3a;margin-bottom:0.5rem;text-transform:uppercase;letter-spacing:0.04em;" data-i18n="emptyEndpoint">Empty endpoint</div>
      <ul class="status-list">
        <li>
          <span class="status-code sc-200">200</span>
          <span data-i18n="emptyStatus200">All jobs were deleted successfully</span>
        </li>
        <li>
          <span class="status-code sc-401" style="background:#ffebee;color:#c62828;">401</span>
          <span data-i18n="emptyStatus401">Invalid or missing credentials (production only)</span>
        </li>
        <li>
          <span class="status-code sc-405" style="background:#e8eaf6;color:#283593;">405</span>
          <span data-i18n="emptyStatus405">Only DELETE method is allowed</span>
        </li>
        <li>
          <span class="status-code sc-503">503</span>
          <span data-i18n="emptyStatus503">Solr core is unavailable or environment not configured</span>
        </li>
      </ul>
    </div>
  </div>

  <!-- Requirements - Random -->
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

    emptyTag: "Delete all job records",
    emptyWarning: "<strong>Warning:</strong> This action permanently deletes ALL job records from the Solr database. This cannot be undone.",
    emptyDesc: "Permanently deletes every job document from the <code>job</code> Solr core. In production, requires valid API credentials.",
    authTitle: "Authentication",
    emptyAuthDesc: "In <strong>production</strong> mode (<code>NODE_ENV=production</code>), you must provide <code>X-API-Key</code> and <code>X-Cleanup-Secret</code> headers matching <code>api.env</code>. In any other environment (<strong>test</strong>, <strong>dev</strong>, <strong>staging</strong>, etc.), these headers are <em>not checked</em> and any value is accepted.",
    requestHeadersTitle: "Request headers",
    emptyApiKeyReq: "Production only",
    emptySecretReq: "Production only",
    emptyApiKeyDesc: "API key from <code>CLEANUP_API_KEY</code> in api.env",
    emptySecretDesc: "Secret from <code>CLEANUP_SECRET</code> in api.env",
    yes: "Yes",
    requestBodyTitle: "Request body",
    emptySuccessTitle: "200 \u2014 Jobs Deleted",
    emptyUnauthTitle: "Unauthorized",
    methodNotAllowedTitle: "Method Not Allowed",
    emptyEndpoint: "Empty endpoint",
    emptyStatus200: "All jobs were deleted successfully",
    emptyStatus401: "Invalid or missing credentials (production only)",
    emptyStatus405: "Only DELETE method is allowed",
    emptyStatus503: "Solr core is unavailable or environment not configured",
    randomEndpoint: "Random endpoint",
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

    emptyTag: "\u0218terge toate \u00EEnregistr\u0103rile de joburi",
    emptyWarning: "<strong>Aten\u021Bie:</strong> Aceast\u0103 ac\u021Biune \u0219terge PERMANENT toate joburile din baza de date Solr. Nu poate fi anulat\u0103.",
    emptyDesc: "\u0218terge permanent toate documentele din core-ul Solr <code>job</code>. \u00CEnv production necesit\u0103 credentiale API valide.",
    authTitle: "Autentificare",
    emptyAuthDesc: "\u00CEn modul <strong>production</strong> (<code>NODE_ENV=production</code>), trebuie s\u0103 furnizezi headerele <code>X-API-Key</code> \u0219i <code>X-Cleanup-Secret</code> care s\u0103 corespund\u0103 cu <code>api.env</code>. \u00CEn orice alt mediu (<strong>test</strong>, <strong>dev</strong>, <strong>staging</strong>, etc.), aceste headere <em>nu sunt verificate</em> \u0219i orice valoare este acceptat\u0103.",
    requestHeadersTitle: "Headere request",
    emptyApiKeyReq: "Doar production",
    emptySecretReq: "Doar production",
    emptyApiKeyDesc: "Cheia API din <code>CLEANUP_API_KEY</code> din api.env",
    emptySecretDesc: "Secretul din <code>CLEANUP_SECRET</code> din api.env",
    yes: "Da",
    requestBodyTitle: "Corpul requestului",
    emptySuccessTitle: "200 \u2014 Joburi \u0218terse",
    emptyUnauthTitle: "Neautorizat",
    methodNotAllowedTitle: "Metod\u0103 nepermis\u0103",
    emptyEndpoint: "Endpoint golire",
    emptyStatus200: "Toate joburile au fost \u0219terse cu succes",
    emptyStatus401: "Credentiale invalide sau lips\u0103 (doar production)",
    emptyStatus405: "Doar metoda DELETE este permis\u0103",
    emptyStatus503: "Core-ul Solr este indisponibil sau mediul nu este configurat",
    randomEndpoint: "Endpoint aleator",
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

const saved = localStorage.getItem('peviitor-lang');
if (saved === 'ro') setLang('ro');
</script>
</body>
</html>
