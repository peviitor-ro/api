<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/http-helpers.php';
registerTestFile(__FILE__);

test("DELETE /v1/cleanjobs/ with company+cif and valid key returns 200", function() {
    $company = 'NUME SRL';
    $cif = '12345678';
    $key = md5($company . $cif);

    $body = json_encode([
        'company' => $company,
        'cif' => $cif,
        'confirmation' => 'CLEAN_COMPANY_JOBS'
    ]);
    $res = apiCall('DELETE', '/v1/cleanjobs/', [
        'X-Api-Key' => $key,
        'Content-Type' => 'application/json'
    ], $body);

    assertEqual(200, $res['code'], "Expected 200, got {$res['code']}: {$res['raw']}");
    assertEqual('Jobs deleted successfully', $res['body']['message'] ?? '');
    assertTrue(isset($res['body']['jobCount']), "Response should contain jobCount");
    assertEqual($company, $res['body']['company'] ?? '');
    assertEqual($cif, $res['body']['cif'] ?? '');
});

test("DELETE /v1/cleanjobs/ with wrong X-Api-Key returns 401", function() {
    $body = json_encode([
        'company' => 'NUME SRL',
        'cif' => '12345678',
        'confirmation' => 'CLEAN_COMPANY_JOBS'
    ]);
    $res = apiCall('DELETE', '/v1/cleanjobs/', [
        'X-Api-Key' => 'wrong-key',
        'Content-Type' => 'application/json'
    ], $body);

    assertEqual(401, $res['code']);
    assertTrue(str_contains($res['body']['error'] ?? '', 'Unauthorized'));
});

test("DELETE /v1/cleanjobs/ without X-Api-Key returns 401", function() {
    $body = json_encode([
        'company' => 'NUME SRL',
        'cif' => '12345678',
        'confirmation' => 'CLEAN_COMPANY_JOBS'
    ]);
    $res = apiCall('DELETE', '/v1/cleanjobs/', [
        'Content-Type' => 'application/json'
    ], $body);

    assertEqual(401, $res['code']);
});

test("DELETE /v1/cleanjobs/ with wrong confirmation returns 400", function() {
    $body = json_encode([
        'company' => 'NUME SRL',
        'cif' => '12345678',
        'confirmation' => 'WRONG'
    ]);
    $res = apiCall('DELETE', '/v1/cleanjobs/', [
        'X-Api-Key' => md5('NUME SRL' . '12345678'),
        'Content-Type' => 'application/json'
    ], $body);

    assertEqual(400, $res['code']);
    assertTrue(str_contains($res['body']['error'] ?? '', 'Confirmation'));
});

test("DELETE /v1/cleanjobs/ without identifiers returns 400", function() {
    $body = json_encode([
        'confirmation' => 'CLEAN_COMPANY_JOBS'
    ]);
    $res = apiCall('DELETE', '/v1/cleanjobs/', [
        'X-Api-Key' => 'some-key',
        'Content-Type' => 'application/json'
    ], $body);

    assertEqual(400, $res['code']);
});

test("DELETE /v1/cleanjobs/ with brand lookup returns 200", function() {
    $brand = 'ORANGE';
    $key = md5($brand);

    $body = json_encode([
        'brand' => $brand,
        'confirmation' => 'CLEAN_COMPANY_JOBS'
    ]);
    $res = apiCall('DELETE', '/v1/cleanjobs/', [
        'X-Api-Key' => $key,
        'Content-Type' => 'application/json'
    ], $body);

    assertEqual(200, $res['code'], "Expected 200, got {$res['code']}: {$res['raw']}");
    assertEqual($brand, $res['body']['brand'] ?? '');
});

test("DELETE /v1/cleanjobs/ with unknown company returns 404", function() {
    $company = 'UNKNOWN';
    $key = md5($company);

    $body = json_encode([
        'company' => $company,
        'confirmation' => 'CLEAN_COMPANY_JOBS'
    ]);
    $res = apiCall('DELETE', '/v1/cleanjobs/', [
        'X-Api-Key' => $key,
        'Content-Type' => 'application/json'
    ], $body);

    assertEqual(404, $res['code']);
});

test("GET /v1/cleanjobs/ returns 405", function() {
    $res = apiCall('GET', '/v1/cleanjobs/');
    assertEqual(405, $res['code']);
});

test("POST /v1/cleanjobs/ returns 405", function() {
    $res = apiCall('POST', '/v1/cleanjobs/');
    assertEqual(405, $res['code']);
});

finish();
