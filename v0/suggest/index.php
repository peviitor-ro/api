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
    $context = stream_context_create([
        'http' => [
            'header' => "Authorization: Basic " . base64_encode("$username:$password")
        ]
    ]);

    // Fetch data from Solr
    $response = @file_get_contents($url, false, $context);

    // Decodifică răspunsul JSON
    $jsonArray = json_decode($response, true);

    // Verifică structura JSON și prezența sugestiilor
    if (!isset($jsonArray['suggest']['jobTitleSuggester']) || 
        !isset($jsonArray['suggest']['jobTitleSuggester'][$query]) || 
        empty($jsonArray['suggest']['jobTitleSuggester'][$query]['suggestions'])) {
        echo json_encode(['message' => 'No suggestions found']);
        exit;
    }

    // Extrage sugestiile
    $suggestions = $jsonArray['suggest']['jobTitleSuggester'][$query]['suggestions'];

    // Răspunde cu sugestiile
    echo json_encode(['suggestions' => $suggestions], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // În caz de eroare, trimite mesajul de eroare
    echo json_encode(['error' => $e->getMessage(), 'code' => $e->getCode()]);
    exit;
}
