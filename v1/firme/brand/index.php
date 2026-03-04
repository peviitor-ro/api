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

    if (!isset($_GET['brand']) || $_GET['brand'] === '') {
        http_response_code(400);
        echo json_encode(["error" => "Missing brand parameter"]);
        exit;
    }

    $brand = strtolower(urldecode($_GET['brand']));

    $core = 'company';
    $base = "https://$PROD_SERVER/solr/$core/select";

    $url = $base . '?' . http_build_query([
        "indent" => "true",
        "q.op" => "OR",
        "q" => 'brands:"' . $brand . '"',
        "fl" => "company,id",
        "wt" => "json"
    ]);

    error_log("FIRME BRAND URL: $url");

    $response = fetchJson($url, $SOLR_USER, $SOLR_PASS, 4);

    if (isset($response['response']['docs'][0])) {
        $doc = $response['response']['docs'][0];
        echo json_encode([
            "id" => $doc['id'] ?? null,
            "company" => $doc['company'] ?? null
        ]);
        exit;
    }

    $url2 = $base . '?' . http_build_query([
        "indent" => "true",
        "q.op" => "OR",
        "q" => 'company:"' . $brand . '"',
        "fl" => "company,id",
        "wt" => "json"
    ]);

    error_log("FIRME BRAND URL2: $url2");

    $response2 = fetchJson($url2, $SOLR_USER, $SOLR_PASS, 4);

    if (isset($response2['response']['docs'][0])) {
        $doc2 = $response2['response']['docs'][0];
        echo json_encode([
            "id" => $doc2['id'] ?? null,
            "company" => $doc2['company'] ?? null
        ]);
    } else {
        echo json_encode([]);
    }

} catch (Exception $e) {
    error_log("FIRME BRAND FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Company core unavailable',
        'details' => $e->getMessage()
    ]);
}
