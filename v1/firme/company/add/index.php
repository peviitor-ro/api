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

function judet_fix($in) {
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

try {
    if (!$PROD_SERVER) {
        throw new Exception("PROD_SERVER not set");
    }

    $raw_data = file_get_contents("php://input");
    $data = json_decode($raw_data);
    
    $id = isset($data->id) ? $data->id : null;
    $company = isset($data->company) ? htmlspecialchars($data->company) : null;
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
    $logo = isset($data->logo) ? htmlspecialchars($data->logo) : null;

    if (!$id || !$company || !$cod_inmatriculare || !$euid || !$adresa_completa || !$localitate || !$judet || !$cod_stare) {
        http_response_code(400);
        echo json_encode([
            "error" => "Missing required fields: id, company, cod_inmatriculare, euid, adresa_completa, localitate, judet or cod_stare.",
            "code" => 400
        ]);
        exit;
    }

    if (!is_int($id) || !is_int($cod_stare)) {
        http_response_code(400);
        echo json_encode([
            "error" => "Fields 'id' and 'cod_stare' must be integers.",
            "code" => 400
        ]);
        exit;
    }

    if ($email && (!preg_match('/^[a-zA-Z0-9._%+-]{3,}@[a-zA-Z0-9.-]/', $email) || preg_match('/[\s\/,<>+=\-:;?"\'\{\}\[\]\|\\\)\(\*&^%$#!~`]/', $email))) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid email format", "received" => $email]);
        exit;
    }

    if ($phone) {
        $phone_cleaned = preg_replace('/[\s\-\(\)]+/', '', $phone);
        if (!preg_match('/^\+?[0-9]{7,15}$/', $phone_cleaned)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid phone format", "received" => $phone]);
            exit;
        }
    }

    if ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid website URL format.", "received" => $website]);
        exit;
    }

    $item = new stdClass();
    $item->id = trim($id);
    $item->company = trim($company);
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
    $item->logo = trim($logo ?? '');
    $item->cod_stare = trim($cod_stare);

    $core = 'company';
    $url = "https://$PROD_SERVER/solr/$core/update?commitWithin=1000&overwrite=true&wt=json";
    $payload = json_encode([$item]);

    error_log("FIRME COMPANY ADD URL: $url");

    $response = postJson($url, $payload, $SOLR_USER, $SOLR_PASS);

    echo json_encode(["success" => "Data successfully inserted into Solr"]);

} catch (Exception $e) {
    error_log("FIRME COMPANY ADD FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Company core unavailable',
        'details' => $e->getMessage()
    ]);
}
