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

    $page = max(1, (int)($_GET['page'] ?? 1));
    $rows = max(1, (int)($_GET['rows'] ?? 100));
    $start = ($page - 1) * $rows;

    $core = 'company';
    $base = "$PROTOCOL://$SOLR_SERVER/solr/$core/select";

    $qs = http_build_query([
        "q" => "*:*",
        "rows" => $rows,
        "start" => $start,
        "fl" => "id,company",
        "indent" => "true"
    ]);

    $url = "$base?$qs";
    error_log("COMPANIES URL: $url");

    $solr = fetchJson($url, $SOLR_USER, $SOLR_PASS, 4);

    $numFound = $solr['response']['numFound'] ?? 0;
    $docs = $solr['response']['docs'] ?? [];

    $companies = [];
    foreach ($docs as $doc) {
        $companies[] = [
            'name' => $doc['company'] ?? null,
            'id' => $doc['id'] ?? null
            ];
    }

    echo json_encode([
        'total' => (int) $numFound,
        'companies' => $companies
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("COMPANIES FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Company core unavailable',
        'details' => $e->getMessage()
    ]);
}
