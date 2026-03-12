<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

$PROD_SERVER = trim(getenv('PROD_SERVER') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Only DELETE method allowed"]);
    exit;
}

function postJson(string $url, string $payload, ?string $user = null, ?string $pass = null): array {
    $headers = [];
    if ($user && $pass) {
        $headers[] = "Authorization: Basic " . base64_encode("$user:$pass");
    }
    $headers[] = "Content-Type: application/json";
    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", $headers),
            'content' => $payload,
            'timeout' => 10
        ]
    ]);
    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        $err = error_get_last()['message'] ?? 'Unknown error';
        throw new Exception("FETCH FAILED: $url | $err");
    }
    $json = json_decode($data, true);
    if (!is_array($json)) {
        throw new Exception("Invalid JSON response");
    }
    return $json;
}

try {
    if (!$PROD_SERVER) {
        throw new Exception("PROD_SERVER not set");
    }

    $requestBody = file_get_contents('php://input');
    $data = json_decode($requestBody, true);

    if (!isset($data['urls']) || !is_array($data['urls'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing "urls" key in the JSON payload.']);
        exit;
    }

    if (empty($data['urls'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No URLs provided in the JSON payload.']);
        exit;
    }

    $url_element = "";
    foreach ($data['urls'] as $url) {
        $url_element .= '"' . $url . '" OR ';
    }
    $url_element = substr($url_element, 0, -4);

    $core = 'job';
    $url = "http://$PROD_SERVER/solr/$core/update?commitWithin=100&overwrite=true&wt=json";

    $deleteOperations = [
        'delete' => [
            'query' => 'job_link:(' . $url_element . ')'
        ]
    ];

    $payload = json_encode($deleteOperations);

    error_log("DELETE URL: $url");

    $response = postJson($url, $payload, $SOLR_USER, $SOLR_PASS);

    echo json_encode($response);

} catch (Exception $e) {
    error_log("DELETE FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}
