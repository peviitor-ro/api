<?php
// Dedicated local test store, never reads api.env or contacts a Solr server.
header('Content-Type: application/json');
$file = getenv('QSEARCH_MOCK_STORE');
$store = is_file($file) ? json_decode(file_get_contents($file), true) : [];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/reset') {
    $store = ['88000002' => ['id' => '88000002', 'company' => 'ZIRA CUSTOM NAME', 'brand' => 'KEEP ME', 'website' => ['https://example.test']]];
    file_put_contents($file, json_encode($store));
    echo '{}'; return;
}
if ($path === '/solr/company/get') {
    echo json_encode(['doc' => $store[$_GET['id']] ?? null]); return;
}
if ($path === '/solr/company/update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $docs = json_decode(file_get_contents('php://input'), true);
    foreach ($docs as $doc) {
        $id = $doc['id'];
        if ($id === '88000005') { http_response_code(500); echo '{"error":"storage failed"}'; return; }
        if ($id === '88000003') {
            $store[$id] = ['id' => $id, 'company' => 'RACE WINNER', 'brand' => 'PRESERVED'];
        }
        if (isset($store[$id]) && ($doc['_version_'] ?? null) === -1) {
            file_put_contents($file, json_encode($store));
            http_response_code(409); echo '{"error":"version conflict"}'; return;
        }
        if (($doc['_version_'] ?? null) !== -1) {
            http_response_code(400); echo '{"error":"create-only version required"}'; return;
        }
        $store[$id] = $doc;
    }
    file_put_contents($file, json_encode($store));
    echo '{"responseHeader":{"status":0}}'; return;
}
if ($path === '/solr/company/select') {
    $query = $_GET['q'] ?? '';
    // Verify the literal field query and mock the matching substring.
    if (preg_match('/^\{!complexphrase inOrder=true\}company:"\*(.*)\*"$/', $query, $phrase)) {
        $term = stripslashes(stripslashes($phrase[1]));
    } elseif (str_starts_with($query, 'company:*') && str_ends_with($query, '*')) {
        $term = substr($query, 9, -1);
        // A wildcard term containing a space cannot match separate indexed tokens.
        if (str_contains($term, '\\ ')) {
            echo '{"response":{"numFound":0,"docs":[]}}'; return;
        }
    } else {
        http_response_code(400); echo '{}'; return;
    }
    $docs = array_values(array_filter($store, fn($doc) => stripos($doc['company'], $term) !== false));
    echo json_encode(['response' => ['numFound' => count($docs), 'docs' => array_slice($docs, (int)($_GET['start'] ?? 0), (int)($_GET['rows'] ?? 10))]]);
    return;
}
http_response_code(501);
echo '{"error":"Endpoint not modeled"}';
