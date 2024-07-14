<?php
header("Access-Control-Allow-Origin: *");

require_once '../config.php';

$method = 'POST';
$core  = 'jobs';
$command = '/update';

$qs = '?_=' . time() . '&commitWithin=1000&overwrite=true&wt=json';

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;

$data = json_encode(['delete' => ['query' => '*:*']]); //New Format

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    )
);

$context  = stream_context_create($options);

try {

    $json = @file_get_contents($url, false, $context);

    if ($json === FALSE) {
        list($version, $status, $msg) = explode(' ', $http_response_header[0], 3);
        // Force HTTP status code to be 503
        header("HTTP/1.1 503 Service Unavailable");
        throw new Exception('Your call to Solr failed and returned HTTP status: ' . $status, $status);
    }

    echo $json;
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'code' => $e->getCode()]);
}
?>