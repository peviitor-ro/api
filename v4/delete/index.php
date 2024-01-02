<?php

// Read JSON data from the request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// Solr endpoint URL
$solrEndpoint = 'http://zimbor.go.ro/solr/jobs/update?commit=true';

// Create an array to store the delete operations
$deleteOperations = [];

// Iterate through URLs and extract job_link values
foreach ($data['urls'] as $url) {
    // Extract job_link from the URL (modify this part based on your URL structure)
    $jobLink = extractJobLinkFromURL($url);

    // Create delete operation for the extracted job_link
    $deleteOperations[] = [
        'delete' => [
            'query' => 'job_link:' ."[". $jobLink."]",
        ],
    ];
}

// Function to extract job_link from a URL (modify this based on your URL structure)
function extractJobLinkFromURL($url) {
    // Implement logic to extract job_link from the URL
    // Example: parse_url, regular expressions, etc.
    // Replace the following line with your actual implementation
    return $url;
}

// Create HTTP context options for the request
$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($deleteOperations),
    ],
];

// Create context stream
$context = stream_context_create($options);

// Send the request to Solr
$response = file_get_contents($solrEndpoint, false, $context);

// Check for errors
if ($response === false) {
    echo 'Error sending request to Solr';
    var_dump($context);
} else {
    // Decode and print the Solr response
    $solrResponse = json_decode($response, true);
    echo json_encode($solrResponse);
}
