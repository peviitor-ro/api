<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../util/loadEnv.php';
require_once __DIR__ . '/../../../util/http.php';
loadEnv(__DIR__ . '/../../../api.env');

$SOLR_SERVER = trim(getenv('SOLR_SERVER') ?: '');
$PROTOCOL = trim(getenv('PROTOCOL') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Only GET method allowed"]);
    exit;
}


try {
    if (!$SOLR_SERVER) {
        throw new Exception("SOLR_SERVER not set");
    }

    if (!isset($_GET['brand']) || $_GET['brand'] === '') {
        http_response_code(400);
        echo json_encode(["error" => "Missing brand parameter"]);
        exit;
    }

    $brand = strtolower(urldecode($_GET['brand']));

    $core = 'company';
    $base = "$PROTOCOL://$SOLR_SERVER/solr/$core/select";

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
