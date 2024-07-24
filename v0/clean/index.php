<?php
header("Access-Control-Allow-Origin: *");

$company = $_POST['company'];

require_once '../config.php';

$core  = 'jobs';

// Step 1: Get the count of jobs for the given company
$countCommand = '/select';

$countQS = '?';
$countQS .= 'q=hiringOrganization.name:"';
$countQS .= rawurlencode($company);
$countQS .= '"&';
$countQS .= 'wt=json';
$countQS .= '&';
$countQS .= 'rows=0';

$countUrl = 'http://' . $server . '/solr/' . $core . $countCommand . $countQS;

try {
    if (empty($company)) {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(['error' => 'Company name is required', 'code' => 400]);
        exit;
    }

    $countJson = @file_get_contents($countUrl);
    if ($countJson === FALSE) {
        list($version, $status, $msg) = explode(' ', $http_response_header[0], 3);
        header("HTTP/1.1 503 Service Unavailable");
        throw new Exception('Failed to query Solr for count, HTTP status: ' . $status, $status);
    }

    $countResponse = json_decode($countJson, true);
    $jobCount = $countResponse['response']['numFound'];

    if ($jobCount === 0) {
        echo json_encode(['message' => 'No jobs found for the specified company', 'jobCount' => 0]);
        exit;
    }

    // Step 2: Delete the jobs
    $deleteCommand = '/update';

    $deleteUrl = 'http://' . $server . '/solr/' . $core . $deleteCommand;

    $deleteData = json_encode(['delete' => ['query' => 'hiringOrganization.name:"' . rawurlencode($company) . '"']]);
    
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => $deleteData
        )
    );

    $context  = stream_context_create($options);
    $deleteJson = @file_get_contents($deleteUrl, false, $context);

    if ($deleteJson === FALSE) {
        list($version, $status, $msg) = explode(' ', $http_response_header[0], 3);
        header("HTTP/1.1 503 Service Unavailable");
        throw new Exception('Failed to delete jobs from Solr, HTTP status: ' . $status, $status);
    }

    echo json_encode(['message' => 'Jobs deleted successfully', 'Jobs deleted' => $jobCount]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'code' => $e->getCode()]);
    exit;
}
?>
