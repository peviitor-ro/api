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

    $core = 'job';
    $base = "http://$PROD_SERVER/solr/$core/select";

    $qs = http_build_query([
        "facet.field" => "company_str",
        "facet.limit" => "2000000",
        "facet" => "true",
        "fl" => "company",
        "indent" => "true",
        "q.op" => "OR",
        "q" => "*:*",
        "rows" => "0",
        "start" => "0"
    ]);

    $url = "$base?$qs";
    error_log("TOTAL URL: $url");

    $solr = fetchJson($url, $SOLR_USER, $SOLR_PASS, 4);

    if (!isset($solr['facet_counts']['facet_fields']['company_str'])) {
        throw new Exception("Invalid response from Solr");
    }

    $companies = $solr['facet_counts']['facet_fields']['company_str'] ?? [];
    $companyCount = 0;
    for ($i = 1; $i < count($companies); $i += 2) {
        if ($companies[$i] > 0) {
            $companyCount++;
        }
    }

    echo json_encode([
        "total" => [
            "jobs" => (int) ($solr['response']['numFound'] ?? 0),
            "companies" => (int) $companyCount
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
