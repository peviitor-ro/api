<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

// Ensure the request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only POST method is allowed"]);
    exit;
}

$method = 'POST';

// Load variables from the api.env file
function loadEnv($file) {
    $file = realpath($file); 

    // Check if the api.env file exists
    if (!$file || !file_exists($file)) {
        die(json_encode(["error" => "The api.env file does not exist!", "path" => $file]));
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments (lines starting with '#')
        if (strpos(trim($line), '#') === 0) continue;

        // Split the line into key and value, and add to environment
        list($key, $value) = explode('=', $line, 2) + [NULL, NULL];
        if ($key && $value) {
            $key = trim($key);
            $value = trim($value);
            $_SERVER[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Load api.env file
loadEnv('../../../api.env');

// Retrieve SOLR variables from environment
$server = getenv('LOCAL_SERVER') ?: ($_SERVER['LOCAL_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

// Debugging: Check if the server is set
if (!$server) {
    die(json_encode(["error" => "LOCAL_SERVER is not set in api.env"]));
}

$core = "firme";

$qs = '?';
$qs .= 'omitHeader=true&';
$qs .= '?fl=denumire%2C%20id';
$qs .= '&indent=true';
$qs .= '&q.op=OR';
$qs .= '&q=';

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs . $brand;

$context = stream_context_create([
   'http' => [
       'header' => "Authorization: Basic " . base64_encode("$username:$password")
   ]
]);

// Fetch data from Solr
$string = file_get_contents($url, false, $context);

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
