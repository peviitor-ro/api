<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        "error" => "Only GET method is allowed",
        "code" => 405
    ]);
    exit;
}

require_once __DIR__ . '/../../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../../api.env');
require_once __DIR__ . '/../../../util/http.php';


$SOLR_SERVER = trim(getenv('SOLR_SERVER') ?: '');
$PROTOCOL = trim(getenv('PROTOCOL') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');



function SolrEscape(string $query): string {
    $chars = ['+', '-', '&&', '||', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '\\', '/'];
    foreach ($chars as $char) {
        $query = str_replace($char, '\\' . $char, $query);
    }
    return $query;
}

try {
    if (!$SOLR_SERVER) {
        throw new Exception("SOLR_SERVER not set");
    }

    $cif = isset($_GET['cif']) ? trim($_GET['cif']) : null;
    $rows = isset($_GET['rows']) ? min((int)$_GET['rows'], 500) : 100;
    $start = isset($_GET['start']) ? max((int)$_GET['start'], 0) : 0;

    if (!$cif) {
        http_response_code(400);
        echo json_encode([
            "error" => "Missing required query parameter: cif",
            "code" => 400
        ]);
        exit;
    }

    $cif = preg_replace('/[^0-9]/', '', $cif);
    if (strlen($cif) !== 8) {
        http_response_code(400);
        echo json_encode([
            "error" => "CIF must be exactly 8 digits",
            "code" => 400
        ]);
        exit;
    }

    $escapedCif = SolrEscape($cif);
    $core = 'job';
    $fl = 'url,title,company,cif,location,tags,workmode,date,status';

    $params = http_build_query([
        'q'     => "cif:$escapedCif",
        'fl'    => $fl,
        'rows'  => $rows,
        'start' => $start,
        'wt'    => 'json'
    ]);

    $url = "$PROTOCOL://$SOLR_SERVER/solr/$core/select?$params";

    $response = fetchJson($url, $SOLR_USER, $SOLR_PASS);

    $docs = $response['response']['docs'] ?? [];
    $total = $response['response']['numFound'] ?? 0;

    echo json_encode([
        "success" => true,
        "total"   => $total,
        "count"   => count($docs),
        "data"    => $docs
    ]);

} catch (Exception $e) {
    error_log("SCRAPER JOBS QUERY FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error'   => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}
