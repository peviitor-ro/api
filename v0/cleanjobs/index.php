<?php
header("Access-Control-Allow-Origin: *");

// Fetch company from DELETE request
parse_str(file_get_contents("php://input"), $_DELETE);
$company = $_DELETE['company'] ?? '';

require_once '../config.php';

$method = 'DELETE';
$core  = 'jobs';
$command = '/update';

$qs = '?';
$qs = $qs . '_=1617366504771';
$qs = $qs . '&';
$qs = $qs . 'commitWithin=1000';
$qs = $qs . '&';
$qs = $qs . 'overwrite=true';
$qs = $qs . '&';
$qs = $qs . 'wt=json';

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;

$data = json_encode(['delete' => ['query' => 'hiringOrganization.name:"' . $company . '"']]);

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    )
);

$context  = stream_context_create($options);

try {
    if (empty($company)) {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(['error' => 'Company name is required', 'code' => 400]);
        exit;
    }
    $json = @file_get_contents($url, false, $context);

    if ($json === FALSE) {
        list($version, $status, $msg) = explode(' ', $http_response_header[0], 3);
        header("HTTP/1.1 503 Service Unavailable");
        throw new Exception('Your call to Solr failed and returned HTTP status: ' . $status, $status);
    }

    echo $json;
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'code' => $e->getCode()]);
    exit;
}
