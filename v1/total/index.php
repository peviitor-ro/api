<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../util/loadEnv.php';
require_once __DIR__ . '/../../util/http.php';

loadEnv(__DIR__ . '/../../api.env');

$SOLR_SERVER = trim(getenv('SOLR_SERVER') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');
$PROTOCOL = trim(getenv('PROTOCOL') ?: '');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Only GET method allowed"]);
    exit;
}

try {
    if (!$SOLR_SERVER) {
        throw new Exception("SOLR_SERVER not set");
    }

    $jobBase = "$PROTOCOL://$SOLR_SERVER/solr/job/select";
    $companyBase = "$PROTOCOL://$SOLR_SERVER/solr/company/select";

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

//TO DO: RATE LIMITING & AUDIT LOGGING