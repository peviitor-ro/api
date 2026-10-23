# v7 — company-managed jobs with API keys

Companies publish and manage their own jobs on peviitor.ro through an API key.
A key belongs to exactly one company (CIF); the company can only see, change and
delete the jobs it posted through v7, never scraped jobs or other companies' jobs.

## Authentication

Every request needs the header:

```
X-Api-Key: pv_live_<48 hex characters>
```

- Companies create and revoke their own keys in the [company panel](#company-panel-v7panel);
  an admin can also manage them from the CLI (see [Admin: managing keys](#admin-managing-keys)).
- **One active key per company.** To get a new key, revoke the current one first (panel and CLI);
  integrations using the old key stop working at that moment.
- Only the SHA-256 hash of a key is stored (Solr core `apikeys`); the key itself is
  shown once, at creation. Logs contain only the 16-character prefix (`pv_live_3f9a0c1d`).
- The company name and CIF of a job always come from the key, never from the request body.

## Endpoint: `/v7/jobs/`

| Method | Body / query | Result |
|---|---|---|
| `GET` | — | Jobs this company posted through v7 |
| `POST` | one job, an array of jobs, or `{"jobs": [...]}` (max 100) | `201`, jobs saved |
| `PUT` | `{"url": ..., <fields to change>}` | `200`, updated job |
| `DELETE` | `?url=...` or `{"url": ...}` | `200`, job deleted |

A job is identified by its `url`, the unique key of the `job` core (the same as for scraped jobs).

Job fields:

| Field | Required | Notes |
|---|---|---|
| `url` | yes | `http(s)` link to the job on the company site, max 1000 chars |
| `title` | yes (POST) | max 200 chars |
| `location` | yes (POST) | string or array of strings, e.g. `["Cluj-Napoca", "Remote"]` |
| `workmode` | no | `on-site`, `remote` or `hybrid` |
| `tags` | no | array of max 20 strings |

v7 sets `company`, `cif`, `date`, `status: "activ"` and `source: "company"` itself.

### Example

```bash
curl -X POST https://api.peviitor.ro/v7/jobs/ \
  -H "X-Api-Key: pv_live_..." -H "Content-Type: application/json" \
  -d '{"url":"https://firma.ro/cariere/java-dev","title":"Java Developer","location":["Cluj-Napoca"],"workmode":"hybrid"}'
```

### Urls already on peviitor are refused

POST is **all-or-nothing**: if any job in the request is invalid (`400`) or conflicts (`409`),
nothing is saved. The response lists every problem:

```json
{
  "error": "Some jobs already exist on peviitor, nothing was saved",
  "conflicts": [
    {"index": 0, "url": "https://firma.ro/jobs/7", "reason": "already_scraped"}
  ]
}
```

| `reason` | Meaning |
|---|---|
| `already_scraped` | peviitor already has this url from scraping |
| `already_posted_use_put` | the company already posted this url; use `PUT` to change it |
| `url_belongs_to_another_company` | the url is registered for another CIF |
| `created_concurrently` | another request or a scraper created this url between the check and the write; retry or use `PUT` |

The existing-url check is also enforced by Solr: new jobs are written with `_version_: -1`
("create only"), so two simultaneous POSTs of the same url, or a POST racing a scraper, can
never overwrite a job. If Solr refuses one job of a batch, the jobs this request already wrote
are deleted again (matched by url, CIF and the request's exact `date`), so POST stays
all-or-nothing.

Only the **same url** counts as the same job. A job that a scraper finds under another url
(e.g. a tracking link or a job board) is a different document.

### Other responses

`401` missing/invalid/revoked key · `403` job not owned by this key or not posted through v7 ·
`404` job not found · `405` method not allowed · `409 concurrent_update` the job changed
meanwhile, retry · `415` body is not JSON · `503` Solr unavailable (details only in the server log).

## Company panel: `/v7/panel/`

Stub of the onboarding flow (design: [`docs/specs/api-key-companies-spec.md`](docs/specs/api-key-companies-spec.md)).

1. An email is **authorized** for a company (CIF). Later an external system will do this;
   for now an admin runs `manage.php authorize` (see below).
2. `/v7/panel/`: the user enters the email and gets a **single-use login link**, valid 15
   minutes. The page answers the same whether or not the email is authorized.
3. `/v7/panel/login.php?token=...`: a button logs them in (the token is consumed on POST
   only, so link scanners in email clients cannot use it up).
4. `/v7/panel/keys.php`: list, **generate** (the key is shown once, with a copy button and a
   warning to store it safely) and **revoke** the company's keys; log out. The "Cum folosești
   cheia" card has `curl` examples for GET/POST/PUT/DELETE and a **Postman download**
   (`/v7/panel/postman.php`, logged-in only). Neither ever contains a key: curl reads it from
   `$PEVIITOR_API_KEY`, Postman from the collection variable `apiKey`.

Stub behaviour:

- **No email is sent yet.** With `PANEL_LINK_STUB=1` in `api.env` the link is shown on the
  page (local/dev only); otherwise it is only written to the server log.
- Replace `findAuthorizedEmail()` (external system) and `deliverLoginLink()` (email
  sending) in `v7/lib/panel.php` for the real integration; nothing else needs to change.

Security: tokens and keys stored only as SHA-256; session cookie `pv_panel` is
`HttpOnly`, `SameSite=Strict` (and `Secure` on HTTPS), 1 hour; CSRF token on every form;
max 5 links per email and 20 per IP per hour; the company always comes from the session;
an email that loses its authorization is logged out on its next request (its company's
keys stay active). Emails in the `firme`/`company` cores are **not** authorizations
(`firme/email/add` is unauthenticated).

Optional `api.env`: `PANEL_BASE_URL` (base of the login link, default: the current host).

## Admin: managing keys

Keys are managed from the command line with `devops/apikeys/manage.php`, on an admin's
machine, with an `api.env` holding the Solr credentials. Whoever can run it already has
write access to Solr, so there is no separate admin secret and no admin endpoint on the web
(the script refuses to run over HTTP and its folder is denied by `.htaccess`).

```bash
php devops/apikeys/manage.php create --cif=12345678 --note="hr@firma.ro, ticket #12"
php devops/apikeys/manage.php list [--cif=12345678]
php devops/apikeys/manage.php revoke --prefix=pv_live_3f9a0c1d

# panel access (stub for the external system)
php devops/apikeys/manage.php authorize --email=hr@firma.ro --cif=12345678
php devops/apikeys/manage.php deauthorize --email=hr@firma.ro
php devops/apikeys/manage.php authorizations [--cif=12345678]
```

- `create` refuses when the company already has an active key (revoke it first), checks that the CIF exists in the `company` core and is not `inactiv`
  (override with `--force --company="Name"`), and prints the key **once**.
  Verify that the requester really represents the company before creating a key, and
  send the key over a private channel.
- `revoke` takes effect immediately.
- `authorize` does the same CIF checks as `create`. `list` shows who created each key
  (`created_by`: the panel email, or `cli`).
- `--env=path/to/api.env` selects another env file.

## Production setup (maintainers)

1. Run `devops/apikeys/setup-solr.sh` (idempotent, SolrCloud):
   ```bash
   SOLR_URL=http://<server>:8983/solr SOLR_USER=... SOLR_PASS=... bash devops/apikeys/setup-solr.sh
   ```
   It creates the collection `apikeys` (`type`, `prefix`, `cif`, `company`, `created_by`,
   `note`, `email`, `ip` string; `active` boolean; `created_at`, `revoked_at`, `expires_at`,
   `used_at` pdate), sets `type: key` on key docs created before that field existed, and
   adds the field `source` (string) to `job`.

   One collection holds the whole feature, told apart by `type`: `key` (API keys),
   `authorization` (emails allowed into the panel) and `token` (login links). The legacy
   `auth` core (md5 keys of v0/v3/v4) is a different system and is not used.
2. Do **not** set `PANEL_LINK_STUB=1` in production.
3. No new secret or change to the deploy workflows is needed.

## Local setup

With the [local environment](https://github.com/peviitor-ro/local_environment) running,
run `setup-solr.sh` against `http://localhost:8983/solr`, add `PANEL_LINK_STUB=1` to `api.env`, then:

```bash
docker exec apache-container php /var/www/html/api/devops/apikeys/manage.php authorize --email=you@test.local --cif=<a CIF from the company core>
```

and open <http://localhost:8081/api/v7/panel/>.

## Known limitations

Planned work, including what must change outside v7 (v1 endpoints), is tracked in
[`docs/follow-ups.md`](docs/follow-ups.md).


- **Scrapers can overwrite company jobs.** v1 scraper uploads replace a document with the
  same url, which drops `source: "company"`. Protecting company jobs needs a change in
  `v1/scraper/jobs/upload` and `v1/add` (separate PR).
- **No rate limiting** per key or per IP yet.
- Panel: no real email sending and no integration with the external system yet (stub);
  jobs posted through v7 never expire; no terms-of-service acceptance; no test keys.

## Postman

There are two sets of Postman files:

| For | Where | What |
|---|---|---|
| **Companies** | downloaded from the panel (generated by `v7/panel/postman.php` from `v7/lib/examples.php`, like the curl examples) | one collection with the 4 examples (GET, POST, PUT, DELETE), no tests; collection variables `baseUrl` (set) and `apiKey` (empty, the company pastes its key or makes its own environment) |
| **Maintainers** | `v7/docs/postman/` in the repo | the test collection below, with an environment for the local stack |

For maintainers, `v7/docs/postman/` has a test collection for `/v7/jobs/` (21 requests with tests:
authentication, list, add, batch, conflicts, validation, update, delete, other companies' and
scraped jobs) and an environment for the local stack.

1. Import `v7-jobs.postman_collection.json` and `v7-local.postman_environment.json`, select
   the environment (`baseUrl` = `http://localhost:8081/api`).
2. Generate a key in the panel and paste it into the environment variable `apiKey`. The
   collection sends it as `X-Api-Key` from its **Authorization** tab; the 401 requests override
   it (no key, a malformed key, an unknown key).
3. Run the collection in order (Collection Runner): requests pass job urls to each other via
   collection variables; the jobs created (`https://firma-test.ro/cariere/postman-...`) are
   deleted in folder 5. DELETE removes one job per request (the url in the query or the body).
4. Folder 6 creates nothing: its PUT/DELETE requests use example urls written in the body
   (another company's job, a scraped job of your company). Replace them with existing urls to
   get `403`; with urls that don't exist you get `404`. Either way the request never succeeds.

## Code layout

| Path | What |
|---|---|
| `v7/jobs/index.php` | the jobs endpoint |
| `v7/panel/` | panel pages (`bootstrap.php` holds the shared layout) |
| `v7/lib/apikey.php` | key generation, hashing, create/list/revoke, `X-Api-Key` check |
| `v7/lib/panel.php` | login tokens, authorizations, rate limit, session, CSRF |
| `v7/lib/examples.php` | request examples for companies (curl on the page, Postman download) |
| `v7/docs/specs/` | design specs |
| `v7/docs/postman/` | Postman collection and local environment |
| `v7/docs/follow-ups.md` | work left for later (incl. v1) |
| `devops/apikeys/` | admin CLI and Solr setup (not web-reachable) |

`v7/lib/` is denied by `.htaccess`.

## Tests

`tests/unit/TestApiKey.php`, `tests/unit/TestPanel.php`, `tests/unit/TestExamples.php`, `tests/integration/TestV7Jobs.php`,
`tests/integration/TestV7Panel.php` (run with `bash tests/run.sh`). The Solr mock keeps the
`apikeys` core in a temp file so the panel flow can be tested end to end.
