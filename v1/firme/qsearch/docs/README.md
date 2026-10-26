# Company name search and import

`GET /v1/firme/qsearch/?q=zira` imports companies from CUIscan into the existing
Solr `company` core, then returns matching database companies. No API key is
required, consistent with the previous qsearch endpoint. This GET writes data;
responses use `Cache-Control: no-store`.

## Source capabilities and limitation

Checked on 2026-10-26 against CUIscan's [official API documentation](https://cuiscan.ro/api-docs.html)
and [terms](https://cuiscan.ro/termeni.html). The documented public search is
`GET https://cuiscan.ro/api.php?action=search&q=zira`, without registration or
an API key. It supplies name, CUI, locality and county in one response; no
per-company detail request or HTML scraping is needed for these fields.

**The documentation says 30 companies, but a live read-only search for `software`
returned 50 on 2026-10-26. No pagination is documented.** This implementation
imports all valid name matches returned by the request, including responses
above 30. It does not invent page parameters, scrape the website, or promise
exhaustive results. At 30 or more rows, `import.possibly_truncated` is true and
a warning explains the uncertainty. For compatibility, `source_limit: 30`
records the documented threshold, not an enforced cap or a verified live limit.
A response below 30 is not independent verification of dataset completeness.

For exhaustive imports, ask the provider for a documented paginated search or
bulk data agreement, then extend `CuiscanClient`. A licensed ONRC dataset is
another option after verifying access and redistribution terms.

The provider documents 100 requests/hour/IP, 10/second, and recommends at least
500ms between calls. A file lock and rolling request budget enforce 100/hour
and 500ms spacing across PHP workers for this checkout. The limiter needs a
writable PHP temporary directory. Multiple hosts/checkouts sharing an outbound
IP need a shared quota service or provider-approved higher limits; this local
limiter cannot coordinate with other applications. Upstream 429 is respected
without automatic retries.

## Existing architecture and workflow

Previously, `index.php` only queried Solr and returned `pagination` and `docs`.
Company add/search endpoints use `SOLR_SERVER`, `PROTOCOL`, `SOLR_USER`, and
`SOLR_PASS` from `api.env`. Company documents use CIF/CUI as their unique `id`,
with `company` and a multi-valued `location` field. There is no separate SQL
company database in this flow.

1. Reject non-GET requests and invalid input before configuration or network access.
2. Trim `q`, require 2–100 UTF-8 characters, reject control characters and arrays.
   PHP has already URL-decoded the input; do not decode it a second time.
3. Reserve a provider request slot and call the documented search over HTTPS.
   URL parameters are encoded, redirects are disabled, and the response is bounded
   to 2 MiB with an 8-second stream connection/read timeout. PHP needs HTTPS stream
   support (OpenSSL, trusted CA certificates) and `allow_url_fopen` enabled.
4. Validate all provider rows before writing. Normalize CUIs (remove `RO` and
   leading zeros), deduplicate within the response, and retain literal company
   name matches. Map `name` to `company` and locality/county to `location`.
5. Use Solr real-time `/get` to detect existing IDs, including uncommitted writes.
   Skip existing records entirely, preserving names, brands, websites and other
   metadata. Create new records with `_version_=-1`; a concurrent create returns
   409 and is counted as existing. No delete or replacement requests are sent.
6. Commit each successful create so the subsequent search sees the new document.
   Search company names in Solr with escaped query characters. Single words use
   wildcard terms; names containing spaces use ordered wildcard phrases so the
   query matches adjacent indexed words, including partial boundary words.
7. Return the existing `docs`/`pagination` structure plus `import`. Solr internal
   fields are removed. Local `page`/`rows` paginate the database response only;
   importing happens before that pagination.

Other endpoints and shared utilities are unchanged. The existing `loadEnv`
helper is reused. The existing HTTP utilities do not expose status codes needed
to distinguish 409/429 or bound redirects/response size, so the feature has a
separate namespaced transport. The library is blocked from direct Apache access.

## Request and response

`q` is required. `page` defaults to 1; `rows` defaults to 10 and is capped at 100.
Zero becomes 1, as before. Non-numeric or array pagination values return 400.

Example (illustrative counts):

```json
{
  "pagination": {"page": 1, "rows": 10, "total": 2, "total_pages": 1},
  "docs": [{"id": "88000001", "company": "ZIRA TEST SRL", "location": ["Cluj-Napoca, Cluj"]}],
  "import": {
    "source": "cuiscan.ro", "received": 3, "matched": 2,
    "imported": 1, "existing": 1, "source_limit": 30,
    "source_pagination_supported": false, "possibly_truncated": false
  }
}
```

`source_limit` is the documented warning threshold, not an import cap.
`received` counts provider rows, `matched` counts unique valid name matches,
`imported` counts acknowledged creates, and `existing` counts skipped IDs or
concurrent-create conflicts. Existing local companies can appear in `docs` even
when the source returns no results. With no local or source matches, `docs` is
empty and the total is zero. Source and database pagination are separate.

## Safe testing and Postman

From the repository root, with PHP 8.1+ and Postman CLI 1.70+ available:

```sh
php tests/unit/TestCompanyQsearch.php
php tests/integration/TestCompanyQsearch.php --postman
```

The second command starts the actual endpoint in a temporary directory with a
temporary `api.env`, a local Solr stand-in, and a Postman CUIscan simulation.
It exercises real HTTP requests, checks failures and runs the saved collection.
It never reads production credentials or sends a request to a production service.
Logs and the fixture store remain in the printed temporary directory for diagnosis.
Servers are stopped when the runner exits. No Postman cloud publishing is needed.

For manual Postman requests:

1. Run `php tests/integration/TestCompanyQsearch.php --serve` and leave it running.
2. Import `qsearch.postman_collection.json` into Postman.
3. Set the collection's `baseUrl` to the exact localhost address printed by the
   runner. The saved default intentionally has an invalid port to avoid accidental
   imports into another running API. No authorization header is needed.
4. Send **Search and import zira**, which calls
   `{{baseUrl}}/v1/firme/qsearch/?q=zira`. Expect HTTP 200, two local matching
   companies, one new import, one existing company, and three provider rows
   (including a duplicate CUI). Names/CUIs in this sandbox are synthetic fixtures.
5. Send **Repeat zira safely**. Expect `imported: 0`, `existing: 2`.
6. Try the empty, invalid-input, upstream-failure and rate-limit requests in the
   collection. All use mock services in this sandbox.
7. Press Enter in the terminal to stop the sandbox.

For an actual integration check, use a separate local/staging company core and
the default CUIscan URL. Its results and counts may differ from the screenshot.
Do not point mock scenarios or synthetic fixture data at a production core.
`CUISCAN_API_URL` in `api.env` is an operator-controlled override for local mocks;
leave it unset for production. Solr schema/indexing compatibility and live
CUIscan access must be verified on staging before deployment.

## Errors and retry behavior

The endpoint is also documented under **Firme** in `v1/swagger.json`, with `zira`
as the example query. Its operation uses the server and v1 base path of the loaded
Swagger document. Restart the sandbox after updating its files: `--serve` prints
the specification URL to load in your Swagger UI. The JSON URL itself is the
OpenAPI specification, not a Swagger UI page. Swagger requests run the import,
so use the sandbox specification URL for synthetic tests. The specification and
Postman collection are maintained alongside the implementation; the collection's
assertions intentionally describe only the sandbox fixtures.

| HTTP | Cause | Handling |
| --- | --- | --- |
| 400 | Missing/invalid q or pagination | JSON error; no external calls or writes |
| 405 | Method other than GET | JSON error and `Allow: GET` |
| 502 | CUIscan unavailable, invalid JSON/schema/company | No companies from this response are written |
| 503 | Local quota/lock or provider 429 | Retry later; no automatic retry |
| 504 | CUIscan timeout | Bounded connection/read wait, JSON error, no import |
| 503 | Solr/configuration failure | JSON error; `import` reports completed work when available |
| 500 | Unexpected internal failure | Generic JSON error; details logged server-side |

Imports are individual creates, not a transaction. If Solr fails mid-import,
earlier successes remain and retry skips them. A write timeout can leave an
unacknowledged create in Solr, so `imported` counts acknowledged writes only;
retry safely resolves its ID through real-time get. Source failures return an
error rather than silently reporting a successful import. No raw upstream
response, server credentials or internal URLs are returned in errors.
