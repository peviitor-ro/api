<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

$method = 'POST';

require_once '../../config.php';

$core  = 'auth';
$command = '/update';

$qs = '?';
$qs .= '_=1617366504771';
$qs .= '&commitWithin=1000';
$qs .= '&overwrite=true';
$qs .= '&wt=json';

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;

$string = @file_get_contents($url);
if ($string === FALSE) {
    http_response_code(503);
    echo json_encode([
        "error" => "SOLR server in DEV is down",
        "code" => 503
    ]);
    exit;
}

// Fetch parameters from query string
$id = isset($_GET['id']) ? trim(urlencode($_GET['id'])) : null;
$logo = isset($_GET['logo']) ? trim(htmlspecialchars($_GET['logo'])) : null;


// Validate required fields
if (!$id || !$logo) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing required parameters"]);
    exit;
}

// Create data for Solr
$item = new stdClass();
$item->id = $id;
$item->logo = $logo;


$data = json_encode([$item]);

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    )
);

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Failed to insert data into Solr"]);
    exit;
}

// Return success response
echo json_encode(["success" => "Data successfully inserted into Solr"]);
