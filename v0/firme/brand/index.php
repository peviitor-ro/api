<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

// Ensure the request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

if (isset($_GET['brands'])) {
    $brands = $_GET['brands'];

    // Definim lista de caractere speciale care sunt interzise
    $invalid_chars = '/[\s\/,<>+=\-:;?"\'\{\}\[\]\|\\\)\(\*&^%$#!~`]/';

    // Verificăm dacă brands-ul conține caractere invalide
    if (!preg_match('/^[a-zA-Z0-9._%+-]{3,}@[a-zA-Z0-9.-]/', $brands) || preg_match($invalid_chars, $brands)) {
        http_response_code(400);  // Returnăm 400 pentru un brands invalid
        echo json_encode(["error" => "Invalid brands format: special characters and spaces are not allowed. Please try something like `local-part@domain`", "received" => $brands]);
        exit;
    }

    $brands = urlencode($brands); // URL encode pentru a fi sigur că brands-ul este tratat corect în URL   

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

    $core = "firme";

    $qs = '?';
    $qs .= 'omitHeader=true&';
    $qs .= '?fl=denumire%2C%20id';
    $qs .= '&indent=true';
    $qs .= '&q.op=OR';
    $qs .= '&q=brands%3A';

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

    // Decodificăm răspunsul JSON de la Solr
    $json = json_decode($string);

    // Verificăm dacă nu există date pentru documente
    if (empty($json->response->docs)) {
        http_response_code(404);
        echo json_encode(["error" => "No user found"]);
        exit;
    }

    $brands = urldecode($_GET['brands']); // Decodează în caz că e URL encoded  

    unset($json->response->docs[0]->_version_);
    unset($json->response->docs[0]->_root_);

    echo json_encode($json->response->docs[0]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Bad request. Parameter is missing.', 'code' => 400]);
}
