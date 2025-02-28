<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json; charset=utf-8');

// Logare erori pentru debugging
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/your/php-error.log');  // Asigură-te că ai permisiuni de scriere în acest fișier

if (isset($_GET['ID'])) {
    $id = $_GET['ID'];

    // Definim lista de caractere speciale care sunt interzise
    $invalid_chars = '/[\/,.<>+=\-_:;?"\'\{\}\[\]\|\\\)\(\*&^%$#@!~`]/';

    // Verificăm dacă ID-ul conține caractere invalide
    if (preg_match($invalid_chars, $id)) {
        http_response_code(400);  // Returnăm 400 pentru un ID invalid
        echo json_encode(["error" => "Invalid ID format: special characters are not allowed", "received" => $id]);
        exit;
    }

    $id = urlencode($id); // URL encode pentru a fi sigur că ID-ul este tratat corect în URL

    require_once '../config.php';

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

    // Îndepărtăm câmpul _version_ din răspunsul Solr
    unset($json->response->docs[0]->_version_);

    // Logăm răspunsul înainte de a-l trimite
    error_log("Response from Solr: " . json_encode($json));

    // Trimitem răspunsul JSON înapoi
    echo json_encode($json->response->docs[0]);
} else {
    http_response_code(400);
    echo json_encode(["error" => "No ID parameter provided"]);
    exit;
}
?>
