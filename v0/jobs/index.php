<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

// Ensure the request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

// Load variables from the api.env file
require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

// Retrieve SOLR variables from environment
$server = getenv('LOCAL_SERVER') ?: ($_SERVER['LOCAL_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

// Debugging: Check if the server is set
if (!$server) {
    die(json_encode(["error" => "LOCAL_SERVER is not set in api.env"]));
}

$core = "jobs";

$qs = '?';
$qs = $qs . 'facet=true';
$qs = $qs . '&';
$qs = $qs . 'indent=true';
$qs = $qs . '&';
$qs = $qs . 'q.op=OR';
$qs = $qs . '&';
$qs = $qs . 'q=*%3A*';
$qs = $qs . '&';
$qs = $qs . 'omitHeader=true';
$qs = $qs . '&';
$qs = $qs . 'useParams=';

$page = 100;
$start = 1;
$rows = 100;  
$stop = $start * $page;

if (isset($_GET["start_page"]) && isset($_GET["stop_page"])) {
    $stop = ($_GET["stop_page"] - 1) * $page;
    $start = ($_GET["start_page"] - 1) * $page;
    if (!is_numeric($stop) || $stop < $start) {
        http_response_code(400);
        echo json_encode([
            "error" => "You must provide a positive number for 'stop_page' less than " . ($start/100)+1,
            "code" => 400
        ]);
        exit;
    }
    if (!is_numeric($start) || $start < 0) {
        http_response_code(400);
        echo json_encode([
            "error" => "You must provide a positive number for 'start_page'",
            "code" => 400
        ]);
        exit;
    }
    if ($start === $stop) $rows = 100;
    else $rows = $stop - $start;  
}

$qs = $qs . '&rows=' . $rows . '&start=' . $start;

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

$context = stream_context_create([
    'http' => [
        'header' => "Authorization: Basic " . base64_encode("$username:$password")
    ]
]);

// Fetch data from Solr
$string = @file_get_contents($url, false, $context);

if ($string == false) {
    http_response_code(503);
    echo json_encode([
        "error" => "SOLR server in DEV is down",
        "code" => 503
    ]);
    exit;
}

$json = json_decode($string, true);

// Check for a valid response
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["error" => "Failed to parse Solr response as JSON"]);
    exit;
}

unset($json['facet_counts']);

// Return only relevant part 
echo json_encode($json);
