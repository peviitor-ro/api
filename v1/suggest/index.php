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

    if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
        echo json_encode(['message' => 'No query provided']);
        exit;
    }
    $query = trim($_GET['q']);

    $core = 'job';
    $url = "https://$PROD_SERVER/solr/$core/suggest"
         . "?suggest=true"
         . "&suggest.build=true"
         . "&suggest.dictionary=jobTitleSuggester"
         . "&suggest.q=" . urlencode($query)
         . "&wt=json";

    error_log("SUGGEST URL: $url");

    $response = fetchJson($url, $SOLR_USER, $SOLR_PASS, 4);

    if (empty($response['suggest']['jobTitleSuggester'][$query]['suggestions'])) {
        echo json_encode(['message' => 'No suggestions found']);
        exit;
    }

    $suggestions = $response['suggest']['jobTitleSuggester'][$query]['suggestions'];
    echo json_encode(['suggestions' => $suggestions], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("SUGGEST FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}
