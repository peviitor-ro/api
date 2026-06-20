<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/http-helpers.php';
registerTestFile(__FILE__);

test("GET /v1/random/ returns 200 with valid job", function() {
    $res = apiCall('GET', '/v1/random/');

    assertEqual(200, $res['code'], "Expected 200, got {$res['code']}: {$res['raw']}");
    assertTrue(is_array($res['body']), "Response should be a JSON object");
    assertTrue(isset($res['body']['title']), "Response should contain title");
    assertTrue(isset($res['body']['company']), "Response should contain company");
    assertTrue(isset($res['body']['url']), "Response should contain url");
    assertEqual('Inginer IT', $res['body']['title']);
    assertEqual('NUME SRL', $res['body']['company']);
});

test("GET /v1/random/ has correct field types", function() {
    $res = apiCall('GET', '/v1/random/');
    assertEqual(200, $res['code']);
    assertTrue(is_string($res['body']['title']));
    assertTrue(is_string($res['body']['company']));
    assertTrue(is_array($res['body']['location']));
    assertTrue(is_string($res['body']['workmode']));
    assertTrue(is_string($res['body']['url']));
});

test("POST /v1/random/ returns 405", function() {
    $res = apiCall('POST', '/v1/random/');
    assertEqual(405, $res['code']);
    assertEqual('Only GET method allowed', $res['body']['error'] ?? '');
});

test("DELETE /v1/random/ returns 405", function() {
    $res = apiCall('DELETE', '/v1/random/');
    assertEqual(405, $res['code']);
});

finish();
