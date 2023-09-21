<?php

 function get_server(){
        //get the IP of the server
        //we need a config file to know where is the SOLR
        require('../../_config/index.php');
        return $server;
    }
// Replace these variables with your SOLR server details

$coreName = 'jobs'; // Name of your SOLR core

// Function to check if SOLR server is up and running
function isSolrServerUp($solrUrl, $coreName) {
    $pingUrl = $solrUrl . '/' . $coreName . '/admin/ping?wt=json';

    // Initialize cURL session with an HTTP HEAD request
    $ch = curl_init($pingUrl);

    // Set cURL options for an HTTP HEAD request
    curl_setopt($ch, CURLOPT_NOBODY, true); // Use HEAD request
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // Do not return the response body
    curl_setopt($ch, CURLOPT_TIMEOUT, 1); // Wait for a maximum of 1 second for a response

    // Execute cURL request
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close cURL session
    curl_close($ch);

    // Check the HTTP response code
    if ($httpCode === 200) {
        return true; // Server is up
    }

    return false; // Server is down or not responding
}

$message = array();
$server = get_server();
   
   
foreach ($server as $solrUrl) {
 $msg = new stdClass();

if (isSolrServerUp($solrUrl, $coreName))
{
  
  $msg->server = $solrUrl;
  $msg->status = "up";
} else {
    $msg->server = $solrUrl;
    $msg->status = "down";
}
 $message[]= $msg; 
}
echo json_encode($message);


?>
