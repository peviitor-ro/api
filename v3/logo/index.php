<?php
header("Access-Control-Allow-Origin: *");

require_once '../config.php';
require_once '../utils/env.php';

// Load environment variables
loadEnv('../../api.env');

// Retrieve SOLR variables from environment
$server = getenv('PROD_SERVER') ?: ($_SERVER['PROD_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

// Function to fetch content with authentication
function file_get_contents_auth($url, $username, $password) {
    $options = [
        "http" => [
            "header" => "Authorization: Basic " . base64_encode("$username:$password")
        ]
    ];
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context); // Suppress warnings

    return $result;
}

// Core 'auth'
$core = 'auth';

$qs = '?';
$qs .= 'indent=true';
$qs .= '&';
$qs .= 'q.op=OR';
$qs .= '&';
$qs .= 'q=logo%3A*';
$qs .= '&';
$qs .= 'rows=1000000';
$qs .= '&';
$qs .= 'omitHeader=true';
$qs .= '&';
$qs .= 'useParams=';

$url =  'http://' . $server . '/solr/' . $core . '/select' . $qs;

// Use the authentication function
$string = file_get_contents_auth($url, $username, $password);

// Error handling
if ($string === false) {
    error_log("Failed to fetch data from $url");
    echo json_encode(['error' => 'Failed to fetch data from Solr']);
    exit;
}

$json = json_decode($string, true);

// Check if JSON decoding was successful
if ($json === null) {
    error_log("JSON decode failed for data from $url");
    echo json_encode(['error' => 'Failed to decode JSON data']);
    exit;
}

$companies = $json['response']['docs'];

$results =  new stdClass();
$results->companies = array();
$results->companies = $companies;

$test = array();
foreach ($companies as $company) {
    $item = strtolower($company["id"]);
    $xurl  =  $company["logo"];
    $url  = $xurl[0];
    $test[$item] = $url;
}

// Core 'jobs'
$core = 'jobs';

$qs = '?';
$qs .= 'facet.field=company_str';
$qs .= '&';
$qs .= 'facet.limit=10000';
$qs .= '&';
$qs .= 'facet=true';
$qs .= '&';
$qs .= 'fl=company';
$qs .= '&facet.sort=index';
$qs .= '&';
$qs .= 'indent=true';
$qs .= '&';
$qs .= 'q.op=OR';
$qs .= '&';
$qs .= 'q=*%3A*';
$qs .= '&';
$qs .= 'rows=0';
$qs .= '&';
$qs .= 'start=0';

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

// Use the authentication function
$string = file_get_contents_auth($url, $username, $password);

// Error handling
if ($string === false) {
    error_log("Failed to fetch data from $url");
    echo json_encode(['error' => 'Failed to fetch data from Solr']);
    exit;
}

$json = json_decode($string, true);

// Check if JSON decoding was successful
if ($json === null) {
    error_log("JSON decode failed for data from $url");
    echo json_encode(['error' => 'Failed to decode JSON data']);
    exit;
}

$companies = $json['facet_counts']['facet_fields']['company_str'];

// Validate $companies before using it
if (!is_array($companies)) {
    error_log("Companies data is not an array");
    echo json_encode(['error' => 'Companies data is not an array']);
    exit;
}

$results =  new stdClass();
$results->total = count($companies) / 2;
$results->companies = array();

for ($i = 0; $i < count($companies) / 2; $i++) {
    $k = 2 * $i;
    $l = 2 * $i + 1;
    $obj = new stdClass();
    $obj->name = $companies[$k];
    if (isset($_GET['count']))
        if ($_GET['count'] == 'true') {
            $obj->jobs = $companies[$l];
        }
    $obj->logo = $test[strtolower($obj->name)];
    $results->companies[$i] = new stdClass();
    $results->companies[$i] = $obj;
}

echo json_encode($results);
?>
