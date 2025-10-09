<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    // Parse body for x-www-form-urlencoded data
    parse_str(file_get_contents('php://input'), $deleteData);

    // Accept both query (?company=) and body (form data)
    $company = $_GET['company'] ?? $deleteData['company'] ?? null;
    $company = $company !== null ? trim($company) : null;

    if (empty($company)) {
        http_response_code(400);
        echo json_encode(['error' => 'Company name is required', 'code' => 400]);
        exit;
    }

    // Load environment variables
    require_once __DIR__ . '/../../util/loadEnv.php';
    loadEnv(__DIR__ . '/../../api.env');

    // Retrieve SOLR credentials
    $server = getenv('LOCAL_SERVER') ?: ($_SERVER['LOCAL_SERVER'] ?? null);
    $username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
    $password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

    if (!$server) {
        http_response_code(500);
        echo json_encode(["error" => "LOCAL_SERVER is not set in api.env"]);
        exit;
    }

    $core = 'jobs';

    // Step 1: Get the count of jobs for the given company
    $countUrl = "http://$server/solr/$core/select?q=" . rawurlencode('hiringOrganization.name:"' . $company . '"') . "&wt=json&rows=0";
    $countResponse = fetchSolrData($countUrl);
    if (!$countResponse)
        exit;

    $jobCount = $countResponse['response']['numFound'] ?? 0;

    if ($jobCount === 0) {
        echo json_encode(['message' => 'No jobs found for the specified company', 'jobCount' => 0]);
        exit;
    }

    // Step 2: Delete the jobs
    $deleteUrl = "http://$server/solr/$core/update?commit=true&wt=json";
    $deletePayload = json_encode(['delete' => ['query' => 'hiringOrganization.name:"' . $company . '"']]);
    $deleteResponse = fetchSolrData($deleteUrl, 'POST', $deletePayload);
    if (!$deleteResponse)
        exit;

    echo json_encode(['message' => 'Jobs deleted successfully', 'jobCount' => $jobCount]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method', 'code' => 405]);
}

// Helper: Perform authenticated Solr request
function fetchSolrData($url, $method = 'GET', $postData = null)
{
    global $username, $password;

    $authHeader = "Authorization: Basic " . base64_encode("$username:$password");

    $contextOptions = [
        'http' => [
            'header' => "Content-Type: application/json\r\n" . $authHeader,
            'method' => $method,
            'content' => $postData ?: ""
        ]
    ];

    $context = stream_context_create($contextOptions);
    $response = @file_get_contents($url, false, $context);
    $httpCode = isset($http_response_header[0]) ? explode(' ', $http_response_header[0])[1] : 500;

    if ($httpCode != 200) {
        http_response_code(503);
        echo json_encode([
            "error" => "SOLR request failed",
            "http_code" => $httpCode,
            "message" => $response ?: "Unknown error"
        ]);
        return false;
    }

    return json_decode($response, true);
}
