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

    if (!isset($_GET['website']) || $_GET['website'] === '') {
        http_response_code(400);
        echo json_encode(["error" => "Missing website parameter"]);
        exit;
    }

    $website = $_GET['website'];

    $core = 'company';
    $url = "$PROTOCOL://$SOLR_SERVER/solr/$core/select?" . http_build_query([
        "indent" => "true",
        "q.op" => "OR",
        "q" => 'website:"' . $website . '"',
        "fl" => "company,id",
        "wt" => "json"
    ]);

    error_log("FIRME WEBSITE URL: $url");

    $response = fetchJson($url, $SOLR_USER, $SOLR_PASS, 4);

    if (isset($response['response']['docs'])) {
        echo json_encode($response['response']['docs']);
    } else {
        echo json_encode([]);
    }

} catch (Exception $e) {
    error_log("FIRME WEBSITE FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Company core unavailable',
        'details' => $e->getMessage()
    ]);
}
