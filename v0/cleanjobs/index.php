<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    // Citim inputul ca JSON
    $deleteData = json_decode(file_get_contents('php://input'), true);

    // Verificăm dacă parametrul 'company' există și nu este gol
    if (!isset($deleteData['company']) || empty($deleteData['company'])) {
        http_response_code(400);
        echo json_encode([
            "error" => "Company parameter is missing or empty",
            "code" => 400
        ]);
        exit;
    }

    $company = $deleteData['company'];

    require_once '../config.php';

    $core  = 'jobs';

    // Step 1: Get the count of jobs for the given company
    $countCommand = '/select';
    $countQS = '?q=' . rawurlencode('hiringOrganization.name:"' . $company . '"') . '&wt=json&rows=0';
    $countUrl = 'http://' . $server . '/solr/' . $core . $countCommand . $countQS;

    $context = stream_context_create([
        'http' => [
            'header' => "Authorization: Basic " . base64_encode("$username:$password")
        ]
    ]);

    // Fetch data from Solr
    $countJson = @file_get_contents($countUrl, false, $context);

    // Check if Solr is down (server not responding)
    if ($countJson === false) {
        http_response_code(503);
        echo json_encode([
            "error" => "SOLR server in DEV is down",
            "code" => 503
        ]);
        exit;
    }

    $countResponse = json_decode($countJson, true);
    $jobCount = $countResponse['response']['numFound'] ?? 0;

    if ($jobCount === 0) {
        echo json_encode([
            'message' => 'No jobs found for the specified company',
            'jobCount' => 0
        ]);
        exit;
    }

    // Step 2: Delete the jobs
    $deleteCommand = '/update';
    $qs = '?commit=true&wt=json';
    $deleteUrl = 'http://' . $server . '/solr/' . $core . $deleteCommand . $qs;

    $deletePayload = json_encode([
        'delete' => ['query' => 'hiringOrganization.name:"' . $company . '"']
    ]);

    $deleteOptions = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => $deletePayload
        ]
    ];

    $deleteContext  = stream_context_create($deleteOptions);
    $deleteJson = @file_get_contents($deleteUrl, false, $deleteContext);

    // Check if Solr is down (server not responding)
    if ($deleteJson === false) {
        http_response_code(503);
        echo json_encode([
            "error" => "SOLR server in DEV is down",
            "code" => 503
        ]);
        exit;
    }

    echo json_encode([
        'message' => 'Jobs deleted successfully',
        'jobCount' => $jobCount
    ]);
} else {
    http_response_code(405);
    echo json_encode([
        'error' => 'Invalid request method',
        'code' => 405
    ]);
    exit;
}
