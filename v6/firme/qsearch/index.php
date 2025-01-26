<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");

// Include configuration file from the directory structure
require_once __DIR__ . '/../../config.php';

// Define Solr core and the endpoint for selecting documents
$core = 'firme';
$selectEndpoint = '/select';

// Retrieve the 'q' query parameter from the URL, handle URL encoding, and check if it's provided
$query = isset($_GET['q']) ? htmlspecialchars(urldecode($_GET['q'])) : '';

// Respond with an error if the query parameter is missing
if (empty($query)) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing required query parameter: q"]);
    exit;
}

// Construct the URL to query the Solr server including necessary Solr query parameters
$queryString = http_build_query([
    'q.op' => 'OR',   // Use OR operator for queries
    'q' => "\"$query\"",
    'useParams' => '',
    'omitHeader' => 'true',
    'wt' => 'json',
]);

// Formulate the full URL using the server setting from config.php
$url = "http://$server/solr/$core$selectEndpoint?$queryString";

// Execute the GET request to the Solr server
$result = file_get_contents($url);

// Handle possible errors in the GET request or send back results
if ($result === FALSE) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Failed to retrieve data from Solr"]);
} else {
    $resultArray = json_decode($result, true);
    if (isset($resultArray['response']['docs'])) {
        echo json_encode($resultArray['response']['docs']);
    } else {
        echo json_encode([]);
    }
}
?>
