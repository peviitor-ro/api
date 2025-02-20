<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
   http_response_code(200);
   exit;
}

// Ensure the request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
   http_response_code(405); // Method Not Allowed
   echo json_encode(["error" => "Only GET method is allowed"]);
   exit;
}

require_once '../config.php';

$core = "jobs";

$qs = '?';
$qs = $qs . 'q=*:*';
$qs .= '&rows=0';
$qs .= '&facet=true';
$qs .= '&facet.field=company_str';
$qs .= '&facet.mincount=1';
$qs .= '&facet.limit=1000000';

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

$context = stream_context_create([
   'http' => [
       'header' => "Authorization: Basic " . base64_encode("$username:$password")
   ]
]);

// Fetch data from Solr
$string = @file_get_contents($url, false, $context);

// Check if Solr is down (server not responding)
if ($string == false) {
   http_response_code(503);
   echo json_encode([
       "error" => "SOLR server in DEV is down",
       "code" => 503
   ]);
   exit;
}

$json = json_decode($string, true);

$companies = $json['facet_counts']['facet_fields']['company_str'];

$results =  new stdClass();
$results->total = count($companies) / 2;
$results->companies = array();

for ($i = 0; $i < count($companies) / 2; $i++) {
   $k = 2 * $i;
   $l = 2 * $i + 1;
   $obj = new stdClass();
   if ($_GET['count'] == 'false') {
      $obj->name = $companies[$k];
   } else if ($_GET['count'] == 'true') {
      $obj->name = $companies[$k];
      $obj->jobs = $companies[$l];
   } else {
      http_response_code(400);
      echo json_encode(["error" => "Missing or wrong required field: count"]);
      exit;
   }
   $results->companies[$i] = new stdClass();
   $results->companies[$i] = $obj;
}
//echo json_encode($results, JSON_PRETTY_PRINT);
echo json_encode($results);
