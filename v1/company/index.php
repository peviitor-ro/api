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

    $cif = $_GET['cif'] ?? '';
    $name = $_GET['name'] ?? '';

    if (empty($cif) && empty($name)) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required field: cif or name"]);
        exit;
    }

    $core = 'company';
    $base = "http://$PROD_SERVER/solr/$core/select";

    if (!empty($name)) {
        $qs = http_build_query([
            "q" => "name:*" . rawurlencode($name) . "*",
            "rows" => 100,
            "indent" => "true"
        ]);
    } else {
        $qs = http_build_query([
            "q" => "id:" . rawurlencode($cif),
            "rows" => 1,
            "indent" => "true"
        ]);
    }

    $url = "$base?$qs";
    error_log("COMPANY URL: $url");

    $solr = fetchJson($url, $SOLR_USER, $SOLR_PASS, 4);

    $numFound = $solr['response']['numFound'] ?? 0;
    if ($numFound === 0) {
        http_response_code(404);
        echo json_encode(["error" => "Company not found"]);
        exit;
    }

    $doc = $solr['response']['docs'][0] ?? [];

    $doc = array_map(function($value) {
        if ($value === '-' || $value === '' || $value === null) {
            return '';
        }
        return $value;
    }, $doc);

    echo json_encode([
        'company' => $doc
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("COMPANY FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Company core unavailable',
        'details' => $e->getMessage()
    ]);
}
