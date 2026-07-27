<?php

/**
 * ============================================================================
 * DELETE /v1/cleanjobs/ - Company Job Cleanup Endpoint
 * ============================================================================
 *
 * DESCRIPTION:
 *   This endpoint permanently deletes ALL job records for a specific company
 *   from the Solr database. Designed for company website owners who want to
 *   remove their job listings from the peviitor platform.
 *   WARNING: This action cannot be undone. No recovery possible without backups.
 *
 * DEPENDENCIES:
 *   - Apache Solr server (required, configured via PROD_SERVER in api.env)
 *   - Solr Basic Authentication (SOLR_USER and SOLR_PASS from api.env)
 *     All Solr requests use: Authorization: Basic base64(SOLR_USER:SOLR_PASS)
 *   - 'job' core in Solr (job records deleted from this core)
 *   - 'company' core in Solr (used for brand lookup only)
 *
 * AUTHENTICATION:
 *   X-Api-Key header computed as md5() of the provided identifiers:
 *     - company + cif  →  md5(company . cif)  (recommended)
 *     - company only   →  md5(company)
 *     - brand only     →  md5(brand)
 *
 * REQUIRED HTTP METHOD:
 *   DELETE (only this method is allowed)
 *
 * REQUIRED HEADERS:
 *   X-Api-Key: <md5 hash of identifiers>
 *   Content-Type: application/json
 *
 * REQUIRED REQUEST BODY:
 *   {
 *     "company"?: "NUME SRL",       // company name (optional if cif or brand given)
 *     "cif"?: "12345678",           // CIF/CUI (optional if company or brand given)
 *     "brand"?: "ORANGE",           // brand name (optional if company or cif given)
 *     "confirmation": "CLEAN_COMPANY_JOBS"  // must be exactly this string
 *   }
 *   At least one of company, cif, or brand is required.
 *
 * IDENTIFICATION LOGIC:
 *   - cif present: delete jobs where cif matches (precise, unique)
 *   - company present: delete jobs where company name matches
 *   - brand present: lookup company core by brand, extract CIFs, then delete jobs by CIF
 *   - Multiple identifiers can be combined for precision (AND logic)
 *
 * SUCCESS RESPONSE (200 OK):
 *   {
 *     "message": "Jobs deleted successfully",
 *     "jobCount": <number>,
 *     "company"?: "NUME SRL",
 *     "cif"?: "12345678",
 *     "brand"?: "ORANGE"
 *   }
 *
 * ERROR RESPONSES:
 *   400: Bad request (missing fields, invalid confirmation, etc.)
 *   401: Unauthorized (invalid or missing X-Api-Key)
 *   404: No jobs found for the given criteria
 *   405: Method not allowed (only DELETE is allowed)
 *   503: Service unavailable (Solr server down or misconfiguration)
 *
 * EXAMPLE CURL:
 *   COMPANY="NUME SRL"
 *   CIF="12345678"
 *   KEY=$(echo -n "${COMPANY}${CIF}" | md5sum | cut -d' ' -f1)
 *
 *   curl -X DELETE \
 *     -H "X-Api-Key: $KEY" \
 *     -H "Content-Type: application/json" \
 *     -d '{
 *       "company": "'"$COMPANY"'",
 *       "cif": "'"$CIF"'",
 *       "confirmation": "CLEAN_COMPANY_JOBS"
 *     }' \
 *     https://api.peviitor.ro/v1/cleanjobs/
 *
 * ============================================================================
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

$PROD_SERVER = trim(getenv('PROD_SERVER') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');

// ----------------------------------------------------------------------
// AUDIT LOG: capture every hit to this endpoint, before any early exits,
// so failed/unauthorized attempts are visible too, not just successes.
// ----------------------------------------------------------------------
$auditRawBody = file_get_contents('php://input');
file_put_contents(
    __DIR__ . '/cleanjobs.log',
    date('Y-m-d H:i:s') .
    ' | IP=' . ($_SERVER['REMOTE_ADDR'] ?? '-') .
    ' | METHOD=' . ($_SERVER['REQUEST_METHOD'] ?? '-') .
    ' | URI=' . ($_SERVER['REQUEST_URI'] ?? '-') .
    ' | APIKEY=' . ($_SERVER['HTTP_X_API_KEY'] ?? '-') .
    ' | UA=' . ($_SERVER['HTTP_USER_AGENT'] ?? '-') .
    ' | BODY=' . $auditRawBody .
    PHP_EOL,
    FILE_APPEND | LOCK_EX
);

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
    $rawBody = $auditRawBody;
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    error_log(
        "CLEANJOBS REQUEST | IP=" .
        ($_SERVER['REMOTE_ADDR'] ?? '-') .
        " | METHOD=" .
        ($_SERVER['REQUEST_METHOD'] ?? '-') .
        " | URI=" .
        ($_SERVER['REQUEST_URI'] ?? '-') .
        " | BODY=" .
        $rawBody
    );

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

    error_log(
        "CLEANJOBS DELETE | QUERY=$query | JOBS=$jobCount"
    );

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

    // Log the confirmed outcome too, so the audit file shows what actually happened
    file_put_contents(
        __DIR__ . '/cleanjobs.log',
        date('Y-m-d H:i:s') .
        ' | RESULT=SUCCESS' .
        ' | company=' . ($company ?? '-') .
        ' | cif=' . ($cif ?? '-') .
        ' | brand=' . ($brand ?? '-') .
        ' | jobsDeleted=' . $jobCount .
        PHP_EOL,
        FILE_APPEND | LOCK_EX
    );

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
