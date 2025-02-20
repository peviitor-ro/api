<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';

$core = 'jobs';

try {
    // Verifică prezența parametrului 'q'
    if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
        echo json_encode(['message' => 'No query provided']);
        exit;
    }
    $query = trim($_GET['q']);

    // Construiește URL-ul
    $url = "http://" . $server . "/solr/" . $core . "/suggest?suggest=true&suggest.build=true&suggest.dictionary=jobTitleSuggester&suggest.q=" . urlencode($query) . "&wt=json";

    // Inițializează cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    // Execută request-ul
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Verifică dacă există o eroare la cURL
    if ($response === false) {
        http_response_code(503);
        echo json_encode([
            "error" => "SOLR server in DEV is down or unreachable",
            "details" => $curl_error,
            "code" => 503
        ]);
        exit;
    }

    // Verifică dacă Solr a răspuns cu un cod valid
    if ($http_code !== 200) {
        http_response_code($http_code);
        echo json_encode([
            "error" => "Solr request failed",
            "http_code" => $http_code
        ]);
        exit;
    }

    // Decodifică JSON-ul primit
    $jsonArray = json_decode($response, true);

    if (empty($jsonArray['suggest']['jobTitleSuggester'][$query]['suggestions'])) {
        echo json_encode(['message' => 'No suggestions found']);
        exit;
    }

    // Extrage sugestiile
    $suggestions = $jsonArray['suggest']['jobTitleSuggester'][$query]['suggestions'];
    echo json_encode(['suggestions' => $suggestions], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'code' => $e->getCode()]);
    exit;
}
?>
