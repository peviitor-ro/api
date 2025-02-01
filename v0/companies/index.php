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

$string = file_get_contents($url);
$json = json_decode($string, true);

$companies = $json['facet_counts']['facet_fields']['company_str'];

$results =  new stdClass();
$results->total = count($companies)/2;
$results->companies = array();

for($i=0;$i<count($companies)/2;$i++) {
    $k=2*$i;
    $l=2*$i+1;
    $obj = new stdClass();
    $obj->name = $companies[$k];   
    if (isset($_GET['count']))
     if ($_GET['count']=='true')
       {
    $obj->jobs = $companies[$l];
       }
    $results->companies[$i] = new stdClass();
    $results->companies[$i] = $obj;  
 }
//echo json_encode($results, JSON_PRETTY_PRINT);
echo json_encode($results);

?>
