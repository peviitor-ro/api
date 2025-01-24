<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");

// Include the configuration file which is in two levels up in the directory structure
require_once __DIR__ . '/../../config.php';

// Define specific Solr core and endpoint
$core = 'firme';
$selectEndpoint = '/select';

// Retrieve the 'id' from the query parameter
$id = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : '';

if (empty($id)) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing required query parameter: id"]);
    exit;
}

// Construct the URL to query the Solr server
$queryString = http_build_query([
    'q' => "id:$id",
    'wt' => 'json',
    'omitHeader' => 'true' // Try to simplify the output format
]);

$url = "http://" . $server . "/solr/$core$selectEndpoint?$queryString";

// Perform the GET request
$result = file_get_contents($url);
if ($result === FALSE) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Failed to retrieve data from Solr"]);
} else {
    $resultArray = json_decode($result, true);
    
    // Manually strip out the responseHeader if needed
    if(isset($resultArray['response']['docs'])) {
        echo json_encode($resultArray['response']['docs']);
    } else {
        echo json_encode([]);
    }
}
?>
