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

function fetchJson(string $url, ?string $user = null, ?string $pass = null, int $timeout = 5): array
{
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

function postJson(string $url, string $payload, ?string $user = null, ?string $pass = null): array
{
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

    $index = 10;
    if (isset($_GET["index"]))
        $index = (int)$_GET["index"];

    $core = 'job';
    $base = "$PROTOCOL://$SOLR_SERVER/solr/$core/select?";

    $qs = http_build_query([
        "q" => "*:*",
        "start" => 0,
        "rows" => 0,
        "indent" => "true"
    ]);

    $url = "$base?$qs";
    $solr = fetchJson($url, $SOLR_USER, $SOLR_PASS, 4);

    $url = "$PROTOCOL://$SOLR_SERVER/solr/$core/select?" . http_build_query([
        "facet" => "true",
        "indent" => "true",
        "q.op" => "OR",
        "q" => "*:*",
        "omitHeader" => "true",
        "rows" => $index,
        "start" => 0,
        "fq" => "-vdate:[NOW/DAY TO NOW/DAY+1DAY]"
    ]);

    error_log("JOBS URL: $url");

    $response = fetchJson($url, $SOLR_USER, $SOLR_PASS, 5);

    $numFound = $response['response']['numFound'] ?? 0;

    unset($response['facet_counts']);

    $docs = $response['response']['docs'] ?? [];
    $jobs = [];
    $updates = [];
    $today = gmdate("Y-m-d\TH:i:s.v\Z");

    foreach ($docs as $doc) {
        if (isset($doc['url']) && isset($doc['title'])) {
            $updates[] = [
                "url"   => $doc['url'],
                "vdate" => ["set" => $today]
            ];

            $jobs[] = [
                "url" => $doc['url'],
                "title" => $doc['title'],
                "company" => $doc['company'] ?? '',
                "cif" => $doc['cif'] ?? '',
                "location" => $doc['location'] ?? [],
                "tags" => $doc['tags'] ?? [],
                "workmode" => $doc['workmode'] ?? '',
                "date" => $doc['date'] ?? null,
                "status" => $doc['status'] ?? '',
                "expirationdate" => $doc['expirationdate'] ?? null,
                "salary" => $doc['salary'] ?? '',
                "vdate" => $today,
            ];
        }
        else
            continue;
    }

    if (!empty($updates)) {
        $updateUrl = "$PROTOCOL://$SOLR_SERVER/solr/$core/update?commit=true";
        $updateResult = postJson($updateUrl, json_encode($updates), $SOLR_USER, $SOLR_PASS);
        error_log("SOLR UPDATE RESPONSE: " . json_encode($updateResult));
    }

    $result = [];

    foreach ($jobs as $job) {
        $result[] = [
            "url" => $job['url']
        ];
    }

    echo json_encode([
        "numFound" => $numFound,
        "count"    => count($jobs),
        "jobs"     => $result
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("JOBS FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}
