<?php
header("Access-Control-Allow-Origin: *");

require_once '../../config.php';

$core = 'auth';
$command = '/update';

$qs = '?_=' . time(); // Use current time as a cache buster
$qs .= '&commitWithin=1000';
$qs .= '&overwrite=true';
$qs .= '&wt=json';

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;

$company = $_POST['company'];

if (empty($company)) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['error' => 'Company name is required', 'code' => 400]);
    exit;
}

$data = json_encode(['delete' => ['query' => 'id:' . $company]]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data)
]);

$response = curl_exec($ch);

if ($response === false) {
    $error_message = curl_error($ch);
    curl_close($ch);
    header("HTTP/1.1 503 Service Unavailable");
    echo json_encode(['error' => 'Failed to execute request: ' . $error_message, 'code' => 503]);
    exit;
}

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code >= 400) {
    header("HTTP/1.1 " . $http_code . " Bad Request");
    echo $response;
    exit;
}

echo $response;

?>