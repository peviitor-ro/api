<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

$SOLR_SERVER = trim(getenv('SOLR_SERVER') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');
$PROTOCOL = trim(getenv('PROTOCOL') ?: '');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Only GET method allowed"]);
    exit;
}

function fetchJson(string $url, ?string $user = null, ?string $pass = null, int $timeout = 5): array {
    $headers = [];
    if ($user && $pass) {
        $headers[] = "Authorization: Basic " . base64_encode("$user:$pass");
    }
    $context = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => implode("\r\n", $headers),
            'timeout' => $timeout
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

    $location = trim($_GET['location'] ?? '');
    $page = isset($_GET['page']) ? max(0, (int)$_GET['page']) : 0;
    $rows = isset($_GET['rows']) ? min(100, max(1, (int)$_GET['rows'])) : 100;

    if (empty($location)) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required field: location"]);
        exit;
    }

    $core = 'job';
    $base = "$PROTOCOL://$SOLR_SERVER/solr/$core/select";

    $escapedLocation = str_replace(' ', '\\ ', $location); // escape spaces for Solr syntax

    $qs = http_build_query([
        "q" => "location:*" . $escapedLocation . "*",
        "start" => $page * $rows,
        "rows" => $rows,
        "indent" => "true"
    ]);

    $url = "$base?$qs";
    error_log("LOCATION SEARCH URL: $url");

    $solr = fetchJson($url, $SOLR_USER, $SOLR_PASS, 4);

    $numFound = $solr['response']['numFound'] ?? 0;

    $docs = $solr['response']['docs'] ?? [];
    $docs = array_map(function($doc) {
        return array_map(function($value) {
            if ($value === '-' || $value === '' || $value === null) {
                return '';
            }
            return $value;
        }, $doc);
    }, $docs);

    echo json_encode([
        'total' => $numFound,
        'page' => $page,
        'rows' => $rows,
        'jobs' => $docs
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("LOCATION SEARCH FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}