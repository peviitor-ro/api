<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../bootstrap.php';
$GLOBALS['solr'] = getSolrCredentials('LOCAL');

// Allow only PUT requests
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only PUT method is allowed"]);
    exit;
}

$solr = $GLOBALS['solr'] ?? null;
$authHeader = $GLOBALS['authHeader'] ?? null;

if (!$solr || !$authHeader) {
    echo json_encode(["error" => "Solr credentials or auth header not set"]);
    exit;
}

$server = $solr['server'];
$username = $solr['username'];
$password = $solr['password'];

// If server is not set, stop execution
if (!$server) {
    http_response_code(500);
    echo json_encode(["error" => "LOCAL_SERVER is not set in api.env"]);
    exit;
}

function judet_fix($in)
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

$core  = 'firme';
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
$id = isset($data->id) ? $data->id : null;
$cui = $id;
$denumire = isset($data->denumire) ? htmlspecialchars($data->denumire) : null;
$cod_inmatriculare = isset($data->cod_inmatriculare) ? htmlspecialchars($data->cod_inmatriculare) : null;
$euid = isset($data->euid) ? htmlspecialchars($data->euid) : null;
$localitate = isset($data->localitate) ? htmlspecialchars($data->localitate) : null;
$adresa_completa = isset($data->adresa_completa) ? htmlspecialchars($data->adresa_completa) : null;
$judet = isset($data->judet) ? htmlspecialchars($data->judet) : null;
$cod_stare = isset($data->cod_stare) ? $data->cod_stare : null;

$brands = isset($data->brands) ? htmlspecialchars($data->brands) : null;
$scraper = isset($data->scraper) ? htmlspecialchars($data->scraper) : null;
$website = isset($data->website) ? htmlspecialchars($data->website) : null;
$email = isset($data->email) ? htmlspecialchars($data->email) : null;
$phone = isset($data->phone) ? htmlspecialchars($data->phone) : null;


if (!$id || !$denumire || !$cod_inmatriculare || !$euid || !$adresa_completa || !$localitate || !$judet || !$cod_stare) {
    http_response_code(400);
    echo json_encode([
        "error" => "Missing required fields: id, denumire, cod_inmatriculare, euid, adresa_completa, localitate, judet or cod_stare. ",
        "code" => 400
    ]);
    exit;
}

// id and cod_stare validation
if (!is_int($id) || !is_int($cod_stare))
{
    http_response_code(400);
    echo json_encode([
        "error" => "Fields 'id' and 'cod_stare' must be integers.",
        "code" => 400
    ]);
    exit;
}

// email validation
$invalid_chars = '/[\s\/,<>+=\-:;?"\'\{\}\[\]\|\\\)\(\*&^%$#!~`]/';

if (!preg_match('/^[a-zA-Z0-9._%+-]{3,}@[a-zA-Z0-9.-]/', $email) || preg_match($invalid_chars, $email)) {
    http_response_code(400);  
    echo json_encode(["error" => "Invalid email format: special characters and spaces are not allowed. Please try something like `local-part@domain`", "received" => $email]);
    exit;
}  

// phone validation
$phone_cleaned = preg_replace('/[\s\-\(\)]+/', '', $phone);
if (!preg_match('/^\+?[0-9]{7,15}$/', $phone_cleaned)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid phone format: only digits, spaces, dashes, parentheses and an optional leading + are allowed.", "received" => $phone]);
    exit;
}

// website validation
if (!filter_var($website, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid website URL format.", "received" => $website]);
    exit;
}

// Create data for Solr
$item = new stdClass();
$item->id = trim($id);
$item->cui = trim($cui);
$item->denumire = trim($denumire);
$item->cod_inmatriculare = trim($cod_inmatriculare);
$item->euid = trim($euid);
$item->localitate = trim($localitate);
$item->adresa_completa = trim($adresa_completa);
$item->judet = judet_fix(trim($judet));
$item->brands = trim($brands ?? '');
$item->scraper = trim($scraper ?? '');
$item->website = trim($website ?? '');
$item->email = trim($email ?? '');
$item->phone = trim($phone ?? '');
$item->cod_stare = trim($cod_stare);

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