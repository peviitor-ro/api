<?php
header("Access-Control-Allow-Origin: *");

require_once '../config.php';

$core = 'jobs';
$qs = '?';
$qs = $qs . $_SERVER['QUERY_STRING'];

if ($_SERVER['QUERY_STRING'] == "page=1") {
    $qs .= "&q=%22*%3A*%22"; // Enclose the search query in quotes
} else {
    // Ensure other queries are also enclosed in quotes
    parse_str($_SERVER['QUERY_STRING'], $queryParams);
    if (isset($queryParams['q'])) {
        // Construct the query string in the required format
        $query = $queryParams['q'];
        $queryParams['q'] = '"' . $query . '"';
        $qs = '?' . http_build_query($queryParams);
    }
}

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

$json = file_get_contents($url);
echo $json;
?>
