# v7 company panel (stub): email link → API key → post jobs

Date: 2026-10-18
Branch: `api-keys`
Status: implemented (stub), see `v7/readme.md`

## Goal

Companies (third parties) that want to post jobs to peviitor through `/v7/jobs/` must be
able to get their own API key, without a peviitor admin running `devops/apikeys/manage.php`.

Target flow (final product):

1. An **external system** authorizes an email for a company (CIF).
2. On a page in this repo the user enters that email.
3. They receive a single-use link by email.
4. The link opens a panel where they generate (and revoke) API keys for their company.
5. With a key they post jobs to `/v7/jobs/` (already implemented on this branch).

This spec covers a **stub** of that flow: everything works end to end inside this repo,
but the external system and email sending are simulated. The stub is built so that the
real integrations replace one function each, without touching the pages or the key logic.

## What already exists (this branch)

- `v7/lib/apikey.php` (was `util/apikey.php`): key format `pv_live_<48 hex>`, SHA-256 hash as Solr id in core
  `apikeys`, 16-char display prefix, `requireCompanyApiKey()` for `X-Api-Key`.
- `devops/apikeys/manage.php`: CLI `create | list | revoke`.
- `v7/jobs/index.php`: GET/POST/PUT/DELETE of a company's own jobs.
- The root `index.php` (documentation page) is the visual reference: single PHP file,
  inline CSS, Inter font, cream gradient background, accent `#c44536`.

## Out of scope (known limitations, kept in `v7/readme.md`)

- Real email sending (SMTP or provider).
- Real integration with the external system that authorizes emails.
- Expiry of jobs posted through v7 (they stay active until deleted).
- Rate limiting on `/v7/jobs/`.
- Protecting company-posted jobs from being overwritten by v1 scraper uploads.
- `v0|v1|v6/firme/email/add` accept unauthenticated writes; the panel must therefore
  **never** treat emails in the `firme`/`company` cores as authorizations.
- Terms of service / GDPR acceptance when a key is created.
- Test keys (`pv_test_`) that validate jobs without publishing them.
- What happens to existing keys when the external system withdraws an email
  (stub: keys stay, they belong to the company; only panel access is lost).

## User flow (stub)

### 1. `/v7/panel/` — request a link

- GET: form with one field, `email`, plus a CSRF token.
- POST:
  1. Validate CSRF and email format (`FILTER_VALIDATE_EMAIL`, max 254 chars), lowercase it.
  2. Apply rate limits (see Security). Over the limit: same generic message, nothing created,
     `error_log` entry.
  3. `findAuthorizedEmail($email)`. If authorized: create a login token, build the link
     `<base>/v7/panel/login.php?token=<token>`.
  4. Always show the same message: "If this email is authorized, a login link has been sent."
  5. Delivery of the link:
     - `PANEL_LINK_STUB=1` in `api.env`: the link is also shown on the page, in a box
       clearly labeled "STUB — in production this link is sent by email".
     - Otherwise: the link is written only to the server log
       (`PANEL LINK: email=... link=...`) until email sending exists.
     The link is shown only when the email is authorized; with the stub flag on, email
     enumeration is accepted (stub environments only).

### 2. `/v7/panel/login.php?token=…` — confirm

- GET: if the token format is valid, show a "Enter the panel" button (POST form with the
  token and a CSRF token). The token is **not** consumed on GET, so link scanners in email
  clients cannot burn it.
- POST: verify the token (exists, not expired, not used, its email still authorized).
  - Valid: mark it used (`used_at`), `session_regenerate_id(true)`, store in the session
    `email`, `cif`, `company`, `login_at`; redirect to `keys.php`.
  - Invalid / expired / used: "This link is invalid or expired" + link back to `/v7/panel/`.

### 3. `/v7/panel/keys.php` — the panel

Requires a session; without one, redirect to `/v7/panel/`. On every request the email's
authorization is re-checked; if it is no longer active, the session is destroyed.

Shows:

- Company name, CIF, logged-in email.
- Table of the company's keys: prefix, state (active/revoked), created_at, created_by,
  revoked_at.
- Request examples (curl for GET/POST/PUT/DELETE) and a Postman collection download
  (`postman.php`, session required), both built from `v7/lib/examples.php`. They never contain
  a key (`$PEVIITOR_API_KEY` / `{{apiKey}}`); the new key is shown only in its own box, with a
  warning to store it safely.
- **Generate key** (POST + CSRF). Refused when the company already has an active key:
  one active key per company, the current one must be revoked first.
  The new key is shown **once**, with a copy button, a warning that it will not be shown
  again, and a ready-to-use `curl` example for `POST /v7/jobs/`.
- **Revoke** next to each active key (POST + CSRF + JS confirm).
- **Log out** (POST + CSRF): destroys the session.
- Link to the v7 documentation (`v7/readme.md` content or its section in `index.php`).

## Data model (Solr)

Everything for this feature is in **one collection, `apikeys`** (the one created for the
keys on 26.09), with a `type` field. The legacy `auth` core (md5 keys of v0/v3/v4) is a
different system and is not used.

| Field | Type | `key` | `authorization` | `token` |
|---|---|---|---|---|
| `id` | string | sha256(key) | email, lowercased | sha256(token) |
| `type` | string | `key` | `authorization` | `token` |
| `prefix` | string | first 16 chars of the key | — | — |
| `cif` | string | company CIF | company CIF | — |
| `company` | string | company name | company name | — |
| `active` | boolean | false once revoked | false once deauthorized | — |
| `created_at` | pdate | yes | yes | yes |
| `created_by` | string | panel email or `cli` | — | — |
| `revoked_at` | pdate | when revoked | — | — |
| `note` | string | optional (CLI) | — | — |
| `email` | string | — | same as id | email the token was issued for |
| `ip` | string | — | — | requester IP |
| `expires_at` | pdate | — | — | created_at + 15 min |
| `used_at` | pdate | — | — | set when consumed |

- Every query filters on `type`, and a key is accepted only if its doc has `type: key`.
- One email authorizes exactly one CIF in the stub (the id is the email).
- Login token: `bin2hex(random_bytes(32))` (64 hex chars); only its SHA-256 is stored.
- Key docs created before the `type` field get `type: key` from `setup-solr.sh`.

## Code structure

Everything for this feature lives under `v7/` (code shared by v7 in `v7/lib/`, denied by
`.htaccess`; specs in `v7/docs/specs/`), plus the admin tools in `devops/apikeys/`.
The title/location duplicate detection that existed earlier on this branch was removed;
only the existing-url check stays (it prevents overwriting other documents).

### `v7/lib/panel.php` (new)

Pure functions where possible, so they are unit-testable without Solr:

- `generateLoginToken(): string`, `isValidLoginTokenFormat(string): bool`
- `isTokenDocUsable(?array $doc, int $now): bool` — exists, type token, not expired, not used
- `findAuthorizedEmail(string $solrBase, $user, $pass, string $email): ?array` —
  returns `['email','cif','company']` for an active authorization, else null.
  **The single seam for the external-system integration.**
- `createLoginToken(...)`, `consumeLoginToken(...)` (sets `used_at`, using `_version_`
  so two parallel POSTs cannot both consume it)
- `countRecentTokens(..., string $field, string $value, int $sinceSeconds): int` and
  `isOverRateLimit(int $countEmail, int $countIp): bool`
- Session/CSRF helpers: `panelStartSession()`, `panelCsrfToken()`, `panelRequireCsrf()`,
  `panelLogin()`, `panelCurrentUser(...)`, `panelLogout()`
- `deliverLoginLink(string $email, string $link): ?string` — returns the link to display
  when `PANEL_LINK_STUB=1`, otherwise logs it and returns null.
  **The single seam for real email sending.**

### `v7/lib/apikey.php` (extended)

Move key creation/listing/revocation out of `manage.php` so CLI and panel share them:

- `createApiKey($solrBase, $user, $pass, string $cif, string $company, string $createdBy, ?string $note = null): string`
- `listApiKeys($solrBase, $user, $pass, ?string $cif): array`
- `revokeApiKey($solrBase, $user, $pass, string $prefix, ?string $requiredCif = null): array` —
  with `$requiredCif`, refuses keys of another company (panel); CLI passes null.
- `countActiveApiKeys(array $docs): int`; constant `MAX_ACTIVE_KEYS_PER_COMPANY = 1`
  (enforced in the panel and in `manage.php create`).

### `devops/apikeys/manage.php` (extended)

- `create | list | revoke` reuse the functions above (`created_by: cli`).
- New: `authorize --email=... --cif=... [--company=...] [--force]` — same CIF checks as
  `create`; writes/overwrites an `authorization` doc with `active: true`.
- New: `deauthorize --email=...` — sets `active: false`.
- New: `authorizations [--cif=...]` — lists them.

### Pages (new)

- `v7/panel/index.php`, `v7/panel/login.php`, `v7/panel/keys.php`
- Shared setup and layout in `v7/panel/bootstrap.php` (header with logo, inline CSS in the style of
  the root `index.php`, footer), so the three pages stay small.
- All output escaped with `htmlspecialchars`.
- Headers: `Content-Type: text/html; charset=UTF-8`, `X-Frame-Options: DENY`,
  `Referrer-Policy: no-referrer` (the token is in the login URL),
  `Cache-Control: no-store`.

## Security

- Session cookie: `HttpOnly`, `SameSite=Strict`, `Secure` when the request is HTTPS,
  name `pv_panel`; session lifetime 1 hour from login, checked server-side
  (`login_at`), not only by cookie expiry.
- CSRF token per session, required on every POST, compared with `hash_equals`.
- Rate limits on link requests, counted from `token` docs in `apikeys`:
  max 5 per email per hour, max 20 per IP per hour. Tokens are only created for
  authorized emails, so the per-IP limit also counts refused attempts via an
  `error_log` line only — acceptable for the stub.
- Login token: 15 min validity, single use, stored hashed, consumed only on POST.
- Panel actions always use the CIF from the session, never from the form.
- One active key per company (revoke before generating a new one).
- Paths under `v7/panel/` are the only new public surface; `manage.php` stays CLI-only.

## Configuration

`api.env`:

- `PANEL_LINK_STUB=1` — show the login link on the page (stub / local / dev only).
- `PANEL_BASE_URL` — optional, base used to build the login link
  (default: scheme + host of the current request).

Production setup: the `apikeys` collection with the fields above and `source` in `job`,
both created by `devops/apikeys/setup-solr.sh`.

## Testing

Unit (`tests/unit/TestPanel.php`, extend `TestApiKey.php`):

- token generation format/uniqueness, format check
- `isTokenDocUsable`: valid, expired, used, wrong type, missing
- `isOverRateLimit` boundaries (5/20)
- `countActiveKeys`, CSRF compare

Integration (`tests/integration/TestV7Panel.php`, via `mock-handler.php`):

- request link: authorized email (stub shows link), unauthorized email (same message, no link),
  over rate limit (same message, no link), missing CSRF (400)
- login GET does not consume; POST with valid token → redirect + session cookie;
  expired / used / unknown token → error page
- `keys.php` without session → redirect to `/v7/panel/`
- generate key: shown once, starts with `pv_live_`; 4th active key refused
- revoke own key; revoke a key of another CIF → refused
- deauthorized email with an open session → session ends

If `tests/integration/http-helpers.php` has no cookie support, add it (store `Set-Cookie`,
send `Cookie`, don't follow redirects).

## Integration later (what changes)

- External system: replace `findAuthorizedEmail()` (call their API, or have them write
  `authorization` docs through a protected endpoint). Pages and key code unchanged.
- Email sending: replace `deliverLoginLink()`; turn `PANEL_LINK_STUB` off.
