<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../../../../api.env');


//$username = ' ';
//$password = ' ';
//$server = '';
//$protocol ='';
//$port ='';
$solrUrl = $protocol.'://'. $server .':'.$port.'/solr/pegard/select?q=*:*&wt=json&omitHeader=true';


$opts = [
    "http" => [
        "method" => "GET",
        "header" => "Authorization: Basic " . base64_encode("$username:$password")
    ]
];

$context = stream_context_create($opts);

$response = file_get_contents($solrUrl, false, $context);

if ($response === false) {
    http_response_code(500);
    echo "Eroare la apelul catre Solr.";
    exit;
}

$data = json_decode($response, true);
$docs = $data['response']['docs'] ?? [];

$markers = [];


// codul tău principal
foreach ($docs as $doc) {
    $lat = (isset($doc['gpslat'][0])) ? $doc['gpslat'][0] : null;
    $lng = (isset($doc['gpslng'])) ? $doc['gpslng'][0] : null;
    $street = (isset($doc['street'][0])) ? $doc['street'][0] : '';
    $city = isset($doc['city'][0]) ? $doc['city'][0] : '';
    $county = isset($doc['county'][0]) ? $doc['county'][0] : '';


    $title = $street . ', ' . $city . ', ' . $county.', Romania';

    if ($lat !== null && $lng !== null) {
        $markers[] = [
            'lat' => $lat,
            'lng' => $lng,
            'title' => "$title"
        ];
    }
}


//header('Content-Type: application/json; charset=utf-8');
echo json_encode($markers, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
