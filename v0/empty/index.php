<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only DELETE method is allowed"]);
    exit;
}

// Load variables from the api.env file
require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

// Retrieve SOLR variables from environment
$server = getenv('LOCAL_SERVER') ?: ($_SERVER['LOCAL_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

// Debugging: Check if the server is set
if (!$server) {
    die(json_encode(["error" => "LOCAL_SERVER is not set in api.env"]));
}

$method = 'POST';
$core  = 'jobs';
$command = '/update';

$qs = '?_=' . time() . '&commitWithin=1000&overwrite=true&wt=json';

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;

$data = json_encode(['delete' => ['query' => '*:*']]); // New Format

// Prepare the basic authentication header
$auth = base64_encode("$username:$password");

// cURL initialization
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . $auth
]);

try {
    // Count the jobs before deletion
    $countUrl = 'http://' . $server . '/solr/' . $core . '/select?q=*:*&wt=json&rows=0';

    // Execute the cURL request for counting jobs
    curl_setopt($ch, CURLOPT_URL, $countUrl);
    $countJson = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception("cURL Error: " . curl_error($ch), 503);
    }

    if ($countJson === false) {
        throw new Exception("Failed to connect to Solr", 503);
    }

    $countResponse = json_decode($countJson, true);
    $jobCount = $countResponse['response']['numFound'] ?? 0;

    // Count companies
    $qsCompanies = '?facet.field=company_str&facet.limit=2000000&facet=true&fl=company&q=*%3A*&rows=0';
    $companyUrl = 'http://' . $server . '/solr/' . $core . '/select' . $qsCompanies;

    curl_setopt($ch, CURLOPT_URL, $companyUrl);
    $companyJson = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception("cURL Error: " . curl_error($ch), 503);
    }

    if ($companyJson === false) {
        throw new Exception("Failed to connect to Solr", 503);
    }

    $companyResponse = json_decode($companyJson, true);
    $companies = $companyResponse['facet_counts']['facet_fields']['company_str'] ?? [];

    $companyCount = 0;
    for ($i = 1; $i < count($companies); $i += 2) {
        if ($companies[$i] > 0) {
            $companyCount++;
        }
    }

    // Delete jobs
    curl_setopt($ch, CURLOPT_URL, $url);
    $json = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception("cURL Error: " . curl_error($ch), 503);
    }

    if ($json === false) {
        throw new Exception("Failed to send delete request to Solr", 503);
    }

    echo json_encode([
        'message' => 'Jobs deleted successfully',
        'jobsDeleted' => $jobCount,
        'companiesDeleted' => $companyCount
    ]);
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['error' => $e->getMessage(), 'code' => $e->getCode()]);
} finally {
    curl_close($ch);
}
