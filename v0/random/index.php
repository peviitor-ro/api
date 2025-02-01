<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';

$core = 'jobs';

try {
    // Check for authorization (example condition)
    $userAuthorized = false; // Change this based on your authorization logic
    if (!$userAuthorized) {
        // Return 403 Forbidden if user is not authorized
        header("HTTP/1.1 403 Forbidden");
        echo json_encode(['error' => 'PHP code is not found.']);
        exit;
    }

    // Request the total number of documents
    $url = 'http://' . $server . '/solr/' . $core . '/select?q=' . urlencode('*:*') . '&rows=0';
    $string = @file_get_contents($url);
    if ($string === FALSE) {
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
    $json = @file_get_contents($url);
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
?>
