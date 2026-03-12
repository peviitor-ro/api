<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

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

    $jobBase = "http://$PROD_SERVER/solr/job/select";
    $companyBase = "http://$PROD_SERVER/solr/company/select";

    $jobUrl = $jobBase . '?' . http_build_query([
        "q" => "*:*",
        "rows" => "0",
        "start" => "0",
        "indent" => "true"
    ]);
    error_log("TOTAL JOBS URL: $jobUrl");

    $jobSolr = fetchJson($jobUrl, $SOLR_USER, $SOLR_PASS, 4);
    $jobsCount = $jobSolr['response']['numFound'] ?? 0;

    $companyUrl = $companyBase . '?' . http_build_query([
        "q" => "*:*",
        "rows" => "0",
        "start" => "0",
        "indent" => "true"
    ]);
    error_log("TOTAL COMPANIES URL: $companyUrl");

    $companySolr = fetchJson($companyUrl, $SOLR_USER, $SOLR_PASS, 4);
    $companiesCount = $companySolr['response']['numFound'] ?? 0;

    echo json_encode([
        "total" => [
            "jobs" => (int) $jobsCount,
            "companies" => (int) $companiesCount
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("TOTAL FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}
