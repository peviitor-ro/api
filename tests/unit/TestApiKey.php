<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../v7/lib/apikey.php';
registerTestFile(__FILE__);

test("generated key has pv_live_ prefix and 48 hex chars", function() {
    $key = generateApiKey();
    assertTrue(isValidApiKeyFormat($key), "Bad format: $key");
    assertEqual(56, strlen($key));
});

test("generated keys are unique", function() {
    $keys = [];
    for ($i = 0; $i < 50; $i++) {
        $keys[generateApiKey()] = true;
    }
    assertEqual(50, count($keys));
});

test("hash is sha256 and deterministic", function() {
    $key = 'pv_live_' . str_repeat('ab', 24);
    assertEqual(hash('sha256', $key), hashApiKey($key));
    assertEqual(64, strlen(hashApiKey($key)));
    assertEqual(hashApiKey($key), hashApiKey($key));
});

test("different keys give different hashes", function() {
    assertNotEqual(hashApiKey(generateApiKey()), hashApiKey(generateApiKey()));
});

test("display prefix is the first 16 characters", function() {
    $key = 'pv_live_3f9a0c1d' . str_repeat('0', 40);
    assertEqual('pv_live_3f9a0c1d', apiKeyDisplayPrefix($key));
});

test("format check rejects old md5 keys, wrong prefix, uppercase and wrong length", function() {
    assertTrue(!isValidApiKeyFormat(md5('NUME SRL')));
    assertTrue(!isValidApiKeyFormat('pv_test_' . str_repeat('a', 48)));
    assertTrue(!isValidApiKeyFormat('pv_live_' . str_repeat('A', 48)));
    assertTrue(!isValidApiKeyFormat('pv_live_' . str_repeat('a', 47)));
    assertTrue(!isValidApiKeyFormat(''));
});

test("key doc usable only when it is a key, active and bound to a CIF", function() {
    assertTrue(isApiKeyDocUsable(['type' => 'key', 'active' => true, 'cif' => '12345678']));
    assertTrue(isApiKeyDocUsable(['type' => ['key'], 'active' => [true], 'cif' => ['12345678']]), "multivalued form");
    assertTrue(!isApiKeyDocUsable(null), "missing doc");
    assertTrue(!isApiKeyDocUsable(['type' => 'key', 'active' => false, 'cif' => '12345678']), "revoked");
    assertTrue(!isApiKeyDocUsable(['type' => 'key', 'active' => true]), "no cif");
    assertTrue(!isApiKeyDocUsable(['type' => 'key', 'cif' => '12345678']), "no active flag");
    assertTrue(!isApiKeyDocUsable(['active' => true, 'cif' => '12345678']), "no type");
    assertTrue(!isApiKeyDocUsable(['type' => 'authorization', 'active' => true, 'cif' => '12345678']), "panel authorization doc");
});

test("solrScalar unwraps single-element arrays", function() {
    assertEqual('x', solrScalar(['x']));
    assertEqual('x', solrScalar('x'));
    assertEqual(null, solrScalar([]));
});

finish();
