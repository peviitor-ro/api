<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/http-helpers.php';
registerTestFile(__FILE__);

// Fresh mock store for the 'apikeys' core
file_get_contents('http://127.0.0.1:18983/mock/reset', false, stream_context_create(['http' => ['method' => 'POST', 'ignore_errors' => true]]));

// Keys known by mock-handler.php: '1...' is active for CIF 87654321, '2...' is revoked
$KEY = 'pv_live_' . str_repeat('1', 48);
$REVOKED = 'pv_live_' . str_repeat('2', 48);

function v7(string $method, ?string $key, ?array $body = null, string $query = ''): array {
    $headers = ['Content-Type' => 'application/json'];
    if ($key !== null) {
        $headers['X-Api-Key'] = $key;
    }
    return apiCall($method, '/v7/jobs/' . $query, $headers, $body === null ? null : json_encode($body));
}

test("GET without X-Api-Key returns 401", function() {
    $res = v7('GET', null);
    assertEqual(401, $res['code'], $res['raw']);
    assertEqual('Missing X-Api-Key header', $res['body']['error'] ?? '');
});

test("GET with a malformed key returns 401", function() {
    assertEqual(401, v7('GET', md5('TEST V7 SRL'))['code']);
});

test("GET with a revoked key returns 401", function() use ($REVOKED) {
    assertEqual(401, v7('GET', $REVOKED)['code']);
});

test("GET lists only the jobs posted through v7", function() use ($KEY) {
    $res = v7('GET', $KEY);
    assertEqual(200, $res['code'], $res['raw']);
    assertEqual('87654321', $res['body']['cif']);
    assertEqual(1, $res['body']['count']);
    assertEqual('https://example.com/v7/own', $res['body']['jobs'][0]['url']);
});

test("POST a new job returns 201, company comes from the key", function() use ($KEY) {
    $res = v7('POST', $KEY, ['url' => 'https://example.com/v7/new', 'title' => 'Programator PHP', 'location' => 'Brașov', 'workmode' => 'remote']);
    assertEqual(201, $res['code'], $res['raw']);
    assertEqual(1, $res['body']['count']);
});

test("POST a url that was already scraped returns 409 already_scraped", function() use ($KEY) {
    $res = v7('POST', $KEY, ['url' => 'https://example.com/scraped/1', 'title' => 'Orice', 'location' => 'Arad']);
    assertEqual(409, $res['code'], $res['raw']);
    assertEqual('already_scraped', $res['body']['conflicts'][0]['reason']);
});

test("POST of a url created by someone else after the check: 409, and this request's writes are rolled back", function() use ($KEY) {
    $res = v7('POST', $KEY, [
        ['url' => 'https://example.com/v7/race-before', 'title' => 'Scris înainte', 'location' => 'Iasi'],
        ['url' => 'https://example.com/v7/race', 'title' => 'Cursă', 'location' => 'Iasi'],
    ]);
    assertEqual(409, $res['code'], $res['raw']);
    assertEqual('created_concurrently', $res['body']['conflicts'][0]['reason']);
    assertEqual('https://example.com/v7/race', $res['body']['conflicts'][0]['url']);
    $q = json_decode(file_get_contents('http://127.0.0.1:18983/mock/last-delete'), true)['query'] ?? '';
    assertTrue(str_contains($q, 'url:"https://example.com/v7/race-before"'), "rollback misses the earlier doc: $q");
    assertTrue(str_contains($q, 'cif:"87654321"') && str_contains($q, 'source:"company"') && str_contains($q, 'date:"'), "rollback not limited to this request: $q");
});

test("POST a url owned by another company returns 409", function() use ($KEY) {
    $res = v7('POST', $KEY, ['url' => 'https://example.com/other', 'title' => 'Contabil', 'location' => 'Cluj-Napoca']);
    assertEqual(409, $res['code'], $res['raw']);
    assertEqual('url_belongs_to_another_company', $res['body']['conflicts'][0]['reason']);
});

test("POST invalid jobs returns 400 with errors per job", function() use ($KEY) {
    $res = v7('POST', $KEY, [['url' => 'ftp://x.ro', 'location' => 'Iasi'], ['url' => 'https://x.ro/2', 'title' => 'Dev', 'location' => 'Iasi', 'workmode' => 'acasa']]);
    assertEqual(400, $res['code'], $res['raw']);
    assertEqual(2, count($res['body']['invalid']));
});

test("POST more than 100 jobs returns 400", function() use ($KEY) {
    $jobs = array_map(fn($i) => ['url' => "https://x.ro/$i", 'title' => 'Dev', 'location' => 'Iasi'], range(1, 101));
    assertEqual(400, v7('POST', $KEY, $jobs)['code']);
});

test("PUT on a scraped job returns 403", function() use ($KEY) {
    assertEqual(403, v7('PUT', $KEY, ['url' => 'https://example.com/scraped/1', 'title' => 'Altceva'])['code']);
});

test("PUT on an own job returns 200 with the merged job", function() use ($KEY) {
    $res = v7('PUT', $KEY, ['url' => 'https://example.com/v7/own', 'title' => 'Tester Senior']);
    assertEqual(200, $res['code'], $res['raw']);
    assertEqual('Tester Senior', $res['body']['job']['title']);
    assertEqual(['Iasi'], $res['body']['job']['location']);
});

test("PUT on a missing job returns 404", function() use ($KEY) {
    assertEqual(404, v7('PUT', $KEY, ['url' => 'https://example.com/none', 'title' => 'X'])['code']);
});

test("DELETE another company's job returns 403", function() use ($KEY) {
    assertEqual(403, v7('DELETE', $KEY, null, '?url=' . rawurlencode('https://example.com/other'))['code']);
});

test("DELETE an own job returns 200", function() use ($KEY) {
    $res = v7('DELETE', $KEY, null, '?url=' . rawurlencode('https://example.com/v7/own'));
    assertEqual(200, $res['code'], $res['raw']);
});

test("PATCH returns 405", function() use ($KEY) {
    assertEqual(405, v7('PATCH', $KEY)['code']);
});

finish();
