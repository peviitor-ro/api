<?php

// Read JSON data from the request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// Check if 'urls' key exists and is an array
if (!isset($data['urls']) || !is_array($data['urls'])) {
    echo 'Invalid or missing "urls" key in the JSON payload.';
    exit;
}

// Check if there are URLs to process
if (empty($data['urls'])) {
    echo 'No URLs provided in the JSON payload.';
    exit;
}

// Solr endpoint URL
$solrEndpoint = 'http://zimbor.go.ro/solr/jobs/update?commit=true';

// Create an array to store the delete operations
$deleteOperations = [];

// Iterate through URLs and create delete operations
foreach ($data['urls'] as $url) {
    // Create delete operation for each URL
    $deleteOperations[] = [
        'delete' => [
            'query' => 'job_link:' . $url,
        ],
    ];
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
    var_dump($context); // Print the context for debugging
} else {
    // Decode and print the Solr response
    $solrResponse = json_decode($response, true);
    echo json_encode($solrResponse);
}
?>
