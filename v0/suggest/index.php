<?php

function getSolrSuggestions($query) {
    $baseUrl = 'http://localhost:8983/solr/jobs/suggest'; // URL-ul endpoint-ului Solr pentru sugestii
    $params = [
        'suggest' => 'true',
        'suggest.build' => 'true',
        'suggest.dictionary' => 'jobTitleSuggester',
        'suggest.q' => $query, // Query-ul introdus de utilizator
        'wt' => 'json', // Cerem răspunsul în format JSON
    ];

    // Construim URL-ul final cu parametrii
    $url = $baseUrl . '?' . http_build_query($params);
    
    // Creăm un context pentru request, cu un header care acceptă json
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\n"
        ]
    ]);

    // Facem request-ul la Solr
    $response = file_get_contents($url, false, $context);

    // Decodăm răspunsul JSON
    $data = json_decode($response, true);

    // Verificăm dacă avem sugestii disponibile
    if (isset($data['suggest']['jobTitleSuggester'][$query]['suggestions'])) {
        return $data['suggest']['jobTitleSuggester'][$query]['suggestions'];
    }

    return [];
}

// Exemplu de utilizare a funcției
$keyword = 'FUNCT'; // presupunem că acesta este inputul de la utilizator
if (strlen($keyword) > 3) {
    $suggestions = getSolrSuggestions($keyword);
    header('Content-Type: application/json');
    echo json_encode($suggestions);
}