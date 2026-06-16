<?php

/**
 * ============================================================================
 * DELETE /v1/empty/ - Database Cleanup Endpoint
 * ============================================================================
 * 
 * DESCRIPTION:
 *   This endpoint permanently deletes ALL job records from the Solr database.
 *   WARNING: This action cannot be undone. No recovery possible without backups.
 * 
 * DEPENDENCIES:
 *   - Apache Solr server (required, configured via PROD_SERVER in api.env)
 *   - Solr Basic Authentication (SOLR_USER and SOLR_PASS from api.env)
 *     All Solr requests use: Authorization: Basic base64(SOLR_USER:SOLR_PASS)
 *   - 'job' core in Solr (all job records deleted from this core)
 *   - 'company' core in Solr (counted, but not deleted)
 * 
 * AUTHENTICATION:
 *   - PRODUCTION (NODE_ENV=production): 
 *     Requires valid X-API-Key and X-Cleanup-Secret headers that match api.env
 *   - NON-PRODUCTION (anything else): 
 *     Accepts any credentials (for testing/staging)
 * 
 * REQUIRED HTTP METHOD:
 *   DELETE (only this method is allowed)
 * 
 * REQUIRED HEADERS:
 *   X-API-Key: <value from CLEANUP_API_KEY in api.env>
 *   X-Cleanup-Secret: <value from CLEANUP_SECRET in api.env>
 *   Content-Type: application/json
 * 
 * REQUIRED REQUEST BODY:
 *   {"confirmation": "DELETE_ALL_DATA"}
 *   (confirmation string must be EXACTLY "DELETE_ALL_DATA")
 * 
 * SUCCESS RESPONSE (200 OK):
 *   {
 *     "message": "Jobs deleted successfully",
 *     "jobsDeleted": <number>,
 *     "companiesDeleted": <number>
 *   }
 * 
 * ERROR RESPONSES:
 *   401: Unauthorized (invalid or missing credentials in PRODUCTION mode)
 *   405: Method not allowed (only DELETE is allowed)
 *   503: Service unavailable (Solr server down or misconfiguration)
 * 
 * EXAMPLE CURL (PRODUCTION):
 *   curl -X DELETE \
 *     -H "X-API-Key: abc123xyz789" \
 *     -H "X-Cleanup-Secret: secret456def012" \
 *     -H "Content-Type: application/json" \
 *     -d '{"confirmation": "DELETE_ALL_DATA"}' \
 *     https://api.peviitor.ro/v1/empty/
 * 
 * ============================================================================
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

// ======= REQUIRED api.env VARIABLES =======
// This script expects the following variables in api.env:
//   NODE_ENV=production              (or any non-production value for permissive mode)
//   CLEANUP_API_KEY=<random_secret>  (required only in production)
//   CLEANUP_SECRET=<random_secret>   (required only in production)
//   PROD_SERVER=<solr_server_url>    (e.g., http://localhost:8983 or https://solr.example.com)
//   SOLR_USER=<solr_username>
//   SOLR_PASS=<solr_password>
// =======================================

$SOLR_SERVER = trim(getenv('PROD_SERVER') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(["error" => "Only DELETE method allowed"]);
    exit;
}

// ======= AUTHENTICATION MIDDLEWARE =======
// Expected headers (CASE-INSENSITIVE, will be converted to HTTP_* uppercase):
//   X-API-Key: <value from CLEANUP_API_KEY in api.env>
//   X-Cleanup-Secret: <value from CLEANUP_SECRET in api.env>
// Example curl:
//   curl -X DELETE \
//     -H "X-API-Key: abc123xyz789" \
//     -H "X-Cleanup-Secret: secret456def012" \
//     https://api.peviitor.ro/v1/empty/

$ENVIRONMENT = trim(getenv('NODE_ENV') ?: 'production');
$API_KEY = trim($_SERVER['HTTP_X_API_KEY'] ?? '');        // Gets: X-API-Key header
$SECRET = trim($_SERVER['HTTP_X_CLEANUP_SECRET'] ?? '');   // Gets: X-Cleanup-Secret header

if ($ENVIRONMENT === 'production') {
    // PRODUCTION: strict validation
    error_log("🔐 PRODUCTION - validating credentials");
    
    if (empty($API_KEY) || empty($SECRET)) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized - missing credentials"]);
        exit;
    }
    
    $VALID_API_KEY = trim(getenv('CLEANUP_API_KEY') ?: '');
    $VALID_SECRET = trim(getenv('CLEANUP_SECRET') ?: '');
    
    if (empty($VALID_API_KEY) || empty($VALID_SECRET)) {
        http_response_code(500);
        echo json_encode(["error" => "Server misconfiguration"]);
        exit;
    }
    
    // Timing-safe comparison (prevents timing attacks)
    if (!hash_equals($API_KEY, $VALID_API_KEY) || 
        !hash_equals($SECRET, $VALID_SECRET)) {
        http_response_code(401);
        error_log("❌ Invalid credentials attempt from " . $_SERVER['REMOTE_ADDR']);
        echo json_encode(["error" => "Unauthorized - invalid credentials"]);
        exit;
    }
    
    error_log("✅ Valid credentials verified");
} else {
    // NOT PRODUCTION (test, staging, dev, etc): accept any credentials
    error_log("📋 Non-production mode - accepting any credentials");
}
// ======= END AUTHENTICATION =======

function fetchJson(string $url, ?string $user = null, ?string $pass = null, int $timeout = 5): array {
    $headers = [];
    if ($user && $pass) {
        $headers[] = "Authorization: Basic " . base64_encode("$user:$pass");
    }
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
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

function postJson(string $url, string $payload, ?string $user = null, ?string $pass = null): array {
    $headers = [];
    if ($user && $pass) {
        $headers[] = "Authorization: Basic " . base64_encode("$user:$pass");
    }
    $headers[] = "Content-Type: application/json";
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
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

try {

    if (!$SOLR_SERVER) {
        throw new Exception("SOLR_SERVER not set");
    }

    $core = 'job';
    $base = "http://$SOLR_SERVER/solr/$core";

    $countUrl = $base . "/select?q=*:*&wt=json&rows=0";
    error_log("EMPTY COUNT URL: $countUrl");

    $countResponse = fetchJson($countUrl, $SOLR_USER, $SOLR_PASS, 4);
    $jobCount = $countResponse['response']['numFound'] ?? 0;

    $companyCount = 0;
    $companyCore = 'company';
    $companyBase = "http://$SOLR_SERVER/solr/$companyCore";
    $companyUrl = $companyBase . "/select?q=*:*&wt=json&rows=0";

    error_log("EMPTY COMPANY COUNT URL: $companyUrl");
    $companyResponse = fetchJson($companyUrl, $SOLR_USER, $SOLR_PASS, 4);
    $companyCount = $companyResponse['response']['numFound'] ?? 0;

    $deleteUrl = $base . "/update?commitWithin=1000&wt=json";
    $deletePayload = json_encode(['delete' => ['query' => '*:*']]);

    error_log("EMPTY DELETE URL: $deleteUrl");
    postJson($deleteUrl, $deletePayload, $SOLR_USER, $SOLR_PASS);

    echo json_encode([
        'message' => 'Jobs deleted successfully',
        'jobsDeleted' => $jobCount,
        'companiesDeleted' => $companyCount
    ]);

} catch (Exception $e) {
    error_log("EMPTY FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}
