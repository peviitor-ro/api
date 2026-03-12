<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode([
        "error" => "Only PUT method is allowed",
        "code" => 405
    ]);
    exit;
}

$contentType = $_SERVER["CONTENT_TYPE"] ?? $_SERVER["HTTP_CONTENT_TYPE"] ?? '';
if (stripos($contentType, 'application/json') === false) {
    http_response_code(415);
    echo json_encode([
        "error" => "Content-Type must be application/json",
        "code" => 415
    ]);
    exit;
}

require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

$PROD_SERVER = trim(getenv('PROD_SERVER') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');

function city_fix($in) {
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

    $raw_data = file_get_contents("php://input");
    $data = json_decode($raw_data);

    $job_link = isset($data->job_link) ? htmlspecialchars($data->job_link) : null;
    $job_title = isset($data->job_title) ? htmlspecialchars($data->job_title) : null;
    $company = isset($data->company) ? htmlspecialchars($data->company) : null;
    $country = isset($data->country) ? htmlspecialchars($data->country) : null;
    $city = isset($data->city) ? htmlspecialchars($data->city) : null;
    $county = isset($data->county) ? htmlspecialchars($data->county) : null;
    $remote = isset($data->remote) ? htmlspecialchars($data->remote) : null;

    if (!$job_link || !$job_title || !$company) {
        http_response_code(400);
        echo json_encode([
            "error" => "Missing required fields: job_link, job_title, company",
            "code" => 400
        ]);
        exit;
    }

    $item = new stdClass();
    $item->job_link = trim($job_link);
    $item->id = md5($item->job_link)."";
    $item->job_title = trim($job_title);
    $item->company = trim($company);
    $item->country = $country ? str_ireplace("Romania", "România", trim($country)) : null;
    $item->city = $city ? city_fix(trim($city)) : null;
    $item->county = $county ? trim($county) : null;
    $item->remote = $remote ? trim($remote) : null;

    $core = 'job';
    $url = "http://$PROD_SERVER/solr/$core/update?commitWithin=1000&overwrite=true&wt=json";
    $payload = json_encode([$item]);

    error_log("UPDATE URL: $url");

    $response = postJson($url, $payload, $SOLR_USER, $SOLR_PASS);

    echo json_encode(["success" => "Data successfully inserted into Solr"]);

} catch (Exception $e) {
    error_log("UPDATE FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}
