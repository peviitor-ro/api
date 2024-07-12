<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: *');

require_once '../config.php';

$core = 'jobs';

try {
    $url = 'http://' .$server .'/solr/' . $core . '/select?q=' . urlencode('*:*') . '&rows=0';
    $string = @file_get_contents($url);
    if ($string === FALSE) {
        // Force HTTP status code to be 503
        header("HTTP/1.1 503 Service Unavailable");
        throw new Exception('SOLR server in DEV is down', 503);
    }

    $json = json_decode($string, true);
    $max = $json['response']['numFound'];
    $start = rand(0, $max-1); 

    $url = 'http://' .$server .'/solr/' . $core . '/select?q=' . urlencode('*:*') . '&rows=1' . '&start=' . $start . '&omitHeader=true';
    $json = @file_get_contents($url);
    if ($json === FALSE) {
        list($version,$status,$msg) = explode(' ', $http_response_header[0], 3);
        // Force HTTP status code to be 503
        header("HTTP/1.1 503 Service Unavailable");
        throw new Exception('Your call to Solr failed and returned HTTP status: ' . $status, $status);
    }

    echo $json;
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'code' => $e->getCode()]);
    exit;
}
?>