<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: *');

// Server settings
$server = '172.18.0.10:8983'; //DEV
$core = 'jobs';

try {
    // Get the max count of jobs
    $url = 'http://' .$server .'/solr/' . $core . '/select?q=' . urlencode('*:*') . '&rows=0';
    $string = @file_get_contents($url);
    if ($string === FALSE) {
        throw new Exception('SOLR server is down', 503);
    }
    $json = json_decode($string, true);

    $max = $json['response']['numFound'];
    $start = rand(0, $max-1); // Select a random job

    // Fetch the selected job in solr
    $url = 'http://' .$server .'/solr/' . $core . '/select?q=' . urlencode('*:*') . '&rows=1' . '&start=' . $start . '&omitHeader=true';
    $json = @file_get_contents($url);
    if ($json === FALSE) {
        list($version,$status,$msg) = explode(' ', $http_response_header[0], 3);
        throw new Exception('Your call to Solr failed and returned HTTP status: ' . $status, $status);
    }

    echo $json; // Display job

} catch (Exception $e) {
    http_response_code($e->getCode());
    echo $e->getMessage();
    exit;
}
?>
