<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../../api.env');

$PROD_SERVER = trim(getenv('PROD_SERVER') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Only POST method allowed"]);
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

    if (!isset($_POST['id']) || !isset($_POST['logo'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing id or logo"]);
        exit;
    }

    $id = $_POST['id'];
    $logo = $_POST['logo'];

    if (strlen($id) > 100) {
        http_response_code(400);
        echo json_encode(["error" => "id exceeds maximum length of 100 characters"]);
        exit;
    }

    if (strlen($logo) > 2048) {
        http_response_code(400);
        echo json_encode(["error" => "logo exceeds maximum length of 2048 characters"]);
        exit;
    }

    if (!preg_match('/^https?:\/\/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(\/.*)?$/', $logo)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid logo URL. It must start with http:// or https:// and be a valid domain.", "received" => $logo]);
        exit;
    }

    $core = 'company';
    $url = "http://$PROD_SERVER/solr/$core/update?commitWithin=1000&overwrite=true&wt=json";

    $payload = json_encode([
        [
            "id" => $id,
            "logo" => ["add" => $logo]
        ]
    ]);

    error_log("FIRME LOGO ADD URL: $url");

    $response = postJson($url, $payload, $SOLR_USER, $SOLR_PASS);

    echo json_encode($response);

} catch (Exception $e) {
    error_log("FIRME LOGO ADD FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Company core unavailable',
        'details' => $e->getMessage()
    ]);
}
