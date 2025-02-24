<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

$method = 'POST';

require_once '../../config.php';  // Asigură-te că fișierul config.php conține corect username și password

$core  = 'auth';
$command = '/update';

$qs = '?';
$qs .= '_=1617366504771';
$qs .= '&commitWithin=1000';
$qs .= '&overwrite=true';
$qs .= '&wt=json';

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;

// Fetch parameters from query string
$id = isset($_GET['id']) ? trim(urlencode($_GET['id'])) : null;
$logo = isset($_GET['logo']) ? trim(htmlspecialchars($_GET['logo'])) : null;

// Validate required fields
if (!$id || !$logo) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing required parameters"]);
    exit;
}

// Set up data for Solr
$item = new stdClass();
$item->id = $id;
$item->logo = $logo;

$data = json_encode([$item]);

// Set up authentication header
$authHeader = base64_encode("$username:$password");

// Use curl to send the POST request to Solr
$ch = curl_init();

// Set curl options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Basic $authHeader"
]);

// Execute the request and get the result
$result = curl_exec($ch);

// Check for curl errors
if (curl_errno($ch)) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Failed to insert data into Solr: " . curl_error($ch)]);
    exit;
}

// Close curl session
curl_close($ch);

// Check if the request was successful
if ($result === FALSE) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Failed to insert data into Solr"]);
    exit;
}

// Return success response
echo json_encode(["success" => "Data successfully inserted into Solr"]);
