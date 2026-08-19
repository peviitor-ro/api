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

$SOLR_SERVER = trim(getenv('SOLR_SERVER') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');
$PROTOCOL = trim(getenv('PROTOCOL') ?: '');

const ALLOWED_WORKMODES = ['remote', 'on-site', 'hybrid'];
const ALLOWED_STATUSES = ['scraped', 'tested', 'published', 'verified'];

function location_fix(string $in): string {
    $map = [
        "Bucharest" => "București",
        "Brasov" => "Brașov",
        "Timisoara" => "Timișoara",
        "Pitesti" => "Pitești",
        "Iasi" => "Iași",
        "Targu Mures" => "Târgu Mureș",
        "Cluj Napoca" => "Cluj-Napoca",
    ];
    return str_replace(array_keys($map), array_values($map), $in);
}

// reject strings that look like they're trying to inject HTML/markup
function reject_if_html(?string $val, string $field): void {
    if ($val !== null && strip_tags($val) !== $val) {
        http_response_code(400);
        echo json_encode(["error" => "Field '$field' must not contain HTML/markup", "code" => 400]);
        exit;
    }
}

function clean_string(mixed $val): ?string {
    if (!is_string($val)) return null;
    $val = trim($val);
    return $val === '' ? null : $val;
}

// normalizes a value that should be a string[] — accepts array or single string
function clean_string_array(mixed $val): array {
    if ($val === null) return [];
    if (is_string($val)) $val = [$val];
    if (!is_array($val)) return [];
    $out = [];
    foreach ($val as $v) {
        if (!is_string($v)) continue;
        $v = trim($v);
        if ($v !== '') $out[] = $v;
    }
    return $out;
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
    if (!$SOLR_SERVER) {
        throw new Exception("SOLR_SERVER not set");
    }

    $raw_data = file_get_contents("php://input");
    $data = json_decode($raw_data);

    if (json_last_error() !== JSON_ERROR_NONE || !is_object($data)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid JSON body", "code" => 400]);
        exit;
    }

    // ---- url ----
    $url = clean_string($data->url ?? null);
    if (!$url || !filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
        http_response_code(400);
        echo json_encode(["error" => "Missing or invalid required field: url", "code" => 400]);
        exit;
    }

    // ---- title (optional on partial update; validated only if present) ----
    $title = clean_string($data->title ?? null);
    if ($title !== null) {
        if (mb_strlen($title) > 200) {
            http_response_code(400);
            echo json_encode(["error" => "title exceeds 200 characters", "code" => 400]);
            exit;
        }
        reject_if_html($title, 'title');
    }

    // ---- company (uppercase per schema) ----
    $company = clean_string($data->company ?? null);
    reject_if_html($company, 'company');
    if ($company !== null) $company = mb_strtoupper($company);

    // ---- cif ----
    $cif = clean_string($data->cif ?? null);
    reject_if_html($cif, 'cif');

    // ---- location (string[], diacritics accepted) — only touched if key present ----
    $location = null;
    if (property_exists($data, 'location')) {
        $location = array_map('location_fix', clean_string_array($data->location));
    }

    // ---- tags (string[], lowercase, no diacritics, max 20) — only touched if key present ----
    $tags = null;
    if (property_exists($data, 'tags')) {
        $tags = clean_string_array($data->tags);
        $tags = array_map(fn($t) => mb_strtolower($t), $tags);
        $tags = array_slice($tags, 0, 20);
    }

    // ---- workmode (enum) ----
    $workmode = clean_string($data->workmode ?? null);
    if ($workmode !== null && !in_array($workmode, ALLOWED_WORKMODES, true)) {
        http_response_code(400);
        echo json_encode([
            "error" => "workmode must be one of: " . implode(', ', ALLOWED_WORKMODES),
            "code" => 400
        ]);
        exit;
    }

    // ---- status (enum) — optional on partial update, no forced default ----
    $status = clean_string($data->status ?? null);
    if ($status !== null && !in_array($status, ALLOWED_STATUSES, true)) {
        http_response_code(400);
        echo json_encode([
            "error" => "status must be one of: " . implode(', ', ALLOWED_STATUSES),
            "code" => 400
        ]);
        exit;
    }

    // ---- date fields (pass through as-is; validate basic ISO8601 shape) ----
    $date = clean_string($data->date ?? null);
    $vdate = clean_string($data->vdate ?? null);
    $expirationdate = clean_string($data->expirationdate ?? null);

    // ---- salary (string, not array) ----
    $salary = clean_string($data->salary ?? null);
    reject_if_html($salary, 'salary');

    // atomic update: url identifies the doc, every other field is wrapped in
    // {"set": ...} so only fields present in this request get touched — anything
    // omitted keeps its existing value in Solr instead of being wiped.
    $item = new stdClass();
    $item->url = $url;
    if ($title !== null) $item->title = ["set" => $title];
    if ($company !== null) $item->company = ["set" => $company];
    if ($cif !== null) $item->cif = ["set" => $cif];
    if ($location !== null) $item->location = ["set" => $location];
    if ($tags !== null) $item->tags = ["set" => $tags];
    if ($workmode !== null) $item->workmode = ["set" => $workmode];
    if ($date !== null) $item->date = ["set" => $date];
    if ($status !== null) $item->status = ["set" => $status];
    if ($vdate !== null) $item->vdate = ["set" => $vdate];
    if ($expirationdate !== null) $item->expirationdate = ["set" => $expirationdate];
    if ($salary !== null) $item->salary = ["set" => $salary];

    $core = 'job';
    $updateUrl = "$PROTOCOL://$SOLR_SERVER/solr/$core/update?commitWithin=1000&wt=json";
    $payload = json_encode([$item], JSON_UNESCAPED_UNICODE);

    error_log("UPDATE URL: $updateUrl");
    error_log("UPDATE PAYLOAD: $payload");

    $response = postJson($updateUrl, $payload, $SOLR_USER, $SOLR_PASS);

    echo json_encode(["success" => "Data successfully inserted into Solr"]);

} catch (Exception $e) {
    error_log("UPDATE FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}