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
require_once __DIR__ . '/../../../util/loadEnv.php';
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

$core = 'logo';
$command = '/update';
$qs = '?indent=true&q.op=OR&q=*%3A*&useParams=';
$url = 'http://' . $server . '/solr/' . $core . $command . $qs;

// Parse JSON input
$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON format."]);
    exit;
}

$id = isset($input['id']) ? trim($input['id']) : null;
$logo = isset($input['logo']) ? trim(htmlspecialchars($input['logo'])) : null;

// Basic presence validation
if (!$id || !$logo) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required parameters: id and logo."]);
    exit;
}

// ------------------- NEW VALIDATION -------------------
$MAX_ID_LENGTH = 100;
$MAX_LOGO_LENGTH = 2048;

$errors = [];

if (strlen($id) > $MAX_ID_LENGTH) {
    $errors['id'] = "id exceeds maximum length of {$MAX_ID_LENGTH} characters";
}

if (strlen($logo) > $MAX_LOGO_LENGTH) {
    $errors['logo'] = "logo exceeds maximum length of {$MAX_LOGO_LENGTH} characters";
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Validation failed",
        "details" => $errors
    ]);
    exit;
}
// ------------------------------------------------------

// Validate logo format
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
    http_response_code(409);
    echo json_encode([
        "error" => "No updates to be made. The logo and ID combination already exists."
    ]);
    exit;
}

if (isset($checkData['response']['docs'])) {
    foreach ($checkData['response']['docs'] as $doc) {
        if (isset($doc['id']) && $doc['id'] !== $id) {
            http_response_code(409);
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

// Send POST to Solr
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Basic $authHeader"
]);

$result = curl_exec($ch);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to insert data into Solr: " . curl_error($ch)]);
    exit;
}

curl_close($ch);

if ($result === FALSE) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to insert data into Solr"]);
    exit;
}

echo json_encode(["success" => "Data successfully inserted into Solr"]);
?>