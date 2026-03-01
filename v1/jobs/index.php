<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

$PROD_SERVER = trim(getenv('PROD_SERVER') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');

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
    if (!$PROD_SERVER) {
        throw new Exception("PROD_SERVER not set");
    }

    $page = 1;
    $rows = 100;

    if (isset($_GET["start_page"]) && isset($_GET["stop_page"])) {
        $stop = (int)$_GET["stop_page"];
        $start = (int)$_GET["start_page"];
        
        if (!is_numeric($stop) || $stop < $start) {
            http_response_code(400);
            echo json_encode([
                "error" => "You must provide a positive number for 'stop_page' less than " . ($start/100)+1,
                "code" => 400
            ]);
            exit;
        }
        if (!is_numeric($start) || $start < 0) {
            http_response_code(400);
            echo json_encode([
                "error" => "You must provide a positive number for 'start_page'",
                "code" => 400
            ]);
            exit;
        }
        
        $startIndex = ($start - 1) * 100;
        $rows = ($stop - 1) * 100 - $startIndex;
    }

    $core = 'job';
    $url = "http://$PROD_SERVER/solr/$core/select?" . http_build_query([
        "facet" => "true",
        "indent" => "true",
        "q.op" => "OR",
        "q" => "*:*",
        "omitHeader" => "true",
        "rows" => $rows,
        "start" => $startIndex ?? 0
    ]);

    error_log("JOBS URL: $url");

    $response = fetchJson($url, $SOLR_USER, $SOLR_PASS, 5);

    unset($response['facet_counts']);

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("JOBS FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}
