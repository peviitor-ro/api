# Random Job Endpoint

**Route:** `GET /v1/random/`

## Description

Returns a single random job from the Solr `job` core. Useful for discovery, widgets, or "job of the day" features.

## How It Works

1. Queries Solr with `q=*:*&rows=0` to get the total number of indexed jobs (`numFound`).
2. Picks a random offset: `start = rand(0, numFound - 1)`.
3. Fetches one document at that offset: `q=*:*&rows=1&start={random}&omitHeader=true`.
4. Returns the job fields mapped to the peviitor Job Model Schema.

## Response Format

### Success (200)

```json
{
  "title": "Inginer IT",
  "company": "COMPANY SRL",
  "location": ["București", "Cluj-Napoca"],
  "workmode": "remote",
  "url": "https://example.com/job/123",
  "salary": "5000-8000 RON",
  "tags": ["python", "java"],
  "cif": "12345678",
  "date": "2026-06-15T10:00:00Z",
  "status": "published"
}
```

### No Jobs Found (404)

```json
{
  "error": "No jobs found"
}
```

### Service Unavailable (503)

```json
{
  "error": "Job core unavailable",
  "details": "PROD_SERVER not set"
}
```

## Response Fields

| Field      | Type     | Description                                      |
|------------|----------|--------------------------------------------------|
| title      | string   | Job title                                        |
| company    | string   | Company name (uppercase)                         |
| location   | string[] | Array of cities                                  |
| workmode   | string   | "remote", "on-site", or "hybrid"                 |
| url        | string   | Full URL to the job detail page                  |
| salary     | string   | Salary interval with currency (e.g. "5000-8000 RON") |
| tags       | string[] | Array of skill tags                              |
| cif        | string   | CIF/CUI                                          |
| date       | string   | ISO8601 UTC timestamp                            |
| status     | string   | "scraped", "tested", "published", or "verified"  |

## Requirements

- **Method:** `GET` only
- **Environment variables:**
  - `PROD_SERVER` — Solr server host
  - `SOLR_USER` — Solr basic auth username
  - `SOLR_PASS` — Solr basic auth password
- **Dependencies:** `util/loadEnv.php`
