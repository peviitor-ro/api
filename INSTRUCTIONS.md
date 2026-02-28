# API Project - peviitor.ro

## Overview
This is the source code for **api.peviitor.ro**, a REST API for a Romanian job board website.

**Technology Stack:**
- PHP backend
- Apache Solr (search/database engine)
- GitHub Actions for CI/CD

## Project Structure

```
api/
├── v0/          # Jobs & Companies API (TEST environment)
├── v1/          # (empty - to be developed for PROD)
├── v3/          # Extended version with logo/images
├── v4/          # Download-focused version
├── v5/          # Simplified jobs API
├── v6/          # Companies (firme) API - query/search focused
├── util/        # Shared utilities (solr, auth, loadEnv)
├── testing/     # Performance testing (JMeter reports)
├── devops/      # DevOps scripts (Solr)
├── .github/     # GitHub workflows (deploy, sonar, scorecard)
└── humans.txt   # Credits file
```

## API Versions

### v0 (TEST)
**Jobs endpoints:**
- `GET /search/` - Search jobs (Solr)
- `GET /suggest/` - Job title suggestions
- `GET /random/` - Get random job
- `PUT /update/` - Add/update job
- `DELETE /cleanjobs/` - Delete jobs by company
- `DELETE /empty/` - Delete all jobs
- `GET /companies/` - List companies
- `GET /total/` - Get total jobs/companies count
- `GET /jobs/` - Get jobs with pagination
- `GET /getuser/` - Get user by ID
- `PATCH /updateuser/` - Update user

**Companies (firme) endpoints:**
- `/firme/company/` - POST (update name), PUT (add), DELETE
- `/firme/brand/` - GET, POST (add), DELETE
- `/firme/email/` - GET, POST (add), DELETE
- `/firme/phone/` - GET, POST (add), DELETE
- `/firme/logo/` - GET, POST (add), DELETE
- `/firme/website/` - GET, POST (add), DELETE
- `/firme/scraper/` - GET, POST (add), DELETE

### v1 (PROD - empty, to be developed)

### v3
Similar to v0 plus:
- `/logo/` - Logo endpoints
- `/images/` - Images endpoint

### v4
Download-focused:
- `/jobs/`, `/companies/`, `/search/`, `/suggest/`, `/random/`
- `/clean/`, `/total/`, `/update/`, `/delete/`, `/fetchai/`

### v5
Simplified jobs API:
- `/add/`, `/update/`, `/delete/`, `/random/`, `/get_token/`

### v6
Companies (firme) API with query capabilities:
- `/firme/company/`, `/firme/brand/`, `/firme/email/`, `/firme/phone/`
- `/firme/logo/`, `/firme/website/`, `/firme/scraper/`, `/firme/qsearch/`, `/firme/search/`

## Running Locally

See: https://github.com/peviitor-ro/local_environment/

## Testing

- JMeter performance tests in `testing/jmeter/`
- Reports: PDF files in `testing/`

## CI/CD

- `.github/workflows/deploy_api.yml` - Auto-deploy to server
- `.github/workflows/sonar.yml` - SonarQube code analysis
- `.github/workflows/scorecard.yml` - Security scorecard

## Shared Utilities

Located in `util/`:
- `solr.php` - Solr connection handling
- `auth.php` - Authentication
- `loadEnv.php` - Environment variable loading
