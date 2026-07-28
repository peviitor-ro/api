<?php
// DELETE /v1/scraper/jobs/delete/
// Deletes job postings from the Solr "job" core.
// Accepts JSON body with exactly one of:
//   { "cif": "05415866" }  — deletes all jobs matching this CIF (8 digits)
//   { "url": "https://..." } — deletes a single job by its exact URL

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow DELETE method
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode([
        "error" => "Only DELETE method is allowed",
        "code" => 405
    ]);
    exit;
}

// Load environment variables (SOLR_SERVER, PROTOCOL, SOLR_USER, SOLR_PASS)
require_once __DIR__ . '/../../../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../../../api.env');

$SOLR_SERVER = trim(getenv('SOLR_SERVER') ?: '');
$PROTOCOL = trim(getenv('PROTOCOL') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');

// POST request to Solr with a JSON payload (used for updates/deletes)
function postJson(string $url, string $payload, ?string $user = null, ?string $pass = null): array {
    $headers = [];
    if ($user && $pass) {
        $headers[] = "Authorization: Basic " . base64_encode("$user:$pass");
    }
    $headers[] = "Content-Type: application/json";
    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", $headers),
            'content' => $payload,
            'timeout' => 10
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

// GET request to Solr (used for counting matching docs before deleting)
function getJson(string $url, ?string $user = null, ?string $pass = null): array {
    $headers = [];
    if ($user && $pass) {
        $headers[] = "Authorization: Basic " . base64_encode("$user:$pass");
    }
    $headers[] = "Content-Type: application/json";
    $context = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => implode("\r\n", $headers),
            'timeout' => 10
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

// Build Solr update URL with commit=true so changes are visible immediately
function buildSolrUpdateUrl(string $server, string $core, string $protocol = 'http'): string {
    $server = rtrim($server, '/');
    if (preg_match('#^https?://#i', $server)) {
        return $server . "/solr/$core/update?commit=true&wt=json";
    }
    return "$protocol://$server/solr/$core/update?commit=true&wt=json";
}

// Escape special Solr query characters to prevent injection
function solrEscape(string $value): string {
    $chars = ['+', '-', '&&', '||', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '\\', '/'];
    foreach ($chars as $char) {
        $value = str_replace($char, '\\' . $char, $value);
    }
    return $value;
}

try {
    if (!$SOLR_SERVER) {
        throw new Exception("SOLR_SERVER not set");
    }

    // --- 1. Parse and validate the request body ---
    $requestBody = file_get_contents('php://input');
    $data = json_decode($requestBody, true);

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON body', 'code' => 400]);
        exit;
    }

    $hasCif = isset($data['cif']) && is_string($data['cif']);
    $hasUrl = isset($data['url']) && is_string($data['url']);

    // Must have exactly one of cif or url
    if (!$hasCif && !$hasUrl) {
        http_response_code(400);
        echo json_encode(['error' => 'Exactly one of "cif" or "url" is required', 'code' => 400]);
        exit;
    }

    if ($hasCif && $hasUrl) {
        http_response_code(400);
        echo json_encode(['error' => 'Provide exactly one of "cif" or "url", not both', 'code' => 400]);
        exit;
    }

    // --- 2. Build the Solr query ---
    $core = 'job';

    if ($hasCif) {
        // Strip non-digits and enforce exactly 8 digits (zero-padded CIF)
        $cif = preg_replace('/[^0-9]/', '', $data['cif']);
        if (strlen($cif) !== 8) {
            http_response_code(400);
            echo json_encode(['error' => 'CIF must be exactly 8 digits', 'code' => 400]);
            exit;
        }
        $escapedCif = solrEscape($cif);
        $query = "cif:$escapedCif";
    } else {
        // URL field values are stored as-is; escape for Solr query syntax
        $escapedUrl = solrEscape($data['url']);
        $query = "url:$escapedUrl";
    }

    // --- 3. Count matching jobs first (GET to /select with URL params) ---
    $selectUrl = "$PROTOCOL://$SOLR_SERVER/solr/$core/select?" . http_build_query([
        'q'    => $query,
        'rows' => 0,
        'wt'   => 'json'
    ]);

    $countResponse = getJson($selectUrl, $SOLR_USER, $SOLR_PASS);
    $totalCount = $countResponse['response']['numFound'] ?? 0;

    // Nothing to delete — return 404
    if ($totalCount === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'No matching jobs found', 'code' => 404]);
        exit;
    }

    // --- 4. Delete matching jobs (POST to /update with JSON delete body) ---
    $solrUrl = buildSolrUpdateUrl($SOLR_SERVER, $core, $PROTOCOL);

    $deletePayload = json_encode([
        'delete' => [
            'query' => $query
        ]
    ]);

    $response = postJson($solrUrl, $deletePayload, $SOLR_USER, $SOLR_PASS);

    // Solr returns { responseHeader: { status: 0 } } on success
    $solrStatus = $response['responseHeader']['status'] ?? -1;
    if ($solrStatus !== 0) {
        throw new Exception("Solr delete returned status: $solrStatus");
    }

    // --- 5. Return success with count of deleted jobs ---
    echo json_encode([
        'success' => true,
        'message' => "Jobs deleted successfully",
        'count'   => $totalCount
    ]);

} catch (Exception $e) {
    error_log("SCRAPER JOBS DELETE FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error'   => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}
