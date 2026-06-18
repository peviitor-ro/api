<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

$PROD_SERVER = trim(getenv('PROD_SERVER') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(["error" => "Only DELETE method allowed"]);
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

function solrEscape(string $value): string {
    $special = ['+', '-', '&', '|', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '\\', '/'];
    $result = '';
    for ($i = 0; $i < strlen($value); $i++) {
        $char = $value[$i];
        if (in_array($char, $special, true)) {
            $result .= '\\' . $char;
        } else {
            $result .= $char;
        }
    }
    return $result;
}

try {
    if (!$PROD_SERVER) {
        throw new Exception("PROD_SERVER not set");
    }

    // Parse body - support JSON and form-encoded
    $rawBody = file_get_contents('php://input');
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (strpos($contentType, 'application/json') !== false) {
        $body = json_decode($rawBody, true);
        if (!is_array($body)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON body']);
            exit;
        }
    } else {
        parse_str($rawBody, $body);
    }

    // Extract fields from body
    $company     = isset($body['company']) ? trim($body['company']) : null;
    $cif         = isset($body['cif']) ? trim($body['cif']) : null;
    $brand       = isset($body['brand']) ? trim($body['brand']) : null;
    $confirmation = isset($body['confirmation']) ? trim($body['confirmation']) : null;

    // Validate: at least one identifier required
    if (!$company && !$cif && !$brand) {
        http_response_code(400);
        echo json_encode(['error' => 'At least one of company, cif, or brand is required']);
        exit;
    }

    // Validate confirmation
    if ($confirmation !== 'CLEAN_COMPANY_JOBS') {
        http_response_code(400);
        echo json_encode(['error' => 'Confirmation must be "CLEAN_COMPANY_JOBS"']);
        exit;
    }

    // Validate input length
    if ($company && strlen($company) > 200) {
        http_response_code(400);
        echo json_encode(['error' => 'Company name too long (max 200 characters)']);
        exit;
    }
    if ($cif && strlen($cif) > 20) {
        http_response_code(400);
        echo json_encode(['error' => 'CIF too long']);
        exit;
    }
    if ($brand && strlen($brand) > 200) {
        http_response_code(400);
        echo json_encode(['error' => 'Brand name too long (max 200 characters)']);
        exit;
    }

    // Auth: validate X-Api-Key header
    $apiKey = trim($_SERVER['HTTP_X_API_KEY'] ?? '');
    if (empty($apiKey)) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized - missing X-Api-Key header']);
        exit;
    }

    // Determine expected key based on provided fields
    if ($company && $cif) {
        $expectedKey = md5($company . $cif);
        if (!hash_equals($expectedKey, $apiKey)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized - invalid X-Api-Key']);
            exit;
        }
    } elseif ($company) {
        $expectedKey = md5($company);
        if (!hash_equals($expectedKey, $apiKey)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized - invalid X-Api-Key']);
            exit;
        }
        error_log("CLEANJOBS WARNING: auth by company name only (no CIF) for '$company'");
    } elseif ($brand) {
        $expectedKey = md5($brand);
        if (!hash_equals($expectedKey, $apiKey)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized - invalid X-Api-Key']);
            exit;
        }
        error_log("CLEANJOBS WARNING: auth by brand only for '$brand'");
    }

    // Build Solr query
    $core = 'job';
    $base = "http://$PROD_SERVER/solr/$core";

    $queryParts = [];

    if ($cif) {
        $queryParts[] = 'cif:' . solrEscape($cif);
    }

    if ($company) {
        $queryParts[] = 'company:"' . solrEscape($company) . '"';
    }

    if ($brand) {
        $companyCore = "http://$PROD_SERVER/solr/company";
        $brandUrl = $companyCore . "/select?q=" . rawurlencode('brand:"' . solrEscape($brand) . '"') . "&wt=json&rows=100&fl=id,company";

        $brandResponse = fetchJson($brandUrl, $SOLR_USER, $SOLR_PASS, 4);
        $brandDocs = $brandResponse['response']['docs'] ?? [];

        if (empty($brandDocs)) {
            http_response_code(404);
            echo json_encode(['error' => "No companies found for brand '$brand'"]);
            exit;
        }

        $cifList = [];
        foreach ($brandDocs as $doc) {
            $cifList[] = 'cif:' . solrEscape($doc['id']);
        }

        if (!empty($queryParts)) {
            $queryParts[] = '(' . implode(' OR ', $cifList) . ')';
        } else {
            $queryParts = [implode(' OR ', $cifList)];
        }
    }

    $query = implode(' AND ', $queryParts);

    // Count matching jobs
    $countUrl = $base . "/select?q=" . rawurlencode($query) . "&wt=json&rows=0";
    $countResponse = fetchJson($countUrl, $SOLR_USER, $SOLR_PASS, 4);
    $jobCount = $countResponse['response']['numFound'] ?? 0;

    if ($jobCount === 0) {
        http_response_code(404);
        echo json_encode([
            'error' => 'No jobs found',
            'message' => 'No jobs found matching the given criteria'
        ]);
        exit;
    }

    // Execute delete
    $deleteUrl = $base . "/update?commit=true&wt=json";
    $deletePayload = json_encode(['delete' => ['query' => $query]]);

    postJson($deleteUrl, $deletePayload, $SOLR_USER, $SOLR_PASS);

    // Build response
    $response = [
        'message' => 'Jobs deleted successfully',
        'jobCount' => $jobCount
    ];
    if ($company) $response['company'] = $company;
    if ($cif) $response['cif'] = $cif;
    if ($brand) $response['brand'] = $brand;

    error_log("CLEANJOBS SUCCESS: company=$company cif=$cif brand=$brand jobsDeleted=$jobCount");

    http_response_code(200);
    echo json_encode($response);

} catch (Exception $e) {
    error_log("CLEANJOBS FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}
