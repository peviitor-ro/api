<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../../api.env');

$PROD_SERVER = trim(getenv('PROD_SERVER') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(["error" => "Only PUT method allowed"]);
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

function normalizeToArray($value): array {
    if (is_array($value)) return $value;
    if (is_string($value) && trim($value) !== '') return [trim($value)];
    return [];
}

function isValidCif(string $cif): bool {
    return preg_match('/^\d{8}$/', $cif) === 1;
}

function isValidUrl(string $url): bool {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

$ALLOWED_STATUSES = ['activ', 'suspendat', 'inactiv', 'radiat'];

try {
    if (!$PROD_SERVER) {
        throw new Exception("PROD_SERVER not set");
    }

    $raw_data = file_get_contents("php://input");
    $data = json_decode($raw_data);

    if (!$data) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid or empty JSON body"]);
        exit;
    }

    $id = isset($data->id) ? trim((string)$data->id) : '';
    $company = isset($data->company) ? trim((string)$data->company) : '';
    $brand = isset($data->brand) ? trim((string)$data->brand) : null;
    $group = isset($data->group) ? trim((string)$data->group) : null;
    $status = isset($data->status) ? trim((string)$data->status) : null;
    $location = $data->location ?? null;
    $website = $data->website ?? null;
    $career = $data->career ?? null;
    $lastScraped = isset($data->lastScraped) ? trim((string)$data->lastScraped) : null;
    $scraperFile = isset($data->scraperFile) ? trim((string)$data->scraperFile) : null;

    if ($id === '' || $company === '') {
        http_response_code(400);
        echo json_encode([
            "error" => "Missing required fields: id, company",
            "code" => 400
        ]);
        exit;
    }

    if (!isValidCif($id)) {
        http_response_code(400);
        echo json_encode([
            "error" => "Field 'id' must be an 8-digit CIF/CUI string (e.g. '24415960')",
            "code" => 400
        ]);
        exit;
    }

    if ($status !== null && !in_array($status, $ALLOWED_STATUSES, true)) {
        http_response_code(400);
        echo json_encode([
            "error" => "Field 'status' must be one of: " . implode(', ', $ALLOWED_STATUSES),
            "code" => 400
        ]);
        exit;
    }

    $locationArr = normalizeToArray($location);
    $websiteArr = normalizeToArray($website);
    $careerArr = normalizeToArray($career);

    foreach ($websiteArr as $url) {
        if (!isValidUrl($url)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid website URL: $url"]);
            exit;
        }
    }
    foreach ($careerArr as $url) {
        if (!isValidUrl($url)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid career URL: $url"]);
            exit;
        }
    }

    $item = new stdClass();
    $item->id = $id;
    $item->company = $company;

    if ($brand !== null && $brand !== '') $item->brand = $brand;
    if ($group !== null && $group !== '') $item->group = $group;
    if ($status !== null) $item->status = $status;
    if (!empty($locationArr)) $item->location = $locationArr;
    if (!empty($websiteArr)) $item->website = $websiteArr;
    if (!empty($careerArr)) $item->career = $careerArr;
    if ($lastScraped !== null && $lastScraped !== '') $item->lastScraped = $lastScraped;
    if ($scraperFile !== null && $scraperFile !== '') $item->scraperFile = $scraperFile;

    $core = 'company';
    $url = "http://$PROD_SERVER/solr/$core/update?commitWithin=1000&overwrite=true&wt=json";
    $payload = json_encode([$item]);

    error_log("COMPANY ADD: CIF=$id company=$company");

    $response = postJson($url, $payload, $SOLR_USER, $SOLR_PASS);

    echo json_encode([
        "success" => true,
        "id" => $id,
        "message" => "Company '$company' ($id) upserted to company core"
    ]);

} catch (Exception $e) {
    error_log("COMPANY ADD FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Company core unavailable',
        'details' => $e->getMessage()
    ]);
}
