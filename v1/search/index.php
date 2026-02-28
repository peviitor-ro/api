<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

// =========================
// CONFIG
// =========================
$PROD_SERVER = trim(getenv('PROD_SERVER') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Only GET method allowed"]);
    exit;
}

// =========================
// HELPERS
// =========================
function normalize(string $v): string {
    $map = [
        'ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ş'=>'s','ț'=>'t','ţ'=>'t',
        'Ă'=>'a','Â'=>'a','Î'=>'i','Ș'=>'s','Ş'=>'s','Ț'=>'t','Ţ'=>'t'
    ];
    return strtr(mb_strtolower(trim($v), 'UTF-8'), $map);
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

function buildSolrQuery(array $params, int $start, int $rows): string {
    $parts = [];
    $parts[] = 'indent=true';
    $parts[] = 'q.op=AND';
    $parts[] = 'defType=edismax';
    $parts[] = 'qf=' . rawurlencode('title company location');
    $parts[] = 'mm=100%';

    $parts[] = !empty($params['q'])
        ? 'q=' . rawurlencode($params['q'])
        : 'q=*:*';

    $filters = [
        'company' => 'company',
        'city'    => 'location',
        'remote'  => 'workmode'
    ];

    foreach ($filters as $param => $field) {
        if (!empty($params[$param])) {
            $items = explode(',', $params[$param]);
            $fq = array_map(
                fn($i) => $field . ':"' . rawurlencode(trim($i)) . '"',
                $items
            );
            $parts[] = 'fq=' . implode('%20OR%20', $fq);
        }
    }

    $parts[] = "start=$start";
    $parts[] = "rows=$rows";

    return implode('&', $parts);
}

$page  = max(1, (int)($_GET['page'] ?? 1));
$rows  = max(1, (int)($_GET['rows'] ?? 12));
$start = ($page - 1) * $rows;

$params = [];
foreach ($_GET as $k => $v) {
    $params[$k] = normalize($v);
}

try {
    if (!$PROD_SERVER) {
        throw new Exception("PROD_SERVER not set");
    }

    $core = 'job';
    $base = "http://$PROD_SERVER/solr/$core/select";
    $url  = $base . '?' . buildSolrQuery($params, $start, $rows);

    error_log("JOB CORE URL: $url");

    $solr = fetchJson($url, $SOLR_USER, $SOLR_PASS, 4);

    $numFound = $solr['response']['numFound'] ?? 0;
    if ($numFound === 0) {
        http_response_code(404);
        echo json_encode(["error" => "No jobs found"]);
        exit;
    }

    $docs = array_map(function($doc) {
        return [
            'job_title' => $doc['title'] ?? null,
            'company' => $doc['company'] ?? null,
            'city' => $doc['location'] ?? [],
            'county' => $doc['location'] ?? [],
            'remote' => $doc['workmode'] ?? '',
            'job_link' => $doc['url'] ?? null,
            'id' => md5($doc['url'] ?? '')
        ];
    }, $solr['response']['docs'] ?? []);

    echo json_encode([
        'response' => [
            'docs' => $docs,
            'numFound' => $numFound
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("JOB CORE FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}
