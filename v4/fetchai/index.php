<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

// Verifică metoda POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda permisă este POST']);
    exit;
}

// Citește payload-ul brut
$rawData = file_get_contents('php://input');
$json = json_decode($rawData);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON invalid']);
    exit;
}

// Verifică dacă există câmpul items și este array
if (!isset($json->items) || !is_array($json->items)) {
    http_response_code(400);
    echo json_encode(['error' => 'Structură JSON invalidă: lipsă items']);
    exit;
}

// Transformă datele pentru Solr
$solrDocs = [];
foreach ($json->items as $item) {
    $doc = new stdClass();

    $doc->id = md5($item->job_link);
    $doc->job_title = html_entity_decode($item->job_title);
    $doc->company = $item->company;
    $doc->city = str_replace(
        ['Cluj Napoca', 'Bucharest', 'Bucuresti'],
        ['Cluj-Napoca', 'București', 'București'],
        $item->city
    );
    $doc->country = str_replace('Romania', 'România', $item->country);
    $doc->county = $item->county;
    $doc->remote = $item->remote;
    $doc->job_link = $item->job_link;

    $solrDocs[] = $doc;
}

// Pregătește payload-ul pentru Solr
$solrPayload = json_encode($solrDocs);

$server = 'zimbor.go.ro';
$core = 'jobs';
$command = '/update';
$qs = '?commitWithin=1000&overwrite=true&wt=json';

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;

// Date autentificare Basic
$username = 'solr';
$password = 'SolrRocks';

// Initializează cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Basic " . base64_encode("$username:$password")
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $solrPayload);

// Execută cererea
$result = curl_exec($ch);

if ($result === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Eroare la trimiterea datelor către Solr: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 400) {
    http_response_code($httpCode);
    echo json_encode(['error' => "Solr a răspuns cu codul HTTP $httpCode", 'response' => $result]);
    exit;
}

// Răspunde cu rezultatul Solr
echo $result;
