<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only POST method is allowed"]);
    exit;
}

$method = 'POST';

// Load variables from the api.env file
require_once __DIR__ . '/../../../includes/loadEnv.php';
loadEnv(__DIR__ . '/../../../api.env');

// Retrieve SOLR variables from environment
$server = getenv('LOCAL_SERVER') ?: ($_SERVER['LOCAL_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

// Set up authentication header
$authHeader = base64_encode("$username:$password");

// Debugging: Check if the server is set
if (!$server) {
    die(json_encode(["error" => "LOCAL_SERVER is not set in api.env"]));
}
 
$core  = 'logo';
$command = '/update';

$qs = '?indent=true&q.op=OR&q=*%3A*&useParams=';

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;

// Fetch parameters from query string
$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Invalid JSON format."]);
    exit;
}

$id = isset($input['id']) ? trim($input['id']) : null;
$logo = isset($input['logo']) ? trim(htmlspecialchars($input['logo'])) : null;

if (!$id || !$logo) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing required parameters: id and logo."]);
    exit;
}

if (!preg_match('/^https?:\/\/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(\/.*)?$/', $logo)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid logo URL. It must start with http:// or https:// and be a valid domain.",
        "received" => $logo
    ]);
    exit;   
}

// Step: Check if logo already exists with a different ID
$checkUrl = 'http://' . $server . '/solr/' . $core . '/select?q=logo:"' . urlencode($logo) . '"&wt=json';

$checkCh = curl_init();
curl_setopt($checkCh, CURLOPT_URL, $checkUrl);
curl_setopt($checkCh, CURLOPT_RETURNTRANSFER, true);
curl_setopt($checkCh, CURLOPT_HTTPHEADER, [
    "Authorization: Basic $authHeader"
]);

$checkResponse = curl_exec($checkCh);
curl_close($checkCh);

if (!$checkResponse) {
    http_response_code(500);
    echo json_encode(["error" => "Error querying Solr for existing logo."]);
    exit;
}

$checkData = json_decode($checkResponse, true);

if (isset($checkData['response']['docs']) && count($checkData['response']['docs']) > 0) {
    http_response_code(409); // Conflict
    echo json_encode([
        "error" => "No updates to be made. The logo and ID combination already exists."
    ]);
    exit;
}

if (isset($checkData['response']['docs'])) {
    foreach ($checkData['response']['docs'] as $doc) {
        if (isset($doc['id']) && $doc['id'] !== $id) {
            http_response_code(409); // Conflict
            echo json_encode([
                "error" => "This logo is already registered with another company."
            ]);
            exit;
        }
    }
}

// Set up data for Solr
$item = new stdClass();
$item->id = $id;
$item->logo = $logo;

$data = json_encode([$item]);

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
