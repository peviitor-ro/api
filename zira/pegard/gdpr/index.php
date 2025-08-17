<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['id']) || trim($_GET['id']) === '') {
    echo "You should specify the id.";
} else {

    $id = $_GET['id'];

    $solrUrl = 'http://zimbor.go.ro:80/solr/pegard/select?q=id:' . $id . '&wt=json&omitHeader=true';
    $username = 'solr';
    $password = 'SolrRocks';

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

    $personalData = [];

    // codul tău principal
    foreach ($docs as $doc) {
        $fullname = (isset($doc['fullname'][0])) ? $doc['fullname'][0] : null;
        $phone = (isset($doc['phone'])) ? $doc['phone'] : null;
        $email = (isset($doc['email'][0])) ? $doc['email'][0] : null;
        $gdprconsent = (isset($doc['gdprconsent'][0])) ? $doc['gdprconsent'][0] : false;
        $timestamp = (isset($doc['timestamp'][0])) ? $doc['timestamp'][0] : null;

        if ($gdprconsent === true) {
            $personalData[] = [
                'fullname' => $fullname,
                'phone' => $phone,
                'email' => $email,
                'gdprconsent' => $gdprconsent,
                'timestamp' => $timestamp
            ];
        }
    }

    echo json_encode($personalData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
