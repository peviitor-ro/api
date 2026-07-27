<?php

/**
 * ============================================================================
 * DELETE /v1/empty/ - Empty All Jobs Endpoint
 * ============================================================================
 *
 * DESCRIPTION:
 *   This endpoint permanently deletes ALL job records from the Solr database.
 *   WARNING: This action cannot be undone. No recovery possible without backups.
 *
 * DEPENDENCIES:
 *   - Apache Solr server (required, configured via PROD_SERVER in api.env)
 *   - Solr Basic Authentication (SOLR_USER and SOLR_PASS from api.env)
 *   - 'job' core in Solr (all job records deleted from this core)
 *
 * AUTHENTICATION:
 *   X-API-Key header must match CLEANUP_API_KEY from api.env
 *   X-Cleanup-Secret header must match CLEANUP_SECRET from api.env
 *
 * REQUIRED HTTP METHOD:
 *   DELETE (only this method is allowed)
 *
 * SUCCESS RESPONSE (200 OK):
 *   {
 *     "message": "Jobs deleted successfully",
 *     "jobsDeleted": <number>
 *   }
 *
 * ERROR RESPONSES:
 *   401: Unauthorized (invalid or missing auth headers)
 *   405: Method not allowed (only DELETE is allowed)
 *   503: Service unavailable (Solr server down or misconfiguration)
 *
 * ============================================================================
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

$PROD_SERVER = trim(getenv('PROD_SERVER') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');
$CLEANUP_API_KEY = trim(getenv('CLEANUP_API_KEY') ?: '');
$CLEANUP_SECRET = trim(getenv('CLEANUP_SECRET') ?: '');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(["error" => "Only DELETE method allowed"]);
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

try {
    if (!$PROD_SERVER) {
        throw new Exception("PROD_SERVER not set");
    }

    // Auth: validate both X-API-Key and X-Cleanup-Secret headers
    $apiKey = trim($_SERVER['HTTP_X_API_KEY'] ?? '');
    $cleanupSecret = trim($_SERVER['HTTP_X_CLEANUP_SECRET'] ?? '');

    if (empty($apiKey) || empty($cleanupSecret)) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized - missing X-API-Key or X-Cleanup-Secret header']);
        exit;
    }

    if ($apiKey !== $CLEANUP_API_KEY || $cleanupSecret !== $CLEANUP_SECRET) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized - invalid credentials']);
        exit;
    }

    $core = 'job';
    $base = "http://$PROD_SERVER/solr/$core";

    // Count all jobs before delete
    $countUrl = "$base/select?q=*:*&wt=json&rows=0";
    $countResponse = fetchJson($countUrl, $SOLR_USER, $SOLR_PASS, 4);
    $jobCount = $countResponse['response']['numFound'] ?? 0;

    if ($jobCount > 0) {
        // Execute delete all
        $deleteUrl = "$base/update?commit=true&wt=json";
        $deletePayload = json_encode(['delete' => ['query' => '*:*']]);
        postJson($deleteUrl, $deletePayload, $SOLR_USER, $SOLR_PASS);
    }

    error_log("EMPTY SUCCESS: jobsDeleted=$jobCount");

    http_response_code(200);
    echo json_encode([
        'message' => 'Jobs deleted successfully',
        'jobsDeleted' => $jobCount
    ]);

} catch (Exception $e) {
    error_log("EMPTY FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}
