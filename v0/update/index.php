<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ensure the request is PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        "error" => "Only PUT method is allowed",
        "code" => 405
    ]);
    exit;
}

// Validate Content-Type header
$contentType = $_SERVER["CONTENT_TYPE"] ?? $_SERVER["HTTP_CONTENT_TYPE"] ?? '';
if (stripos($contentType, 'application/json') === false) {
    http_response_code(415); // Unsupported Media Type
    echo json_encode([
        "error" => "Content-Type must be application/json",
        "code" => 415
    ]);
    exit;
}

function city_fix($in)
{
    $output = $in;
    $output = str_replace("Bucharest", "București", $output);
    $output = str_replace("Brasov", "Brașov", $output);
    $output = str_replace("Timisoara", "Timișoara", $output);
    $output = str_replace("Pitesti", "Pitești", $output);
    $output = str_replace("Iasi", "Iași", $output);
    $output = str_replace("Targu Mures", "Târgu Mureș", $output);
    $output = str_replace("Cluj Napoca", "Cluj-Napoca", $output);

    return $output;
}

$method = 'PUT';

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

$core  = 'jobs';
$command = '/update';

$qs = '?';
$qs = $qs . '_=1617366504771';
$qs = $qs . '&';
$qs = $qs . 'commitWithin=1000';
$qs = $qs . '&';
$qs = $qs . 'overwrite=true';
$qs = $qs . '&';
$qs = $qs . 'wt=json';

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;

// Prepare for cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Authorization: Basic " . base64_encode("$username:$password")
));

$putdata = fopen("php://input", "r");
$raw_data = '';
while ($data = fread($putdata, 1024)) {
    $raw_data .= $data;
}
fclose($putdata);

$data = json_decode($raw_data);
$job_link = isset($data->job_link) ? htmlspecialchars($data->job_link) : null;
$job_title = isset($data->job_title) ? htmlspecialchars($data->job_title) : null;
$company = isset($data->company) ? htmlspecialchars($data->company) : null;
$country = isset($data->country) ? htmlspecialchars($data->country) : null;
$city = isset($data->city) ? htmlspecialchars($data->city) : null;
$county = isset($data->county) ? htmlspecialchars($data->county) : null;
$remote = isset($data->remote) ? htmlspecialchars($data->remote) : null;

if (!$job_link || !$job_title || !$company) {
    http_response_code(400);
    echo json_encode([
        "error" => "Missing required fields: job_link, job_title, company",
        "code" => 400
    ]);
    exit;
}

// Create data for Solr
$item = new stdClass();
$item->id = md5($item->job_link)."";
$item->job_link = trim($job_link);
$item->job_title = trim($job_title);
$item->company = trim($company);
$item->country = $country ? str_ireplace("Romania", "România", trim($country)) : null;
$item->city = $city ? city_fix(trim($city)) : null;  // Apply city fix if exists
$item->county = $county ? trim($county) : null;
$item->remote = $remote ? trim($remote) : null;

$data = json_encode([$item]);

// Set cURL options for POST
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Content-Type: application/json",
    "Authorization: Basic " . base64_encode("$username:$password")
));

// Execute the cURL request and capture the response
$response = curl_exec($ch);

// Check for errors in cURL execution
if (curl_errno($ch)) {
    http_response_code(503);
    echo json_encode([
        "error" => "SOLR server in DEV is down",
        "code" => 503
    ]);
    curl_close($ch);
    exit;
}

// Check if Solr returns a successful response
$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($response_code !== 200) {
    http_response_code(503);
    echo json_encode([
        "error" => "SOLR server in DEV is down",
        "code" => 503
    ]);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Return success response
echo json_encode(["success" => "Data successfully inserted into Solr"]);
