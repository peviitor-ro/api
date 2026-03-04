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
    $base = "https://$PROD_SERVER/solr/$core/select";

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
        'job_title' => $job['title'] ?? null,
        'company' => $job['company'] ?? null,
        'city' => $job['location'] ?? [],
        'county' => $job['location'] ?? [],
        'remote' => $job['workmode'] ?? '',
        'job_link' => $job['url'] ?? null,
        'id' => md5($job['url'] ?? '')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("RANDOM FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}
