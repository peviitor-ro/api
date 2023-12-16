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
    // Construct the SOLR core URL
    $coreUrl = rtrim($solrUrl, '/') . '/' . $coreName;

    // Create a sample query URL for a ping request with omitHeader=true
    $pingUrl = $coreUrl . '/admin/ping?wt=json&omitHeader=true';

    // Make the external call to SOLR using file_get_contents
    $response = file_get_contents($pingUrl);


    // Decode the JSON response
    $decodedResponse = json_decode($response, true);

    // Check if the ping was successful
    return isset($decodedResponse['status']) && $decodedResponse['status'] == 'OK';
}




$message = array();
$server = get_server();
   
   
foreach ($server as $solrUrl) {
  $msg = new stdClass();
  $msg->server = $solrUrl;
  $msg->testUrl = rtrim($solrUrl, '/') . '/' . $coreName.'/admin/ping?wt=json&omitHeader=true';
  
if (isSolrServerUp($solrUrl, $coreName))
{  
	$msg->status = "up";
} else {
    $msg->status = "down";
}
 $message[]= $msg; 
}
echo json_encode($message);


?>
