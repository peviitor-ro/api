<?php
require_once __DIR__ . '/../helpers.php';
registerTestFile(__FILE__);

function computeKey(?string $company, ?string $cif): ?string {
    if ($company && $cif) return md5($company . $cif);
    if ($company) return md5($company);
    return null;
}

function validateKey(string $apiKey, ?string $company, ?string $cif, ?string $brand): bool {
    if ($company && $cif) {
        return hash_equals(md5($company . $cif), $apiKey);
    }
    if ($company) {
        return hash_equals(md5($company), $apiKey);
    }
    if ($brand) {
        return hash_equals(md5($brand), $apiKey);
    }
    return false;
}

test("md5 company+cif matches expected", function() {
    $expected = md5('NUME SRL' . '12345678');
    assertEqual($expected, computeKey('NUME SRL', '12345678'));
});

test("md5 company only matches expected", function() {
    $expected = md5('NUME SRL');
    assertEqual($expected, computeKey('NUME SRL', null));
});

test("null when no company", function() {
    assertEqual(null, computeKey(null, '12345678'));
});

test("validateKey success with company+cif", function() {
    assertTrue(validateKey(md5('NUME SRL' . '12345678'), 'NUME SRL', '12345678', null));
});

test("validateKey fails with wrong key", function() {
    assertTrue(!validateKey('wrong-key', 'NUME SRL', '12345678', null));
});

test("validateKey empty key fails", function() {
    assertTrue(!validateKey('', 'NUME SRL', '12345678', null));
});

test("validateKey success with brand only", function() {
    assertTrue(validateKey(md5('ORANGE'), null, null, 'ORANGE'));
});

test("validateKey fails when no identifiers given", function() {
    assertTrue(!validateKey('some-key', null, null, null));
});

test("hash_equals timing-safe comparison works", function() {
    assertTrue(hash_equals('abc', 'abc'));
    assertTrue(!hash_equals('abc', 'xyz'));
});

finish();
