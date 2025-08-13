<?php
require_once __DIR__ . '/../bootstrap.php';

// Asigură că metoda HTTP este GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

$solr = $GLOBALS['solr'] ?? null;
$authHeader = $GLOBALS['authHeader'] ?? null;

if (!$solr || !$authHeader) {
    echo json_encode(["error" => "Solr credentials or auth header not set"]);
    exit;
}

$server = $solr['server'];
$username = $solr['username'];
$password = $solr['password'];

$core = 'jobs';

try {
    // Set up the HTTP context for the request
    $context = stream_context_create([
        'http' => [
            'header' => $authHeader
        ]
    ]);

    // Cerere pentru numărul total de documente
    $url = 'http://' . $server . '/solr/' . $core . '/select?q=' . urlencode('*:*') . '&rows=0';

    $string = @file_get_contents($url, false, $context);
    if ($string === false) {
        header("HTTP/1.1 503 Service Unavailable");
        throw new Exception('SOLR server is down', 503);
    }

    $json = json_decode($string, true);
    $max = $json['response']['numFound'];

    if ($max == 0) {
        echo json_encode(['message' => 'There are no jobs to display']);
        exit;
    }

    // Selectează un document aleatoriu
    $start = rand(0, $max - 1);

    $url = 'http://' . $server . '/solr/' . $core . '/select?q=' . urlencode('*:*') . '&rows=1' . '&start=' . $start . '&omitHeader=true';

    $json = @file_get_contents($url, false, $context);
    if ($json === false) {
        list($version, $status, $msg) = explode(' ', $http_response_header[0], 3);
        header("HTTP/1.1 503 Service Unavailable");
        throw new Exception('Your call to Solr failed and returned HTTP status: ' . $status, $status);
    }

    $jsonArray = json_decode($json, true);
    unset($jsonArray['response']['docs'][0]['_version_']);
    echo json_encode($jsonArray, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'code' => $e->getCode()]);
    exit;
}
