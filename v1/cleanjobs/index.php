<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

$PROD_SERVER = trim(getenv('PROD_SERVER') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');

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

    parse_str(file_get_contents('php://input'), $deleteData);
    $company = $_GET['company'] ?? $deleteData['company'] ?? null;
    $company = $company !== null ? trim($company) : null;

    if (empty($company)) {
        http_response_code(400);
        echo json_encode(['error' => 'Company name is required', 'code' => 400]);
        exit;
    }

    $core = 'job';
    $base = "http://$PROD_SERVER/solr/$core";

    $countUrl = $base . "/select?q=" . rawurlencode('company:"' . $company . '"') . "&wt=json&rows=0";
    error_log("CLEANJOBS COUNT URL: $countUrl");

    $countResponse = fetchJson($countUrl, $SOLR_USER, $SOLR_PASS, 4);
    $jobCount = $countResponse['response']['numFound'] ?? 0;

    if ($jobCount === 0) {
        http_response_code(404);
        echo json_encode([
            'error' => 'Company not found',
            'message' => "No jobs found for the specified company '$company'",
            'code' => 404
        ]);
        exit;
    }

    $deleteUrl = $base . "/update?commit=true&wt=json";
    $deletePayload = json_encode(['delete' => ['query' => 'company:"' . $company . '"']]);
    error_log("CLEANJOBS DELETE URL: $deleteUrl");

    $deleteResponse = postJson($deleteUrl, $deletePayload, $SOLR_USER, $SOLR_PASS);

    http_response_code(200);
    echo json_encode([
        'message' => "Jobs for company '$company' deleted successfully",
        'jobCount' => $jobCount
    ]);

} catch (Exception $e) {
    error_log("CLEANJOBS FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}
