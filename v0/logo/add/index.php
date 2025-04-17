<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only POST method is allowed"]);
    exit;
}

$method = 'POST';

// Load variables from the .env file
function loadEnv($file) {
    $file = realpath($file); 

    // Check if the .env file exists
    if (!$file || !file_exists($file)) {
        die(json_encode(["error" => "The .env file does not exist!", "path" => $file]));
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

// Load .env file
loadEnv('../../../.env');

// Retrieve SOLR variables from environment
$server = getenv('LOCAL_SERVER') ?: ($_SERVER['LOCAL_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

// Debugging: Check if the server is set
if (!$server) {
    die(json_encode(["error" => "LOCAL_SERVER is not set in .env"]));
}
 
$core  = 'logo';
$command = '/update';

$qs = '?indent=true&q.op=OR&q=*%3A*&useParams=';

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;

// Fetch parameters from query string
$id = isset($_GET['id']) ? trim($_GET['id']) : null;
$logo = isset($_GET['logo']) ? trim(htmlspecialchars($_GET['logo'])) : null;

// Validate required fields
if (!$id || !$logo) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing required parameters."]);
    exit;
}

if (!preg_match('/^www.[a-zA-Z0-9.-]+\.[a-zA-Z0-9]{2,}$/', $logo)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid ID format. It must be in the format: 'www.', then at least 1 character, then '.', and at least 2 characters.",
        "received" => $logo
    ]);
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
