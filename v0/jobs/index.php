<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

// Ensure the request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

require_once '../config.php';
    
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
        echo json_encode(["error" => "You must provide a positive number for 'rows'"]);
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
?>
