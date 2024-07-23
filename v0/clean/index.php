<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$company = $_POST['company'];

require_once '../config.php';

$core = 'jobs';
$command = '/update';

$qs = '?';
$qs .= '_=1617366504771';  // To prevent caching
$qs .= '&';
$qs .= 'commitWithin=1000'; // Commit within 1 second
$qs .= '&';
$qs .= 'overwrite=true';
$qs .= '&';
$qs .= 'wt=json';

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;

// Function to get the number of jobs for a company
function getJobCount($server, $core, $company) {
    $qs = '?';
    $qs .= 'q=hiringOrganization.name:"';

    $q = '"&';
    $q .= 'wt=json';

    $queryUrl = 'http://' . $server . '/solr/' . $core . '/select' . $qs . rawurlencode($company) . $q;

    $response = @file_get_contents($queryUrl);

    if ($response === FALSE) {
        throw new Exception('Failed to query Solr for job count');
    }

    $data = json_decode($response, true);

    return $data['response']['numFound'];
}

try {
    // Check if the company parameter is empty
    if (empty($company)) {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(['error' => 'Company name is required', 'code' => 400]);
        exit;
    }

    // Get the initial job count
    $initialCount = getJobCount($server, $core, $company);

    // Construct the delete query
    $data = json_encode(['delete' => ['query' => 'hiringOrganization.name:"' . addslashes($company) . '"']]);

    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\n",
            'method' => 'POST',
            'content' => $data
        ]
    ];

    $context = stream_context_create($options);
    $json = @file_get_contents($url, false, $context);

    if ($json === FALSE) {
        list($version, $status, $msg) = explode(' ', $http_response_header[0], 3);
        // Force HTTP status code to be 503
        header("HTTP/1.1 503 Service Unavailable");
        throw new Exception('Your call to Solr failed and returned HTTP status: ' . $status, $status);
    }

    // Commit the changes explicitly to ensure that deletions are applied
    $qsu = '?';
    $qsu .= 'commit=true';

    $commitUrl = 'http://' . $server . '/solr/' . $core . $command . $qsu;

    $commitJson = @file_get_contents($commitUrl);

    if ($commitJson === FALSE) {
        throw new Exception('Failed to commit changes');
    }

    // Get the final job count
    $finalCount = getJobCount($server, $core, $company);

    // Calculate the number of deleted jobs
    $numDeleted = $initialCount - $finalCount;

    echo json_encode(['message' => 'Jobs deleted successfully', 'numDeleted' => $numDeleted]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'code' => $e->getCode()]);
    exit;
}
?>