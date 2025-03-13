<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

// Ensure the request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

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
loadEnv('../../.env');

// Retrieve SOLR variables from environment
$server = getenv('SOLR_SERVER') ?: ($_SERVER['SOLR_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

// Debugging: Check if the server is set
if (!$server) {
    die(json_encode(["error" => "SOLR_SERVER is not set in .env"]));
}

$core = "jobs";

// Parametrii pentru interogarea Solr
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

// Parametru pentru numărul de joburi
$rows = 100;  // Valoare implicită
if (isset($_GET["rows"])) {
    $rows = $_GET["rows"];
    if (!is_numeric($rows) || $rows <= 0) {
        // Dacă rows nu este valid, returnează o eroare
        http_response_code(400);
        echo json_encode([
            "error" => "You must provide a positive number for 'rows'",
            "code" => 400
        ]);
        exit;
    }
}
$qs = $qs . '&rows=' . $rows;

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

$context = stream_context_create([
    'http' => [
        'header' => "Authorization: Basic " . base64_encode("$username:$password")
    ]
]);

// Fetch data from Solr
$string = @file_get_contents($url, false, $context);

// Verifică dacă Solr este în picioare (server nefuncțional)
if ($string == false) {
    http_response_code(503);
    echo json_encode([
        "error" => "SOLR server in DEV is down",
        "code" => 503
    ]);
    exit;
}

$json = json_decode($string, true);

// Verifică dacă Solr a returnat un răspuns valid
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["error" => "Failed to parse Solr response as JSON"]);
    exit;
}

// Elimină secțiunea "facet_counts" din răspuns
unset($json['facet_counts']);

// Returnează doar partea relevantă din răspuns
echo json_encode($json);
