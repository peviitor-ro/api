<?php

function getJobsByJobLinksAndCompany($jobLinks, $company) {
    // Configurarea detaliilor despre serverul Solr
    $solrServer = 'https://solr.peviitor.ro'; // Adresa serverului Solr
    $coreName = 'jobs'; // Numele core-ului tău Solr

    $solrEndpoint = $solrServer . '/' . $coreName . '/select';

    // Construirea query-ului Solr
    $query = 'job_link:(' . implode(' OR ', array_map('urlencode', $jobLinks)) . ')';
    $filterQuery = 'fq=company:' . urlencode($company);

    // Construirea URL-ului final pentru apelul către Solr
    $solrUrl = $solrEndpoint . '?' . $query . '&' . $filterQuery;

    // Realizarea apelului către Solr
    $solrResponse = file_get_contents($solrUrl);

    // Procesarea răspunsului JSON de la Solr
    $result = json_decode($solrResponse, true);

    // Extrage job-urile din răspunsul Solr
    $jobs = [];
    if (isset($result['response']['docs'])) {
        $jobs = $result['response']['docs'];
    }

    return $jobs;
}

// Exemplu de folosire a funcției
$jobLinksToCheck = ['job_link_A', 'job_link_B', 'job_link_C'];
$companyToFilter = $_GET['company'];

$jobs = getJobsByJobLinksAndCompany($jobLinksToCheck, $companyToFilter);

// Afiseaza rezultatele
print_r($jobs);
?>
