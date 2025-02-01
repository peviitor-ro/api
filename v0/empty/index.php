<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';

$method = 'POST';
$core  = 'jobs';
$command = '/update';

$qs = '?_=' . time() . '&commitWithin=1000&overwrite=true&wt=json';

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;

$string = @file_get_contents($url);
if ($string === FALSE) {
    http_response_code(503);
    echo json_encode([
        "error" => "SOLR server in DEV is down",
        "code" => 503
    ]);
    exit;
}

$data = json_encode(['delete' => ['query' => '*:*']]); //New Format

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    )
);

$context  = stream_context_create($options);

try {
    //Count the jobs before deletion
    $countUrl = 'http://' . $server . '/solr/' . $core . '/select?q=*:*&wt=json&rows=0';  // Count all jobs
    $countJson = @file_get_contents($countUrl);
    $countResponse = json_decode($countJson, true);
    $jobCount = $countResponse['response']['numFound'];

    //Count companies
    $qsCompanies = '?facet.field=company_str&facet.limit=2000000&facet=true&fl=company&q=*%3A*&rows=0';
    $companyUrl = 'http://' . $server . '/solr/' . $core . '/select' . $qsCompanies;
    $companyJson = @file_get_contents($companyUrl);
    $companyResponse = json_decode($companyJson, true);
    $companies = $companyResponse['facet_counts']['facet_fields']['company_str'];

    $companyCount = 0;
    for ($i = 1; $i < count($companies); $i += 2) {
        if ($companies[$i] > 0) {
            $companyCount++;
        }
    }
    $json = @file_get_contents($url, false, $context);

    echo $json;
    echo json_encode([
        'message' => 'Jobs deleted successfully',
        'jobsDeleted' => $jobCount,
        'companiesDeleted' => $companyCount
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'code' => $e->getCode()]);
}
