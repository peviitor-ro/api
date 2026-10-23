<?php

/**
 * ============================================================================
 * /v7/jobs/ - Jobs managed by a company with its own API key
 * ============================================================================
 *
 * DESCRIPTION:
 *   Lets a company publish and manage its own jobs. Every request must carry
 *   the company's API key; the company (CIF and name) always comes from the
 *   key, never from the request body, so a company can only touch its own jobs.
 *
 *   A job is identified by its url (the uniqueKey of the 'job' core, same as
 *   for scraped jobs). Posting a url that already exists is refused, so a
 *   company can never overwrite a scraped job or another company's job.
 *
 * DEPENDENCIES:
 *   - Solr cores 'job' (field 'source' must exist) and 'apikeys'
 *   - api.env: SOLR_SERVER, PROTOCOL, SOLR_USER, SOLR_PASS
 *
 * AUTHENTICATION:
 *   Header  X-Api-Key: pv_live_<48 hex>   (created by an admin with devops/apikeys/manage.php)
 *
 * METHODS:
 *   GET     List the jobs this company posted through v7.
 *   POST    Create jobs. Body: one job, an array of jobs, or {"jobs": [...]}; max 100.
 *           Job: { "url": required, "title": required, "location": required (string or array),
 *                  "workmode": "on-site"|"remote"|"hybrid", "tags": [..] }
 *           All-or-nothing: if any job is invalid or conflicts, nothing is written.
 *   PUT     Update one job posted through v7. Body: { "url": required, + fields to change }
 *   DELETE  Delete one job posted through v7. ?url=... or body { "url": ... }
 *
 * RESPONSES:
 *   200 OK / 201 Created
 *   400 Invalid body          401 Missing or invalid API key
 *   403 Job not owned by this key's company, or not posted through v7
 *   404 Job not found         405 Method not allowed
 *   409 Conflict: job already exists / already scraped / changed concurrently
 *   503 Solr unavailable
 *
 * EXAMPLE CURL:
 *   curl -X POST https://api.peviitor.ro/v7/jobs/ \
 *     -H "X-Api-Key: pv_live_..." -H "Content-Type: application/json" \
 *     -d '{"url":"https://firma.ro/cariere/dev-java","title":"Java Developer","location":["Cluj-Napoca"]}'
 * ============================================================================
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Api-Key");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');
require_once __DIR__ . '/../lib/apikey.php';

$SOLR_SERVER = trim(getenv('SOLR_SERVER') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');
$PROTOCOL = trim(getenv('PROTOCOL') ?: '');

// Marks jobs created through v7, as opposed to scraped ones
const JOB_SOURCE_COMPANY = 'company';


/**
 * Validates one job from the request. With $partial (PUT) only url is required.
 * Returns [cleanJob, errors]. Text is stored escaped, like v1/add does.
 */
function validateJobInput(mixed $job, bool $partial): array {
    if (!is_array($job)) {
        return [null, ["job must be an object"]];
    }
    $errors = [];
    $clean = [];

    $url = is_string($job['url'] ?? null) ? trim($job['url']) : '';
    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
        $errors[] = "url must be a valid http(s) link";
    } elseif (strlen($url) > 1000) {
        $errors[] = "url too long (max 1000 characters)";
    } else {
        $clean['url'] = $url;
    }

    if (array_key_exists('title', $job) || !$partial) {
        $title = is_string($job['title'] ?? null) ? trim($job['title']) : '';
        if ($title === '') {
            $errors[] = "title is required";
        } elseif (mb_strlen($title) > 200) {
            $errors[] = "title too long (max 200 characters)";
        } else {
            $clean['title'] = htmlspecialchars($title);
        }
    }

    if (array_key_exists('location', $job) || !$partial) {
        $locations = $job['location'] ?? null;
        if (is_string($locations)) {
            $locations = [$locations];
        }
        $locations = is_array($locations)
            ? array_values(array_filter(array_map(fn($l) => is_string($l) ? trim($l) : '', $locations)))
            : [];
        if (empty($locations)) {
            $errors[] = "location is required (string or array of strings)";
        } elseif (count($locations) > 20 || max(array_map('mb_strlen', $locations)) > 100) {
            $errors[] = "location: max 20 entries of max 100 characters";
        } else {
            $clean['location'] = array_map('htmlspecialchars', $locations);
        }
    }

    if (array_key_exists('workmode', $job)) {
        $allowedWorkmodes = ['on-site', 'remote', 'hybrid'];
        $workmode = is_string($job['workmode']) ? strtolower(trim($job['workmode'])) : '';
        if (!in_array($workmode, $allowedWorkmodes, true)) {
            $errors[] = "workmode must be one of: " . implode(', ', $allowedWorkmodes);
        } else {
            $clean['workmode'] = $workmode;
        }
    }

    if (array_key_exists('tags', $job)) {
        $tags = $job['tags'];
        if (!is_array($tags) || count($tags) > 20) {
            $errors[] = "tags must be an array of max 20 strings";
        } else {
            $clean['tags'] = array_values(array_filter(array_map(
                fn($t) => is_string($t) ? htmlspecialchars(mb_strtolower(trim(mb_substr($t, 0, 50)))) : '',
                $tags
            )));
        }
    }

    return [$clean, $errors];
}

/**
 * Real-time get of jobs by url (the uniqueKey). POSTed as a form, since up to
 * 100 urls do not fit in a GET query string. Returns [url => doc].
 */
function fetchJobsByUrl(string $coreUrl, ?string $user, ?string $pass, array $urls): array {
    if (empty($urls)) {
        return [];
    }
    $form = 'wt=json&' . implode('&', array_map(fn($u) => 'id=' . rawurlencode($u), $urls));
    $json = solrRequest('POST', "$coreUrl/get", $user, $pass, $form, 'application/x-www-form-urlencoded');
    $docs = isset($json['doc']) ? [$json['doc']] : ($json['response']['docs'] ?? []);
    $byUrl = [];
    foreach ($docs as $doc) {
        if ($doc) {
            $byUrl[(string)solrScalar($doc['url'] ?? '')] = $doc;
        }
    }
    return $byUrl;
}

function fetchCompanyJobs(string $coreUrl, ?string $user, ?string $pass, string $cif): array {
    $query = http_build_query([
        'q'    => 'cif:"' . addcslashes($cif, '"\\') . '"',
        'fl'   => 'url,title,company,cif,location,workmode,tags,date,status,source',
        'rows' => 20000,
        'wt'   => 'json',
    ]);
    $json = solrRequest('GET', "$coreUrl/select?$query", $user, $pass);
    return $json['response']['docs'] ?? [];
}

function isPostedThroughV7(array $doc): bool {
    return solrScalar($doc['source'] ?? null) === JOB_SOURCE_COMPANY;
}

/**
 * The job as returned to the company: single values unwrapped, Solr internals removed.
 */
function publicJob(array $doc): array {
    $out = [];
    foreach (['url', 'title', 'company', 'cif', 'location', 'workmode', 'tags', 'date', 'status'] as $f) {
        if (!array_key_exists($f, $doc)) {
            continue;
        }
        $out[$f] = in_array($f, ['location', 'tags'], true) ? (array)$doc[$f] : solrScalar($doc[$f]);
    }
    return $out;
}

/**
 * For PUT/DELETE: the job must exist, belong to the caller's company and have been posted through v7.
 */
function loadOwnedJob(string $coreUrl, ?string $user, ?string $pass, string $url, string $cif): array {
    $doc = fetchJobsByUrl($coreUrl, $user, $pass, [$url])[$url] ?? null;
    if (!$doc) {
        http_response_code(404);
        echo json_encode(["error" => "Job not found", "url" => $url]);
        exit;
    }
    if ((string)solrScalar($doc['cif'] ?? '') !== $cif || !isPostedThroughV7($doc)) {
        http_response_code(403);
        echo json_encode(["error" => "You can only change jobs your company posted through this API", "url" => $url]);
        exit;
    }
    return $doc;
}

function readJsonBody(): array {
    $contentType = $_SERVER["CONTENT_TYPE"] ?? $_SERVER["HTTP_CONTENT_TYPE"] ?? '';
    if (stripos($contentType, 'application/json') === false) {
        http_response_code(415);
        echo json_encode(["error" => "Content-Type must be application/json"]);
        exit;
    }
    $data = json_decode(file_get_contents("php://input"), true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(["error" => "Body must be valid JSON"]);
        exit;
    }
    return $data;
}

if (!$SOLR_SERVER) {
    error_log("V7 JOBS: SOLR_SERVER not set");
    http_response_code(503);
    echo json_encode(["error" => "Job core unavailable"]);
    exit;
}

$core = 'job';
$maxJobsPerRequest = 100;
$solrBase = ($PROTOCOL ?: 'http') . "://$SOLR_SERVER/solr";
$coreUrl = "$solrBase/$core";
$updateUrl = "$coreUrl/update?commitWithin=1000&wt=json";

$client = requireCompanyApiKey($solrBase, $SOLR_USER, $SOLR_PASS);
$cif = $client['cif'];

try {
    if ($method === 'GET') {
        $jobs = array_values(array_map('publicJob', array_filter(
            fetchCompanyJobs($coreUrl, $SOLR_USER, $SOLR_PASS, $cif),
            'isPostedThroughV7'
        )));
        echo json_encode(["cif" => $cif, "company" => $client['company'], "count" => count($jobs), "jobs" => $jobs]);
        exit;
    }

    if ($method === 'POST') {
        $body = readJsonBody();
        $jobs = isset($body['jobs']) && is_array($body['jobs']) ? $body['jobs']
              : (array_is_list($body) ? $body : [$body]);

        if (empty($jobs)) {
            http_response_code(400);
            echo json_encode(["error" => "No jobs in body"]);
            exit;
        }
        if (count($jobs) > $maxJobsPerRequest) {
            http_response_code(400);
            echo json_encode(["error" => "Too many jobs in one request (max $maxJobsPerRequest)"]);
            exit;
        }

        $clean = [];
        $invalid = [];
        $seenUrls = [];
        foreach ($jobs as $i => $job) {
            [$c, $errors] = validateJobInput($job, false);
            if (!$errors && isset($seenUrls[$c['url']])) {
                $errors[] = "same url as job #" . $seenUrls[$c['url']] . " in this request";
            }
            if ($errors) {
                $invalid[] = ["index" => $i, "errors" => $errors];
                continue;
            }
            $seenUrls[$c['url']] = $i;
            $clean[$i] = $c;
        }
        if ($invalid) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid jobs, nothing was saved", "invalid" => $invalid]);
            exit;
        }

        // Scrapers (v1) store urls through htmlspecialchars(), so "&" may be saved as "&amp;": look up both forms
        $lookupUrls = [];
        foreach ($clean as $job) {
            $lookupUrls[$job['url']] = true;
            $lookupUrls[htmlspecialchars($job['url'])] = true;
        }
        $existingByUrl = fetchJobsByUrl($coreUrl, $SOLR_USER, $SOLR_PASS, array_keys($lookupUrls));

        $conflicts = [];
        foreach ($clean as $i => $job) {
            $existing = $existingByUrl[$job['url']] ?? $existingByUrl[htmlspecialchars($job['url'])] ?? null;
            if ($existing) {
                if ((string)solrScalar($existing['cif'] ?? '') !== $cif) {
                    $reason = "url_belongs_to_another_company";
                } elseif (isPostedThroughV7($existing)) {
                    $reason = "already_posted_use_put";
                } else {
                    $reason = "already_scraped";
                }
                $conflicts[] = ["index" => $i, "url" => $job['url'], "reason" => $reason];
            }
        }
        if ($conflicts) {
            http_response_code(409);
            echo json_encode(["error" => "Some jobs already exist on peviitor, nothing was saved", "conflicts" => $conflicts]);
            exit;
        }

        // Milliseconds: the date also marks the docs written by this request (see the rollback below)
        $now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z');
        $docs = array_map(fn($job) => $job + [
            'company' => $client['company'] ?: $cif,
            'cif'     => $cif,
            'date'    => $now,
            'status'  => 'activ',
            'source'  => JOB_SOURCE_COMPANY,
            // Solr writes the doc only if no doc with this url exists yet, atomically:
            // closes the gap between the existing-url check above and this write
            '_version_' => -1,
        ], array_values($clean));

        try {
            solrRequest('POST', $updateUrl, $SOLR_USER, $SOLR_PASS, json_encode($docs));
        } catch (Exception $e) {
            if ($e->getCode() !== 409) {
                throw $e;
            }
            // Another request (or a scraper) created one of these urls meanwhile. Solr may have
            // kept the docs before it, so delete what THIS request wrote: same urls, this
            // company and this exact date. The other writer's doc never matches all three.
            preg_match('/version conflict for (\S+) expected/', $e->getMessage(), $m);
            $urlsQuery = implode(' OR ', array_map(fn($d) => 'url:"' . addcslashes($d['url'], '"\\') . '"', $docs));
            $rollback = ['delete' => ['query' => "($urlsQuery) AND cif:\"" . addcslashes($cif, '"\\') . "\" AND source:\"" . JOB_SOURCE_COMPANY . "\" AND date:\"$now\""]];
            solrRequest('POST', $updateUrl, $SOLR_USER, $SOLR_PASS, json_encode($rollback));
            error_log("V7 JOBS POST CONFLICT: key={$client['prefix']} cif=$cif url=" . ($m[1] ?? '?'));
            http_response_code(409);
            echo json_encode([
                "error" => "Some jobs already exist on peviitor, nothing was saved",
                "conflicts" => [["url" => $m[1] ?? null, "reason" => "created_concurrently"]],
            ]);
            exit;
        }
        error_log("V7 JOBS POST: key={$client['prefix']} cif=$cif count=" . count($docs));
        http_response_code(201);
        echo json_encode(["success" => "Jobs saved", "count" => count($docs), "urls" => array_column($docs, 'url')]);
        exit;
    }

    if ($method === 'PUT') {
        [$changes, $errors] = validateJobInput(readJsonBody(), true);
        if ($errors) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid job", "errors" => $errors]);
            exit;
        }
        $existing = loadOwnedJob($coreUrl, $SOLR_USER, $SOLR_PASS, $changes['url'], $cif);

        $updated = $changes + $existing;
        unset($updated['_root_'], $updated['score']);

        try {
            // _version_ from the read makes Solr reject the write if the job changed meanwhile
            solrRequest('POST', $updateUrl, $SOLR_USER, $SOLR_PASS, json_encode([$updated]));
        } catch (Exception $e) {
            if ($e->getCode() === 409) {
                http_response_code(409);
                echo json_encode(["error" => "The job was changed by another request, retry", "reason" => "concurrent_update"]);
                exit;
            }
            throw $e;
        }
        error_log("V7 JOBS PUT: key={$client['prefix']} cif=$cif url={$changes['url']}");
        echo json_encode(["success" => "Job updated", "job" => publicJob($updated)]);
        exit;
    }

    if ($method === 'DELETE') {
        $url = trim($_GET['url'] ?? '');
        if ($url === '') {
            $url = trim((string)(readJsonBody()['url'] ?? ''));
        }
        if ($url === '') {
            http_response_code(400);
            echo json_encode(["error" => "url is required (?url=... or JSON body)"]);
            exit;
        }
        loadOwnedJob($coreUrl, $SOLR_USER, $SOLR_PASS, $url, $cif);

        solrRequest('POST', $updateUrl, $SOLR_USER, $SOLR_PASS, json_encode(['delete' => ['id' => $url]]));
        error_log("V7 JOBS DELETE: key={$client['prefix']} cif=$cif url=$url");
        echo json_encode(["success" => "Job deleted", "url" => $url]);
        exit;
    }

} catch (Exception $e) {
    // Details stay in the server log; the client only learns that Solr failed
    error_log("V7 JOBS FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode(["error" => "Job core unavailable"]);
}
