<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

// Ensure the request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

require_once '../config.php';

$core = "jobs";

$qs = '?facet.field=company_str';
$qs .= '&facet.limit=2000000';
$qs .= '&facet=true';
$qs .= '&fl=company';
$qs .= '&indent=true';
$qs .= '&q.op=OR';
$qs .= '&q=*%3A*';
$qs .= '&rows=0';
$qs .= '&start=0';
$qs .= '&useParams=';

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

// Decode JSON response
$json = json_decode($string, true);

$companies = $json['facet_counts']['facet_fields']['company_str'] ?? '';

// If JSON is invalid or Solr returned an error, return error (but NOT 503)
if ($json === null || !isset($json['facet_counts']['facet_fields']['company_str'])) {
    http_response_code(500);
    echo json_encode([
        "error" => "Invalid response from Solr",
        "code" => 500
    ]);
    exit;
}

// Count companies with at least one job
$companyCount = 0;
for ($i = 1; $i < count($companies); $i += 2) {
    if ($companies[$i] > 0) {
        $companyCount++;
    }
}

// Prepare response
$obj = new stdClass();
$obj->total = new stdClass();
$obj->total->jobs = (int) ($json['response']['numFound'] ?? 0);
$obj->total->companies = (int) $companyCount;


echo json_encode($obj);
?>
