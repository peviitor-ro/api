<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json; charset=utf-8');

// Ensure the request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

if (isset($_GET['ID'])) {
    $id = $_GET['ID'];

    // Definim lista de caractere speciale care sunt interzise
    $invalid_chars = '/[\s\/,<>+=\-:;?"\'\{\}\[\]\|\\\)\(\*&^%$#!~`]/';

    // Verificăm dacă ID-ul conține caractere invalide
    if (!preg_match('/^[a-zA-Z0-9._%+-]{3,}@[a-zA-Z0-9.-]+\.[a-zA-Z0-9]{2,}$/', $id) || preg_match($invalid_chars, $id)) {
        http_response_code(400);  // Returnăm 400 pentru un ID invalid
        echo json_encode(["error" => "Invalid ID format: special characters and spaces are not allowed", "received" => $id]);
        exit;
    }    

    $id = urlencode($id); // URL encode pentru a fi sigur că ID-ul este tratat corect în URL   

    // Load variables from the api.env file
    function loadEnv($file)
    {
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
    loadEnv('../../api.env');

    // Retrieve SOLR variables from environment
    $server = getenv('LOCAL_SERVER') ?: ($_SERVER['LOCAL_SERVER'] ?? null);
    $username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
    $password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

    // Debugging: Check if the server is set
    if (!$server) {
        die(json_encode(["error" => "LOCAL_SERVER is not set in api.env"]));
    }

    $core = 'auth';

    // Construim query-ul
    $qs = '?';
    $qs .= 'omitHeader=true&';
    $qs .= 'q.op=OR&';
    $qs .= 'q=id%3A';

    $url = 'http://' . $server . '/solr/' . $core . '/select' . $qs . $id;

    // Logăm URL-ul pentru debugging
    error_log("Request URL: " . $url);

    $context = stream_context_create([
        'http' => [
            'header' => "Authorization: Basic " . base64_encode("$username:$password")
        ]
    ]);

    // Încercăm să obținem datele de la Solr
    $string = @file_get_contents($url, false, $context);

    // Verificăm dacă cererea a eșuat
    if ($string === FALSE) {
        error_log("Error fetching data from Solr: " . error_get_last()['message']);
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

    $id = urldecode($_GET['ID']); // Decodează în caz că e URL encoded  

    unset($json->response->docs[0]->_version_);
    unset($json->response->docs[0]->_root_);

    echo json_encode($json->response->docs[0]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Bad request. Parameter is missing.', 'code' => 400]);
}
