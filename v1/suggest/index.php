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

    if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
        echo json_encode(['message' => 'No query provided']);
        exit;
    }
    $query = trim($_GET['q']);

    $core = 'job';
    $url = "$PROTOCOL://$SOLR_SERVER/solr/$core/suggest"
         . "?suggest=true"
         . "&suggest.build=true"
         . "&suggest.dictionary=jobTitleSuggester"
         . "&suggest.q=" . urlencode($query)
         . "&wt=json";

    error_log("SUGGEST URL: $url");

    $response = fetchJson($url, $SOLR_USER, $SOLR_PASS, 4);

    if (empty($response['suggest']['jobTitleSuggester'][$query]['suggestions'])) {
        echo json_encode(['message' => 'No suggestions found']);
        exit;
    }

    $suggestions = $response['suggest']['jobTitleSuggester'][$query]['suggestions'];
    echo json_encode(['suggestions' => $suggestions], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("SUGGEST FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}
