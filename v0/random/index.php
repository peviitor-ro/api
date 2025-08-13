<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ensure the request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

// Retrieve SOLR variables from environment
$server = getenv('LOCAL_SERVER') ?: ($_SERVER['LOCAL_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

// Debugging: Check if the server is set
if (!$server) {
    die(json_encode(["error" => "LOCAL_SERVER is not set in api.env"]));
}
$core = 'jobs';

try {
    // Request the total number of documents
    $url = 'http://' . $server . '/solr/' . $core . '/select?q=' . urlencode('*:*') . '&rows=0';

    $context = stream_context_create([
        'http' => [
            'header' => "Authorization: Basic " . base64_encode("$username:$password")
        ]
    ]);
    
    $string = @file_get_contents($url, false, $context);
    if ($string === "false") {
        // Force HTTP status code to be 503
        header("HTTP/1.1 503 Service Unavailable");
        throw new Exception('SOLR server in DEV is down', 503);
    }

    $json = json_decode($string, true);
    $max = $json['response']['numFound'];

    // If no documents are found, return a specific message
    if ($max == 0) {
        echo json_encode(['message' => 'There are no jobs to display']);
        exit;
    }

    // Randomly select a document if there are jobs
    $start = rand(0, $max - 1);

    $url = 'http://' . $server . '/solr/' . $core . '/select?q=' . urlencode('*:*') . '&rows=1' . '&start=' . $start . '&omitHeader=true';
    $json = @file_get_contents($url, false, $context);
    if ($json === FALSE) {
        list($version, $status, $msg) = explode(' ', $http_response_header[0], 3);
        // Force HTTP status code to be 503
        header("HTTP/1.1 503 Service Unavailable");
        throw new Exception('Your call to Solr failed and returned HTTP status: ' . $status, $status);
    }

    $jsonArray = json_decode($json, true);
    unset($jsonArray['response']['docs'][0]['_version_']);
    $newJson = json_encode($jsonArray, JSON_PRETTY_PRINT);
    echo $newJson;
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'code' => $e->getCode()]);
    exit;
}
