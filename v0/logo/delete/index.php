<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

require_once '../../config.php';

$core  = 'logo';
$command = '/update';

$qs = '?_=' . time(); // Use current time as a cache buster
$qs .= '?indent=true&q.op=OR&q=*%3A*&useParams=';

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;

$context = stream_context_create([
    'http' => [
        'header' => "Authorization: Basic " . base64_encode("$username:$password")
    ]
]);

// Fetch data from Solr
$string = @file_get_contents($url, false, $context);

// Get the required parameters
$id = $_GET['id'] ?? ''; // Document ID to update
$field = 'url';          // Field to remove (hardcoded for this endpoint)

if (empty($id)) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['error' => 'Document ID is required', 'code' => 400]);
    exit;
}

// Create the atomic update payload
$data = json_encode([
    [
        "id" => $id,
        $field => ["set" => null] // Use Solr's atomic update syntax to remove the field
    ]
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data)
]);

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code >= 400) {
    header("HTTP/1.1 " . $http_code . " Bad Request");
    echo $response;
    exit;
}

echo $response;
