<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../utils/env.php';
loadEnv('../../api.env');

// =========================
// CONFIG
// =========================
$PROD_SERVER = trim(getenv('PROD_SERVER') ?: '');
$BACK_SERVER = trim(getenv('BACK_SERVER') ?: '');

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
        error_log("FETCH FAILED: $url | $err");
        throw new Exception($err);
    }

    $json = json_decode($data, true);
    if (!is_array($json)) {
        throw new Exception("Invalid JSON response");
    }

    return $json;
}

function buildSolrQuery(array $params, int $start, int $rows): string {
    $query = [
        'indent=true',
        'q.op=AND',
        'defType=edismax',
        'qf=' . rawurlencode('job_title company city county'),
        'mm=100%'
    ];

    // Query
    $query[] = !empty($params['q'])
        ? 'q=' . rawurlencode($params['q'])
        : 'q=*:*';

    // Filters
    $filters = [
        'company' => 'company',
        'city'    => 'city',
        'remote'  => 'remote'
    ];

    foreach ($filters as $param => $field) {
        if (!empty($params[$param])) {
            $items = explode(',', $params[$param]);
            $fq = array_map(
                fn($i) => $field . ':"' . rawurlencode(trim($i)) . '"',
                $items
            );
            $query[] = 'fq=' . implode('%20OR%20', $fq);
        }
    }

    $query[] = "start=$start";
    $query[] = "rows=$rows";

    return implode('&', $query);
}

// =========================
// PAGINATION
// =========================
$page  = max(1, (int)($_GET['page'] ?? 1));
$rows  = max(1, (int)($_GET['rows'] ?? 12));
$start = ($page - 1) * $rows;

// Normalize GET
$params = [];
foreach ($_GET as $k => $v) {
    $params[$k] = normalize($v);
}

// =========================
// PRIMARY → SOLR
// =========================
try {
    if (!$PROD_SERVER) {
        throw new Exception("PROD_SERVER not set");
    }

    $core = 'jobs';
    $base = "http://$PROD_SERVER/solr/$core/select";
    $url  = $base . '?' . buildSolrQuery($params, $start, $rows);

    error_log("SOLR URL: $url");

    $solr = fetchJson($url, $SOLR_USER, $SOLR_PASS, 4);

    $numFound = $solr['response']['numFound'] ?? 0;
    if ($numFound === 0) {
        http_response_code(404);
        echo json_encode(["error" => "No jobs found"]);
        exit;
    }

    echo json_encode($solr, JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    error_log("PRIMARY FAILED: " . $e->getMessage());
}

// =========================
// FALLBACK
// =========================
try {
    if (!$BACK_SERVER) {
        throw new Exception("BACK_SERVER not set");
    }

    $fallback = rtrim($BACK_SERVER, '/') . '/mobile/?';
    $fallback .= 'search=' . urlencode($_GET['q'] ?? '');
    $fallback .= '&page=' . $page;

    if (!empty($_GET['city'])) {
        $fallback .= '&cities=' . urlencode($_GET['city']);
    }
    if (!empty($_GET['company'])) {
        $fallback .= '&companies=' . urlencode($_GET['company']);
    }
    if (!empty($_GET['remote'])) {
        $fallback .= '&remote=' . urlencode($_GET['remote']);
    }

    error_log("FALLBACK URL: $fallback");

    $data = fetchJson($fallback, null, null, 3);

    $docs = array_map(fn($j) => [
        'job_title' => $j['job_title'] ?? null,
        'company'   => $j['company_name'] ?? null,
        'city'      => [$j['city'] ?? null],
        'county'    => [$j['county'] ?? null],
        'remote'    => $j['remote'] ?? null,
        'job_link'  => $j['job_link'] ?? null,
        'id'        => $j['id'] ?? null,
    ], $data['results'] ?? []);

    echo json_encode([
        'response' => [
            'docs' => $docs,
            'numFound' => $data['count'] ?? count($docs)
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(503);
    echo json_encode([
        'error' => 'Primary and fallback unavailable',
        'details' => $e->getMessage()
    ]);
}