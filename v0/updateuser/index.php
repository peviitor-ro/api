<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';

$core = 'auth';

$qs = '?';
$qs .= 'omitHeader=true';
$qs .= '&';
$qs .= 'q.op=OR';
$qs .= '&';
$qs .= 'q=id%3A';

// Retrieve parameters from the query string
$id = isset($_GET['id']) ? trim($_GET['id']) : null;
$urlParam = isset($_GET['url']) ? trim($_GET['url']) : null;
$company = isset($_GET['company']) ? trim($_GET['company']) : null;
$logo = isset($_GET['logo']) ? trim($_GET['logo']) : null;
$apikey = isset($_GET['apikey']) ? trim($_GET['apikey']) : null;


$id = urlencode($id);

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs . $id;

$json = file_get_contents($url);
$json = json_decode($json);
unset($json->response->docs[0]->version);

// Ensure $json->response->docs[0] is an object
if (!isset($json->response->docs[0]) || !is_object($json->response->docs[0])) {
  $json->response->docs[0] = new stdClass();
}

// Populate the Solr document with parameters
$json->response->docs[0]->id = $id;
if ($urlParam) {
  $json->response->docs[0]->url = $urlParam;
}
if ($company) {
  $json->response->docs[0]->company = $company;
}
if ($logo) {
  $json->response->docs[0]->logo = $logo;
}
if ($apikey) {
  $json->response->docs[0]->apikey = $apikey;
}

$method = 'PUT';
$command = '/update';

$qs = '?';
$qs .= '_=1617366504771';
$qs .= '&';
$qs .= 'commitWithin=1000';
$qs .= '&';
$qs .= 'overwrite=true';
$qs .= '&';
$qs .= 'wt=json';

$data = "[" . json_encode($json->response->docs[0]) . "]";

$options = array(
  'http' => array(
    'header'  => "Content-type: application/json\r\n",
    'method'  => 'POST',
    'content' => $data
  )
);

$context = stream_context_create($options);

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;

$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
  http_response_code(500); // Set response code to 500 Internal Server Error
  echo json_encode(["message" => "Failed to update Solr."]);
  exit;
}

echo $data;
