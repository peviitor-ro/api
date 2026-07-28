<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode([
        "error" => "Only DELETE method is allowed",
        "code" => 405
    ]);
    exit;
}

require_once __DIR__ . '/../../../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../../../api.env');

$SOLR_SERVER = trim(getenv('SOLR_SERVER') ?: '');
$PROTOCOL = trim(getenv('PROTOCOL') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');

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

function buildSolrUpdateUrl(string $server, string $core, string $protocol = 'http'): string {
    $server = rtrim($server, '/');
    if (preg_match('#^https?://#i', $server)) {
        return $server . "/solr/$core/update?commit=true&wt=json";
    }
    return "$protocol://$server/solr/$core/update?commit=true&wt=json";
}

try {
    if (!$SOLR_SERVER) {
        throw new Exception("SOLR_SERVER not set");
    }

    $requestBody = file_get_contents('php://input');
    $data = json_decode($requestBody, true);

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON body', 'code' => 400]);
        exit;
    }

    $hasCif = isset($data['cif']) && is_string($data['cif']);
    $hasUrl = isset($data['url']) && is_string($data['url']);

    if (!$hasCif && !$hasUrl) {
        http_response_code(400);
        echo json_encode(['error' => 'Exactly one of "cif" or "url" is required', 'code' => 400]);
        exit;
    }

    if ($hasCif && $hasUrl) {
        http_response_code(400);
        echo json_encode(['error' => 'Provide exactly one of "cif" or "url", not both', 'code' => 400]);
        exit;
    }

    $core = 'job';
    $solrUrl = buildSolrUpdateUrl($SOLR_SERVER, $core, $PROTOCOL);

    if ($hasCif) {
        $cif = preg_replace('/[^0-9]/', '', $data['cif']);
        if (strlen($cif) !== 8) {
            http_response_code(400);
            echo json_encode(['error' => 'CIF must be exactly 8 digits', 'code' => 400]);
            exit;
        }

        $escapedCif = str_replace(['+', '-', '&&', '||', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '\\', '/'], ['\\+', '\\-', '\\&&', '\\||', '\\!', '\\(', '\\)', '\\{', '\\}', '\\[', '\\]', '\\^', '\\"', '\\~', '\\*', '\\?', '\\:', '\\\\', '\\/'], $cif);

        $query = "cif:$escapedCif";
    } else {
        $url = htmlspecialchars($data['url'], ENT_QUOTES, 'UTF-8');
        $escapedUrl = str_replace(['+', '-', '&&', '||', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '\\', '/'], ['\\+', '\\-', '\\&&', '\\||', '\\!', '\\(', '\\)', '\\{', '\\}', '\\[', '\\]', '\\^', '\\"', '\\~', '\\*', '\\?', '\\:', '\\\\', '\\/'], $url);

        $query = "url:$escapedUrl";
    }

    $countPayload = json_encode([
        'query' => $query,
        'fields' => ['url'],
        'limit' => 1
    ]);

    $selectUrl = "$PROTOCOL://$SOLR_SERVER/solr/$core/select?" . http_build_query([
        'q'    => $query,
        'rows' => 0,
        'wt'   => 'json'
    ]);

    $countResponse = postJson($selectUrl, null, $SOLR_USER, $SOLR_PASS);
    $totalCount = $countResponse['response']['numFound'] ?? 0;

    if ($totalCount === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'No matching jobs found', 'code' => 404]);
        exit;
    }

    $deletePayload = json_encode([
        'delete' => [
            'query' => $query
        ]
    ]);

    $response = postJson($solrUrl, $deletePayload, $SOLR_USER, $SOLR_PASS);

    echo json_encode([
        'success' => true,
        'message' => "Jobs deleted successfully",
        'count'   => $totalCount
    ]);

} catch (Exception $e) {
    error_log("SCRAPER JOBS DELETE FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error'   => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}
