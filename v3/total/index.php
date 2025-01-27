<?php

header("Access-Control-Allow-Origin: *");

require_once '../config.php';

class EndpointNotAvailableException extends Exception {}

/**
 * Builds a query string from an associative array of parameters.
 *
 * This function takes an array of key-value pairs and converts them into a URL-encoded
 * query string. The resulting string starts with a question mark (?) followed by the
 * encoded parameters.
 *
 * @param array $params An associative array of parameters to be included in the query string.
 *                      The keys represent the parameter names, and the values represent the
 *                      parameter values.
 * 
 * @return string The URL-encoded query string starting with a question mark (?).
 */
function buildQueryString(array $params): string {
    return '?' . http_build_query($params);
}

/**
 * Fetches JSON data from a given URL.
 *
 * @param string $url The URL to fetch JSON data from.
 * 
 * @return array The decoded JSON data as an associative array.
 * 
 * @throws EndpointNotAvailableException If the endpoint is not available, 
 *                                       if the content could not be retrieved, 
 *                                       or if the JSON response is invalid.
 */
function fetchJsonData(string $url): array {
    $headers = @get_headers($url);

    if ($headers === false || strpos($headers[0], '200') === false) {
        throw new EndpointNotAvailableException('Endpoint-ul nu este disponibil: ' . $url);
    }

    $response = file_get_contents($url);
    if ($response === false) {
        throw new EndpointNotAvailableException('Nu s-a putut obține conținutul de la: ' . $url);
    }

    $json = json_decode($response, true);
    if ($json === null) {
        throw new EndpointNotAvailableException('Răspuns JSON invalid de la: ' . $url);
    }

    return $json;
}

try {
    $core = "jobs";

    $params = [
        'facet.field' => 'company_str',
        'facet.limit' => 2000000,
        'facet' => 'true',
        'fl' => 'company',
        'indent' => 'true',
        'q.op' => 'OR',
        'q' => '*:*',
        'rows' => 0,
        'start' => 0,
        'useParams' => ''
    ];

    $url = 'http://' . $server . '/solr/' . $core . '/select' . buildQueryString($params);

    $json = fetchJsonData($url);

    $companies = $json['facet_counts']['facet_fields']['company_str'] ?? [];
    $totalJobs = $json['response']['numFound'] ?? 0;

    $obj = (object) [
        'total' => (object) [
            'jobs' => (string) $totalJobs,
            'companies' => (string) (count($companies) / 2)
        ]
    ];

    echo json_encode($obj);

} catch (EndpointNotAvailableException $e) {
    $backupUrl = $backup . '/mobile/total/';

    try {
        $json = fetchJsonData($backupUrl);

        $obj = (object) [
            'total' => (object) [
                'jobs' => (string) ($json['total'] ?? 0)
            ]
        ];

        echo json_encode($obj);

    } catch (Exception $backupException) {
        echo json_encode([
            'error' => 'Ambele endpoint-uri sunt indisponibile.',
            'details' => $backupException->getMessage()
        ]);
    }
}