<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';

$core = 'jobs';
$qs = '?';
$qs = $qs . $_SERVER['QUERY_STRING'];

if ($_SERVER['QUERY_STRING'] == "page=1") {
    $qs .= "&q=%22*%3A*%22"; // Enclose the search query in quotes
} else {
    // Ensure other queries are also enclosed in quotes
    parse_str($_SERVER['QUERY_STRING'], $queryParams);
    if (isset($queryParams['q'])) {
        // Construct the query string in the required format
        $query = $queryParams['q'];
        $queryParams['q'] = '"' . $query . '"';
        $qs = '?' . http_build_query($queryParams);
    }
}

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

$string = @file_get_contents($url);
if ($string === FALSE) {
    http_response_code(503);
    echo json_encode([
        "error" => "SOLR server in DEV is down",
        "code" => 503
    ]);
    exit;
}
$data = json_decode($json, true);

if (isset($data['response']['numFound']) && $data['response']['numFound'] == 0) {
    // Return a 404 error with a custom message if no results are found
    http_response_code(404);
    echo json_encode([
        "error" => "This job is not in the Database",
        "code" => 404
    ]);
} else {
    // Return the original response if results are found
    echo $json;
}
