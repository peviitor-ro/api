<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../v7/lib/panel.php';
registerTestFile(__FILE__);

test("email is trimmed, lowercased and validated", function() {
    assertEqual('hr@firma.ro', normalizePanelEmail('  HR@Firma.RO '));
    assertEqual(null, normalizePanelEmail('not-an-email'));
    assertEqual(null, normalizePanelEmail(''));
    assertEqual(null, normalizePanelEmail(str_repeat('a', 250) . '@x.ro'), "longer than 254");
});

test("login token is 64 hex chars and unique", function() {
    $tokens = [];
    for ($i = 0; $i < 20; $i++) {
        $t = generateLoginToken();
        assertTrue(isValidLoginTokenFormat($t), "Bad format: $t");
        $tokens[$t] = true;
    }
    assertEqual(20, count($tokens));
});

test("token format check rejects other strings", function() {
    assertTrue(!isValidLoginTokenFormat(''));
    assertTrue(!isValidLoginTokenFormat(str_repeat('a', 63)));
    assertTrue(!isValidLoginTokenFormat(str_repeat('A', 64)));
    assertTrue(!isValidLoginTokenFormat('pv_live_' . str_repeat('a', 48)));
});

test("token hash is sha256", function() {
    $t = str_repeat('ab', 32);
    assertEqual(hash('sha256', $t), hashLoginToken($t));
});

test("token doc usable only when unused and not expired", function() {
    $now = strtotime('2026-10-18T12:00:00Z');
    $doc = ['type' => 'token', 'email' => 'a@b.ro', 'expires_at' => '2026-10-18T12:10:00Z'];
    assertTrue(isTokenDocUsable($doc, $now));
    assertTrue(isTokenDocUsable(['type' => ['token'], 'expires_at' => ['2026-10-18T12:10:00Z']], $now), "multivalued form");
    assertTrue(!isTokenDocUsable(null, $now), "missing");
    assertTrue(!isTokenDocUsable(['expires_at' => '2026-10-18T12:00:00Z'] + $doc, $now), "expired exactly now");
    assertTrue(!isTokenDocUsable(['used_at' => '2026-10-18T11:59:00Z'] + $doc, $now), "used");
    assertTrue(!isTokenDocUsable(['type' => 'authorization'] + $doc, $now), "wrong type");
    assertTrue(!isTokenDocUsable(['type' => 'token', 'expires_at' => 'garbage'], $now), "bad date");
});

test("authorization doc active only with type, active flag and CIF", function() {
    $doc = ['type' => 'authorization', 'email' => 'a@b.ro', 'cif' => '123', 'active' => true];
    assertTrue(isAuthorizationDocActive($doc));
    assertTrue(isAuthorizationDocActive(['active' => [true], 'cif' => ['123']] + $doc), "multivalued form");
    assertTrue(!isAuthorizationDocActive(['active' => false] + $doc), "inactive");
    assertTrue(!isAuthorizationDocActive(['cif' => ''] + $doc), "no cif");
    assertTrue(!isAuthorizationDocActive(['type' => 'token'] + $doc), "token doc");
    assertTrue(!isAuthorizationDocActive(null));
});

test("link rate limit: 5 per email, 20 per IP", function() {
    assertTrue(!isOverLinkRateLimit(4, 19));
    assertTrue(isOverLinkRateLimit(5, 0));
    assertTrue(isOverLinkRateLimit(0, 20));
});

test("csrf compare needs a non-empty matching string", function() {
    assertTrue(isValidCsrf('abc', 'abc'));
    assertTrue(!isValidCsrf('abc', 'abd'));
    assertTrue(!isValidCsrf('', ''), "empty session token");
    assertTrue(!isValidCsrf(null, 'abc'));
    assertTrue(!isValidCsrf('abc', ['abc']), "array from the form");
});

test("panel session expires after one hour", function() {
    $now = 1_000_000;
    assertTrue(isPanelSessionFresh(['login_at' => $now - 3599], $now));
    assertTrue(!isPanelSessionFresh(['login_at' => $now - 3600], $now));
    assertTrue(!isPanelSessionFresh(null, $now));
    assertTrue(!isPanelSessionFresh(['email' => 'a@b.ro'], $now), "no login_at");
});

test("active key count ignores revoked keys", function() {
    assertEqual(2, countActiveApiKeys([
        ['type' => 'key', 'active' => true, 'cif' => '1'], ['type' => 'key', 'active' => false, 'cif' => '1'],
        ['type' => ['key'], 'active' => [true], 'cif' => ['1']],
    ]));
});

test("key prefix format", function() {
    assertTrue(isValidApiKeyPrefix('pv_live_3f9a0c1d'));
    assertTrue(!isValidApiKeyPrefix('pv_live_3f9a0c1'));
    assertTrue(!isValidApiKeyPrefix('pv_live_3F9A0C1D'));
    assertTrue(!isValidApiKeyPrefix('"; drop'));
});

finish();
