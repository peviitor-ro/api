# v7 — follow-ups

Work deliberately left for later. One line per item: what, why it matters, where.
When an item is done, delete it here and mention it in the PR.

## Outside v7 (adapt later)

| Item | Why | Where |
|---|---|---|
| Require authentication on the v1 job upload endpoints | `/v1/scraper/jobs/upload/` accepts writes from anyone, with any `url`, `company` and `cif`, and overwrites the job with the same `url` | `v1/scraper/jobs/upload/index.php`, `v1/add/index.php` |
| Stop v1 from overwriting company jobs | v1 writes with `overwrite=true` and drops `source: "company"`, so the company can no longer change or delete its job through `/v7/jobs/`. Fix: skip docs with `source: company`, or write with `_version_: -1` like v7 | same files |
| City filter only matches the exact city name | the site sends `city=Cluj,Cluj*` (prefix search), but `v1/search` quotes it (`location:"Cluj*"`) so `*` is literal and `location` is an exact string field: "Cluj" finds nothing, "Cluj-Napoca" works | `v1/search/index.php` (`buildSolrQuery`) |
| The site sometimes drops the city filter | after a search with a city it also sends the same search without the city; if that answer arrives last, the unfiltered results are shown | frontend (search-engine repo) |
| The current frontend build reads v0 (`jobs` core) | jobs posted through v1/v7 go to the `job` core, so they would not appear on a site that searches through v0; check which API the live site uses before launch | frontend config |
| Secure `firme/email/add` | it accepts unauthenticated writes, so emails in the `firme`/`company` cores must never be trusted as panel authorizations (v7 does not use them) | `v0`, `v1`, `v6` `/firme/email/add/` |

## v7 stub → production

| Item | Why | Where |
|---|---|---|
| Send the login link by email | today it is shown on the page (`PANEL_LINK_STUB=1`, local only) or written to the server log | `deliverLoginLink()` in `v7/lib/panel.php` |
| Stop logging the login link once email works | the log line contains a live single-use token | same function |
| Make `PANEL_BASE_URL` mandatory before sending emails | without it, the link is built from the request's `Host` header (link poisoning once it is emailed) | `panelUrl()` in `v7/lib/panel.php` |
| Integrate the external system that authorizes emails | today an admin runs `manage.php authorize` | `findAuthorizedEmail()` in `v7/lib/panel.php` |
| Decide what happens to keys when an email loses its authorization | today the company's keys stay active | `v7/lib/panel.php`, `manage.php deauthorize` |

## v7 improvements

| Item | Why | Where |
|---|---|---|
| Rate limiting on `/v7/jobs/` | there is no limit per key or per IP yet | `v7/jobs/index.php` |
| Expiry of jobs posted through v7 | they stay active until the company deletes them | `v7/jobs/index.php` + a cleanup job |
| Terms of service / GDPR acceptance when a key is created | not recorded today | `v7/panel/keys.php` |
| Test keys (`pv_test_`) | to validate jobs without publishing them | `v7/lib/apikey.php`, `v7/jobs/index.php` |
| Same job found by a scraper under another url | only the same `url` counts as the same job, so the scraped copy is a second document (the title/location matching was removed on purpose) | `v7/jobs/index.php` |
| Jobs without their own url | `url` is mandatory (candidates apply there); hosting jobs on peviitor would need a detail page in the frontend | product decision |
