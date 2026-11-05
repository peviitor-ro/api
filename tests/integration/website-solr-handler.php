<?php
// Dedicated HTTP fixture for TestWebsiteGet.php; never connects to real Solr.
header('Content-Type: application/json');
$stateFile = getenv('WEBSITE_TEST_STATE');
$state = json_decode(file_get_contents($stateFile), true);
$state['calls']++;
file_put_contents($stateFile, json_encode($state));
$scenario = $state['scenario'];

if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) !== '/solr/company/select' ||
    ($_SERVER['HTTP_AUTHORIZATION'] ?? '') !== 'Basic ' . base64_encode('test-user:test-pass') ||
    ($_GET['q'] ?? '') !== 'website:[* TO *]' || ($_GET['fl'] ?? '') !== '*' ||
    ($_GET['sort'] ?? '') !== 'id asc' || !isset($_GET['cursorMark'], $_GET['rows'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Unexpected Solr request']);
    return;
}

$cursor = $_GET['cursorMark'];
if ($scenario === 'http-error' || ($scenario === 'later-error' && $cursor !== '*' && (int)base64_decode($cursor) >= 1000)) {
    http_response_code(500);
    echo json_encode(['error' => 'Solr failed']);
    return;
}
if ($scenario === 'invalid-json') {
    echo '<html>Unavailable</html>';
    return;
}
if ($scenario === 'malformed') {
    echo json_encode(['response' => ['docs' => 'invalid']]);
    return;
}

// Entire first page contains unusable websites: pagination must still continue.
$docs = [];
if ($scenario !== 'empty') {
    $invalid = [null, '', '   ', 'not a URL', [''], [' ', 'invalid'], 123, []];
    for ($i = 0; $i < 500; $i++) {
        $doc = ['id' => sprintf('%05d', $i), 'company' => ['No website']];
        if ($i % 9 !== 8) {
            $doc['website'] = $invalid[$i % 9];
        }
        $docs[] = $doc;
    }
    for ($i = 0; $i < ($scenario === 'invalid-only' ? 0 : 1005); $i++) {
        $docs[] = [
            'id' => sprintf('%05d', 500 + $i), 'company' => ['Example SRL'],
            'website' => $i === 0 ? 'https://example.com' : ['', 'invalid', 'https://example.com', 'https://example.org'],
            'location' => ['Bucharest'], 'brand' => ['Example'],
            'custom_details' => ['nested' => ['kept' => true]], '_version_' => 42
        ];
    }
}
$offset = $cursor === '*' ? 0 : (int)base64_decode($cursor);
// Simulate a server returning fewer rows than requested.
$page = array_slice($docs, $offset, min(500, (int)$_GET['rows']));
$next = $page ? base64_encode((string)($offset + count($page))) : $cursor;
$response = [
    'responseHeader' => ['status' => 0],
    'response' => ['numFound' => count($docs), 'docs' => $page],
    'nextCursorMark' => $next
];
if ($scenario === 'partial') {
    $response['responseHeader']['partialResults'] = true;
}
if ($scenario === 'missing-cursor') {
    unset($response['nextCursorMark']);
}
if ($scenario === 'solr-error') {
    $response['error'] = ['msg' => 'Query failed'];
}
echo json_encode($response);
