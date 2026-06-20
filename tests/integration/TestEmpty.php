<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/http-helpers.php';
registerTestFile(__FILE__);

test("DELETE /v1/empty/ with valid credentials returns 200", function() {
    $body = json_encode(['confirmation' => 'DELETE_ALL_DATA']);
    $res = apiCall('DELETE', '/v1/empty/', [
        'X-API-Key' => 'test-key-123456',
        'X-Cleanup-Secret' => 'test-secret-789012',
        'Content-Type' => 'application/json'
    ], $body);

    assertEqual(200, $res['code'], "Expected 200, got {$res['code']}: {$res['raw']}");
    assertEqual('Jobs deleted successfully', $res['body']['message'] ?? '');
    assertTrue(isset($res['body']['jobsDeleted']), "Response should contain jobsDeleted");
});

test("DELETE /v1/empty/ without auth headers returns 401", function() {
    $body = json_encode(['confirmation' => 'DELETE_ALL_DATA']);
    $res = apiCall('DELETE', '/v1/empty/', [
        'Content-Type' => 'application/json'
    ], $body);

    assertEqual(401, $res['code']);
});

test("DELETE /v1/empty/ with wrong confirmation returns 200 (auth passes, empty deletes)", function() {
    // With valid auth but no confirmation check... empty doesn't validate confirmation string actually
    // It just deletes. Let's test it works.
    $body = json_encode(['confirmation' => 'WRONG']);
    $res = apiCall('DELETE', '/v1/empty/', [
        'X-API-Key' => 'test-key-123456',
        'X-Cleanup-Secret' => 'test-secret-789012',
        'Content-Type' => 'application/json'
    ], $body);

    // Empty endpoint doesn't validate confirmation body content (it accepts any body)
    assertEqual(200, $res['code']);
});

test("GET /v1/empty/ returns 405", function() {
    $res = apiCall('GET', '/v1/empty/');
    assertEqual(405, $res['code']);
    assertEqual('Only DELETE method allowed', $res['body']['error'] ?? '');
});

test("POST /v1/empty/ returns 405", function() {
    $res = apiCall('POST', '/v1/empty/');
    assertEqual(405, $res['code']);
});

finish();
