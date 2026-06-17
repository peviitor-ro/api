<?php

/**
 * ============================================================================
 * GET /v1/random/ - Random Job Endpoint
 * ============================================================================
 * 
 * DESCRIPTION:
 *   This endpoint returns a single random job record from the Solr database.
 *   Useful for "job of the day" widgets, testing, or exploratory browsing.
 * 
 * DEPENDENCIES:
 *   - Apache Solr server (required, configured via PROD_SERVER in api.env)
 *   - Solr Basic Authentication (SOLR_USER and SOLR_PASS from api.env)
 *     All Solr requests use: Authorization: Basic base64(SOLR_USER:SOLR_PASS)
 *   - 'job' core in Solr (random job selected from this core)
 * 
 * AUTHENTICATION:
 *   No authentication required. This endpoint is publicly accessible.
 * 
 * REQUIRED HTTP METHOD:
 *   GET (only this method is allowed)
 * 
 * SUCCESS RESPONSE (200 OK):
 *   {
 *     "title": <string>,
 *     "company": <string>,
 *     "location": <array>,
 *     "workmode": <string>,
 *     "url": <string>,
 *     "salary": <string|null>,
 *     "tags": <array>,
 *     "cif": <string>,
 *     "date": <string|null>,
 *     "status": <string|null>
 *   }
 * 
 * ERROR RESPONSES:
 *   404: No jobs found (database is empty)
 *   405: Method not allowed (only GET is allowed)
 *   503: Service unavailable (Solr server down or misconfiguration)
 * 
 * EXAMPLE CURL:
 *   curl https://api.peviitor.ro/v1/random/
 * 
 * ============================================================================
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

// ======= REQUIRED api.env VARIABLES =======
// This script expects the following variables in api.env:
//   PROD_SERVER=<solr_server_url>    (e.g., http://localhost:8983 or https://solr.example.com)
//   SOLR_USER=<solr_username>
//   SOLR_PASS=<solr_password>
// =======================================

$PROD_SERVER = trim(getenv('PROD_SERVER') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Only GET method allowed"]);
    exit;
}

function fetchJson(string $url, ?string $user = null, ?string $pass = null, int $timeout = 5): array {
    $headers = [];
    if ($user && $pass) {
        $headers[] = "Authorization: Basic " . base64_encode("$user:$pass");
    }
    $context = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => implode("\r\n", $headers),
            'timeout' => $timeout
        ]
    ]);
    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        $err = error_get_last()['message'] ?? 'Unknown error';
        throw new Exception("FETCH FAILED: $url | $err");
    }
    $json = json_decode($data, true);
    if (!is_array($json)) {
        throw new Exception("Invalid JSON response");
    }
    return $json;
}

try {
    if (!$PROD_SERVER) {
        throw new Exception("PROD_SERVER not set");
    }

    $core = 'job';
    $base = "http://$PROD_SERVER/solr/$core/select";

    $countUrl = $base . '?q=*:*&rows=0';
    error_log("RANDOM COUNT URL: $countUrl");

    $solr = fetchJson($countUrl, $SOLR_USER, $SOLR_PASS, 4);
    $numFound = $solr['response']['numFound'] ?? 0;

    if ($numFound === 0) {
        http_response_code(404);
        echo json_encode(["error" => "No jobs found"]);
        exit;
    }

    $start = rand(0, $numFound - 1);
    $randomUrl = $base . '?q=*:*&rows=1&start=' . $start . '&omitHeader=true';
    error_log("RANDOM URL: $randomUrl");

    $doc = fetchJson($randomUrl, $SOLR_USER, $SOLR_PASS, 4);
    $job = $doc['response']['docs'][0] ?? null;

    if (!$job) {
        throw new Exception("Failed to fetch random job");
    }

    echo json_encode([
        'title' => $job['title'] ?? null,
        'company' => $job['company'] ?? null,
        'location' => $job['location'] ?? [],
        'workmode' => $job['workmode'] ?? '',
        'url' => $job['url'] ?? null,
        'salary' => $job['salary'] ?? null,
        'tags' => $job['tags'] ?? [],
        'cif' => $job['cif'] ?? '',
        'date' => $job['date'] ?? null,
        'status' => $job['status'] ?? null,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("RANDOM FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}
