<?php
header("Access-Control-Allow-Origin: *");

//#354
//using v3 API we get the data from PROD
//using v0 API we push data to local

header("Content-Type: application/json");

require_once '../config.php';

function getTotalJobs() {
    $url = "https://api.peviitor.ro/v3/total/";
    $response = file_get_contents($url);
    if (!$response) {
        throw new Exception("Failed to fetch total jobs data");
    }
    $data = json_decode($response, true);
    return $data['total']['jobs'] ?? 0;
}

function fetchAllJobs($totalJobs, $rowsPerPage = 500) {
    $totalPages = ceil($totalJobs / $rowsPerPage);
    $allJobs = [];

    for ($page = 1; $page <= $totalPages; $page++) {
        $start = ($page - 1) * $rowsPerPage;
        $url = "https://api.peviitor.ro/v3/search/?start=$start&rows=$rowsPerPage";

        $response = file_get_contents($url);
        if (!$response) {
            throw new Exception("Failed to fetch jobs data at page $page");
        }
        
        $data = json_decode($response, true);
        if (!empty($data["response"]["docs"])) {
            $allJobs = array_merge($allJobs, $data["response"]["docs"]);
        }
    }
    return $allJobs;
}

function sendDataToLocal($data) {
    global $server;
    $core = 'jobs';
    $command = '/update';
    $qs = '?commitWithin=1000&overwrite=true&wt=json';

    $url = 'http://' . $server . '/solr/' . $core . $command . $qs;
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'PUT',
            'content' => json_encode($data)
        )
    );

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) { 
        throw new Exception("Failed to push data to local Solr server");
    }
    return $result;
}

try {
    $totalJobs = getTotalJobs();

    if ($totalJobs > 0) {
        $allJobs = fetchAllJobs($totalJobs);
        $result = sendDataToLocal($allJobs);
        echo json_encode(['success' => true, 'result' => $result]);
    } else {
        echo json_encode(['success' => false, 'message' => "No jobs found."]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>