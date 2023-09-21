<?php
//$data = "https://solr.peviitor.ro/solr/";

 function get_server(){
        //get the IP of the server
        //we need a config file to know where is the SOLR
        require('../../../../_config/index.php');
        return $server;
    }


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
    $data = $solrUrl;
    break;
} else {
    $msg->server = $solrUrl;
    $msg->status = "down";
}
 $message[]= $msg; 
}

// Data to be written to the file


// File path
$filePath = "../server.txt";

// Write data to the file
if (file_put_contents($filePath, $data) !== false) {
    echo $data;
} else {
    echo "Unable to write data to the file.";
}
?>
