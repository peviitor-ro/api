<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';

$core = 'jobs';

try {
    // Verifică prezența și validitatea parametrului de query 'q'
    if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
        echo json_encode(['message' => 'No query provided']);
        exit;
    }
    $query = trim($_GET['q']);

    // Request suggestions from Solr
    $url = 'http://' . $server . '/solr/' . $core . '/suggest?suggest=true&suggest.build=true&suggest.dictionary=jobTitleSuggester&suggest.q=' . urlencode($query) . '&wt=json';

    $string = @file_get_contents($url);
    if ($string === FALSE) {
        http_response_code(503);
        echo json_encode([
            "error" => "SOLR server in DEV is down",
            "code" => 503
        ]);
        exit;
    }

    $jsonArray = json_decode($response, true);

    if (empty($jsonArray['suggest']['jobTitleSuggester'][$query]['suggestions'])) {
        echo json_encode(['message' => 'No suggestions found']);
        exit;
    }

    // Extracting suggestions and sending them back as JSON
    $suggestions = $jsonArray['suggest']['jobTitleSuggester'][$query]['suggestions'];
    echo json_encode(['suggestions' => $suggestions], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'code' => $e->getCode()]);
    exit;
}
