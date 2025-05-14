<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: *');

require_once '../config.php';
require_once '../utils/env.php';

// Load environment variables
loadEnv('../../api.env');
$server = getenv('PROD_SERVER') ?: ($_SERVER['PROD_SERVER'] ?? null);
$backup = getenv('BACK_SERVER') ?: ($_SERVER['BACK_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);
$context = stream_context_create([
    'http' => [
        'header' => "Authorization: Basic " . base64_encode("$username:$password")
    ]
]);
// Debugging: Check if the server is set
if (!$server) {
    die(json_encode(["error" => "PROD_SERVER is not set in .env"]));
}


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
    $response = @file_get_contents($url, false, $context);
    
    // Check if the request was successful
    if ($response === FALSE) {
        // Force HTTP status code to be 503
        header("HTTP/1.1 503 Service Unavailable");
        throw new Exception('SOLR server is down or request failed', 503);
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
?>