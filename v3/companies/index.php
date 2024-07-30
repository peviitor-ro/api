<?php
// Allow cross-origin requests
header("Access-Control-Allow-Origin: *");

// Include the configuration file
require_once '../config.php';

// Define the core for Solr
$core = "jobs";

// Function to escape special characters in the user input for the regex query
function escapeSpecialCharacters($string) {
    return preg_quote($string, '/');
}

// Function to fetch companies from Solr based on user input
function getCompanies($userInput) {
    global $server, $core;

    // Escape special characters in user input for the regex pattern
    $escapedUserInput = escapeSpecialCharacters($userInput);

    // Construct the regex pattern for Solr
    $pattern = '.*' . implode('[^a-zA-Z0-9]*', str_split($escapedUserInput)) . '.*';

    // Construct the query string with the regex pattern and wildcard
    $qs = '?';
    $qs .= 'facet.field=company_str';
    $qs .= '&facet=true';
    $qs .= '&facet.limit=10000';
    $qs .= '&fl=company';
    $qs .= '&indent=true';
    $qs .= '&q.op=OR';
    $qs .= '&useParams=';
    $qs .= '&q=company_str:/'. urlencode($pattern) . '/';

    // Construct the URL for the Solr request
    $url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

    // Fetch the data from Solr
    $string = file_get_contents($url);

    if ($string === FALSE) {
        return json_encode(array("message" => "Failed to fetch data from Solr."));
    }

    $json = json_decode($string, true);

    if ($json === null) {
        return json_encode(array("message" => "Invalid JSON response from Solr."));
    }

    // Extract the companies from the response
    if (!isset($json['facet_counts']['facet_fields']['company_str'])) {
        return json_encode(array("message" => "No company data found in Solr response."));
    }

    $companies = $json['facet_counts']['facet_fields']['company_str'];
    $results = array();

    // Iterate through the companies and add them to the results
    for ($i = 0; $i < count($companies) / 2; $i++) {
        $k = 2 * $i;
        $companyName = $companies[$k];
        // Only add companies that start with the user input
        if (stripos($companyName, $userInput) !== false) {
            $results[] = $companyName;
        }
    }

    // Check if no matching companies were found
    if (empty($results)) {
        return json_encode(array("message" => "There are no companies with these letters"));
    }

    // Return the results as a JSON-encoded array
    return json_encode($results);
}

// Function to fetch the first 25 companies from Solr
function getFirst25Companies() {
    global $server, $core;

    // Construct the query string to fetch the first 25 companies
    $qs = '?';
    $qs .= 'facet.field=company_str';
    $qs .= '&facet=true';
    $qs .= '&facet.limit=25';
    $qs .= '&fl=company';
    $qs .= '&indent=true';
    $qs .= '&q.op=OR';
    $qs .= '&useParams=';
    $qs .= '&q=*:*';

    // Construct the URL for the Solr request
    $url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

    // Fetch the data from Solr
    $string = file_get_contents($url);

    if ($string === FALSE) {
        return json_encode(array("message" => "Failed to fetch data from Solr."));
    }

    $json = json_decode($string, true);

    if ($json === null) {
        return json_encode(array("message" => "Invalid JSON response from Solr."));
    }

    // Extract the companies from the response
    if (!isset($json['facet_counts']['facet_fields']['company_str'])) {
        return json_encode(array("message" => "No company data found in Solr response."));
    }

    $companies = $json['facet_counts']['facet_fields']['company_str'];
    $results = array();

    // Iterate through the companies and add them to the results
    for ($i = 0; $i < count($companies) / 2; $i++) {
        $k = 2 * $i;
        $companyName = $companies[$k];
        $results[] = $companyName;
    }

    // Return the results as a JSON-encoded array
    return json_encode($results);
}

// Retrieve the user input from the query parameter
$userInput = isset($_GET['userInput']) ? $_GET['userInput'] : '';

// Fetch the companies based on the user input or fetch the first 25 companies if no input is provided
if ($userInput) {
    echo getCompanies($userInput);
} else {
    echo getFirst25Companies();
}
?>
