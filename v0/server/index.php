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
    $pingUrl = $solrUrl .  $coreName . '/admin/ping?wt=json';

    // Initialize cURL session
    $ch = curl_init($pingUrl);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    // Execute cURL request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close cURL session
    curl_close($ch);

    // Check the HTTP response code
    if ($httpCode === 200) {
        $data = json_decode($response, true);

        // Check if SOLR is up and running based on the response
        if (isset($data['status']) && $data['status'] === 'OK') {
            return true;
        }
    }

    return false;
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
