<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../../api.env');

$SOLR_SERVER = trim(getenv('SOLR_SERVER') ?: '');
$PROTOCOL = trim(getenv('PROTOCOL') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Only GET method allowed"]);
    exit;
}

function getJson(string $url, ?string $user = null, ?string $pass = null): array {
    $headers = [];
    if ($user && $pass) {
        $headers[] = "Authorization: Basic " . base64_encode("$user:$pass");
    }
    $headers[] = "Content-Type: application/json";
    $context = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => implode("\r\n", $headers),
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

    $cif = isset($_GET['cif']) ? trim($_GET['cif']) : null;
    $name = isset($_GET['name']) ? trim($_GET['name']) : null;
    $rows = isset($_GET['rows']) ? min((int)$_GET['rows'], 50) : 10;
    $start = isset($_GET['start']) ? max((int)$_GET['start'], 0) : 0;

    if (!$cif && !$name) {
        http_response_code(400);
        echo json_encode(["error" => "Missing query parameter: cif or name"]);
        exit;
    }

    $core = 'company';
    $fl = 'id,company,brand,group,status,location,website,career,lastScraped,scraperFile';

    if ($cif) {
        $cif = preg_replace('/[^0-9]/', '', $cif);
        $q = "id:" . urlencode($cif);
        $defType = 'edismax';
    } else {
        $escaped = SolrEscape($name);
        $q = "$escaped";
        $defType = 'edismax';
    }

    $params = http_build_query([
        'q'       => $q,
        'defType' => $defType,
        'fl'      => $fl,
        'rows'    => $rows,
        'start'   => $start,
        'wt'      => 'json'
    ]);

    $url = "$PROTOCOL://$SOLR_SERVER/solr/$core/select?$params";

    $response = getJson($url, $SOLR_USER, $SOLR_PASS);

    $docs = $response['response']['docs'] ?? [];
    $total = $response['response']['numFound'] ?? 0;

    echo json_encode([
        "success" => true,
        "total"   => $total,
        "count"   => count($docs),
        "data"    => $docs
    ]);

} catch (Exception $e) {
    error_log("COMPANY SEARCH FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error'   => 'Company search unavailable',
        'details' => $e->getMessage()
    ]);
}

function SolrEscape(string $query): string {
    $chars = ['+', '-', '&&', '||', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '\\', '/'];
    foreach ($chars as $char) {
        $query = str_replace($char, '\\' . $char, $query);
    }
    return $query;
}
