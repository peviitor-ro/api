<?php
/**
 * BFF (Backend For Frontend) for https://api.peviitor.ro/v1/search.
 *
 * 1) This endpoint relies on the data definitions and conventions from
 *    the repository https://github.com/peviitor-ro/peviitor_core.
 *    It acts as a BFF/adapter over the Solr index defined there.
 *
 * 2) The Solr search engine is hosted at:
 *      https://solr.peviitor.ro
 *    Authentication uses the username and password from the environment
 *    variables:
 *      SOLR_USER and SOLR_PASS (loaded via api.env + loadEnv.php).
 */
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

// =========================
// CONFIG
// =========================
$PROD_SERVER = trim(getenv('PROD_SERVER') ?: '');
$LOCAL_SERVER = trim(getenv('LOCAL_SERVER') ?: '');
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
    $parts[] = 'q.op=OR';
    $parts[] = 'defType=edismax';
    $parts[] = 'tie=1.0';

    $parts[] = !empty($params['q'])
        ? 'q=' . rawurlencode($params['q'])
        : 'q=*:*';

    $parts[] = 'bq=salary:[*+TO+*]^10000';
    $parts[] = 'bq=tags:[*+TO+*]^5000';
    $parts[] = 'bq=cif:[*+TO+*]^2000';
    $parts[] = 'bq=company:[*+TO+*]^500';
    $parts[] = 'bq=title:[*+TO+*]^100';
    $parts[] = 'bq=location:[*+TO+*]^50';

    $filters = [
        'company'  => 'company',
        'city'     => 'location',
        'workmode' => 'workmode'
    ];

    foreach ($filters as $param => $field) {
        if (!empty($params[$param])) {
            $items = explode(',', $params[$param]);
            $fq = array_map(
                fn($i) => $field . ':%22' . trim($i) . '%22',
                $items
            );
            $parts[] = 'fq=' . implode('%20OR%20', $fq);
        }
    }

    // sort=vdate+desc  =>  sort=vdate desc
    if (!empty($params['sort'])) {
        // deja vine normalizat, dar îl folosim direct
        $parts[] = 'sort=' . rawurlencode($params['sort']);
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
    $params[$k] = ($k === 'workmode') ? $v : normalize($v);
}

try {
    if (!$LOCAL_SERVER) {
        throw new Exception("LOCAL_SERVER not set");
    }

    $core = 'job';
    $base = "http://$LOCAL_SERVER/solr/$core/select";
    $url  = $base . '?' . buildSolrQuery($params, $start, $rows);

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
            'title' => $doc['title'] ?? null,            
            'company' => $doc['company'] ?? null,
            'city' => $doc['location'] ?? [],
            'location' => $doc['location'] ?? [],
            'county' => $doc['location'] ?? [],
            'remote' => $doc['workmode'] ?? '',
            'workmode' => $doc['workmode'] ?? '',
            'job_link' => $doc['url'] ?? null,
            'url' => $doc['url'] ?? '',
            'id' => md5($doc['url'] ?? ''),
            'salary' => $doc['salary'] ?? null,
            'tags'   => $doc['tags'] ?? [],
            'cif'   => $doc['cif'] ?? '',
            'date'   => $doc['date'] ?? null,
            'vdate'   => $doc['vdate'] ?? null,
            'expirationdate'   => $doc['expirationdate'] ?? null,
            'status'   => $doc['status'] ?? null,
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
