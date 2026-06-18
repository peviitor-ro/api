<?php
require_once __DIR__ . '/../helpers.php';
registerTestFile(__FILE__);

$API_BASE = getenv('E2E_API_BASE') ?: 'https://api.peviitor.ro';

function apiE2E(string $method, string $path, array $headers = [], ?string $body = null): array {
    $h = [];
    foreach ($headers as $k => $v) {
        $h[] = "$k: $v";
    }
    global $API_BASE;
    $opts = [
        'http' => [
            'method'  => $method,
            'header'  => implode("\r\n", $h),
            'content' => $body,
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ];
    $url = $API_BASE . $path;
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);

    $httpCode = 0;
    if (isset($http_response_header)) {
        preg_match('#HTTP/[0-9.]+ (\d+)#', $http_response_header[0], $m);
        $httpCode = (int)$m[1];
    }

    return [
        'code' => $httpCode,
        'body' => $response !== false ? json_decode($response, true) : null,
        'raw'  => $response
    ];
}

test("GET /v1/random/ returns 200", function() {
    $res = apiE2E('GET', '/v1/random/');
    assertEqual(200, $res['code'], "Expected 200, got {$res['code']}");
    assertTrue(isset($res['body']['title']), "Response should contain title");
    assertTrue(isset($res['body']['company']), "Response should contain company");
    assertTrue(isset($res['body']['url']), "Response should contain url");
});

test("GET /v1/random/ returns valid JSON with expected fields", function() {
    $res = apiE2E('GET', '/v1/random/');
    assertEqual(200, $res['code']);
    assertTrue(is_string($res['body']['title'] ?? null));
    assertTrue(is_string($res['body']['company'] ?? null));
    assertTrue(is_array($res['body']['location'] ?? null));
    assertTrue(is_string($res['body']['cif'] ?? null));
});

test("DELETE /v1/empty/ without auth returns 401", function() {
    $res = apiE2E('DELETE', '/v1/empty/', [
        'Content-Type' => 'application/json'
    ], json_encode(['confirmation' => 'DELETE_ALL_DATA']));
    assertEqual(401, $res['code']);
});

test("DELETE /v1/cleanjobs/ without auth returns 401", function() {
    $res = apiE2E('DELETE', '/v1/cleanjobs/', [
        'Content-Type' => 'application/json'
    ], json_encode(['confirmation' => 'CLEAN_COMPANY_JOBS', 'company' => 'TEST']));
    assertEqual(401, $res['code']);
});

test("GET /v1/empty/ returns 405", function() {
    $res = apiE2E('GET', '/v1/empty/');
    assertEqual(405, $res['code']);
});

test("GET /v1/cleanjobs/ returns 405", function() {
    $res = apiE2E('GET', '/v1/cleanjobs/');
    assertEqual(405, $res['code']);
});

test("POST /v1/random/ returns 405", function() {
    $res = apiE2E('POST', '/v1/random/');
    assertEqual(405, $res['code']);
});

finish();
