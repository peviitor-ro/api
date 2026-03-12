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

    $page = max(1, (int)($_GET['page'] ?? 1));
    $rows = max(1, (int)($_GET['rows'] ?? 100));
    $start = ($page - 1) * $rows;

    $core = 'company';
    $base = "http://$PROD_SERVER/solr/$core/select";

    $qs = http_build_query([
        "q" => "*:*",
        "rows" => $rows,
        "start" => $start,
        "fl" => "id,company,logo",
        "sort" => "company asc",
        "indent" => "true"
    ]);

    $url = "$base?$qs";
    error_log("LOGO URL: $url");

    $solr = fetchJson($url, $SOLR_USER, $SOLR_PASS, 4);

    $numFound = $solr['response']['numFound'] ?? 0;
    $docs = $solr['response']['docs'] ?? [];

    $logos = [];
    foreach ($docs as $doc) {
        $logos[] = [
            'company' => $doc['company'] ?? null,
            'logo' => $doc['logo'] ?? null,
            'id' => $doc['id'] ?? null
        ];
    }

    echo json_encode([
        'total' => (int) $numFound,
        'logos' => $logos
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("LOGO FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Company core unavailable',
        'details' => $e->getMessage()
    ]);
}
