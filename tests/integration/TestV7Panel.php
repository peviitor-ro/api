<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/http-helpers.php';
registerTestFile(__FILE__);

// The mock keeps the 'apikeys' core in a temp file (see mock-handler.php)
const MOCK = 'http://127.0.0.1:18983';
const AUTHORIZED = 'hr@test-v7.ro';

function mockPost(string $path, array $docs = []): void {
    file_get_contents(MOCK . $path, false, stream_context_create(['http' => [
        'method' => 'POST', 'header' => 'Content-Type: application/json', 'content' => json_encode($docs), 'ignore_errors' => true,
    ]]));
}

function seedDocs(string $core, array $docs): void {
    mockPost("/solr/$core/update", $docs);
}

function mockGet(string $core, string $id): ?array {
    return json_decode(file_get_contents(MOCK . "/solr/$core/get?id=" . rawurlencode($id)), true)['doc'] ?? null;
}

function seedToken(string $token, string $email, int $expiresIn, ?string $usedAt = null): void {
    $doc = ['id' => hash('sha256', $token), 'type' => 'token', 'email' => $email, 'ip' => '10.0.0.1',
            'created_at' => gmdate('Y-m-d\TH:i:s\Z'), 'expires_at' => gmdate('Y-m-d\TH:i:s\Z', time() + $expiresIn)];
    if ($usedAt) {
        $doc['used_at'] = $usedAt;
    }
    seedDocs('apikeys', [$doc]);
}

/**
 * A browser-like client for the panel: keeps cookies, does not follow redirects.
 */
class PanelClient {
    public array $cookies = [];

    public function request(string $method, string $path, array $form = []): array {
        $headers = [];
        if ($this->cookies) {
            $headers[] = 'Cookie: ' . implode('; ', array_map(fn($k, $v) => "$k=$v", array_keys($this->cookies), $this->cookies));
        }
        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        $context = stream_context_create(['http' => [
            'method' => $method, 'header' => implode("\r\n", $headers), 'content' => http_build_query($form),
            'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 5,
        ]]);
        $body = @file_get_contents("http://127.0.0.1:8080$path", false, $context);
        $code = 0;
        $location = null;
        foreach ($http_response_header ?? [] as $h) {
            if (preg_match('#^HTTP/[0-9.]+ (\d+)#', $h, $m)) {
                $code = (int)$m[1];
            } elseif (preg_match('/^Set-Cookie: ([^=]+)=([^;]*)/i', $h, $m)) {
                if ($m[2] === '' || $m[2] === 'deleted') {
                    unset($this->cookies[$m[1]]);
                } else {
                    $this->cookies[$m[1]] = $m[2];
                }
            } elseif (preg_match('/^Location: (.*)$/i', $h, $m)) {
                $location = trim($m[1]);
            }
        }
        return ['code' => $code, 'body' => (string)$body, 'location' => $location];
    }

    public function csrf(string $path): string {
        preg_match('/name="csrf" value="([0-9a-f]+)"/', $this->request('GET', $path)['body'], $m);
        return $m[1] ?? '';
    }

    public function requestLink(string $email): array {
        return $this->request('POST', '/v7/panel/', ['csrf' => $this->csrf('/v7/panel/'), 'email' => $email]);
    }

    public function login(string $token): array {
        $path = '/v7/panel/login.php?token=' . $token;
        return $this->request('POST', '/v7/panel/login.php', ['csrf' => $this->csrf($path), 'token' => $token]);
    }

    public function keysAction(string $action, array $extra = []): array {
        return $this->request('POST', '/v7/panel/keys.php', ['csrf' => $this->csrf('/v7/panel/keys.php'), 'action' => $action] + $extra);
    }
}

function stubToken(string $body): ?string {
    return preg_match('/login\.php\?token=([0-9a-f]{64})/', $body, $m) ? $m[1] : null;
}

function authorize(string $email, bool $active = true): void {
    seedDocs('apikeys', [['id' => $email, 'type' => 'authorization', 'email' => $email, 'cif' => '87654321', 'company' => 'TEST V7 SRL', 'active' => $active]]);
}

/**
 * Logged-in client of company 87654321. Each call uses its own authorized email,
 * since one email may request only 5 links per hour.
 */
function loggedInClient(?string &$email = null): PanelClient {
    static $n = 0;
    $email = 'user' . ++$n . '@test-v7.ro';
    authorize($email);
    $c = new PanelClient();
    $token = stubToken($c->requestLink($email)['body']);
    $res = $c->login((string)$token);
    assertEqual(303, $res['code'], "login failed: " . substr($res['body'], 0, 300));
    return $c;
}

function v7Status(string $key): int {
    return apiCall('GET', '/v7/jobs/', ['X-Api-Key' => $key])['code'];
}

mockPost('/mock/reset');
seedDocs('apikeys', [
    ['id' => AUTHORIZED, 'type' => 'authorization', 'email' => AUTHORIZED, 'cif' => '87654321', 'company' => 'TEST V7 SRL', 'active' => true, 'created_at' => '2026-01-01T00:00:00Z'],
    ['id' => 'old@test-v7.ro', 'type' => 'authorization', 'email' => 'old@test-v7.ro', 'cif' => '87654321', 'company' => 'TEST V7 SRL', 'active' => false, 'created_at' => '2026-01-01T00:00:00Z'],
]);
seedDocs('apikeys', [
    ['id' => hash('sha256', 'pv_live_' . str_repeat('3', 48)), 'type' => 'key', 'cif' => '11111111', 'company' => 'ALTA SRL', 'prefix' => 'pv_live_33333333', 'active' => true, 'created_at' => '2026-01-01T00:00:00Z'],
]);

test("panel page renders the email form with a CSRF token and sets the session cookie", function() {
    $c = new PanelClient();
    $res = $c->request('GET', '/v7/panel/');
    assertEqual(200, $res['code']);
    assertTrue(str_contains($res['body'], 'name="email"'));
    assertTrue(preg_match('/name="csrf" value="[0-9a-f]{64}"/', $res['body']) === 1);
    assertTrue(isset($c->cookies['pv_panel']), "no pv_panel cookie");
});

test("POST without CSRF token returns 400", function() {
    $res = (new PanelClient())->request('POST', '/v7/panel/', ['email' => AUTHORIZED]);
    assertEqual(400, $res['code']);
});

test("invalid email shows an error", function() {
    $res = (new PanelClient())->requestLink('not-an-email');
    assertEqual(200, $res['code']);
    assertTrue(str_contains($res['body'], 'adresă de email validă'));
});

test("authorized email gets a login link (stub shows it on the page)", function() {
    $res = (new PanelClient())->requestLink('  HR@Test-V7.ro ');
    assertTrue(str_contains($res['body'], 'Dacă adresa'));
    assertTrue(stubToken($res['body']) !== null, "no stub link");
});

test("unauthorized and deauthorized emails get the same message and no link", function() {
    foreach (['nobody@test-v7.ro', 'old@test-v7.ro'] as $email) {
        $res = (new PanelClient())->requestLink($email);
        assertTrue(str_contains($res['body'], 'Dacă adresa'), $email);
        assertEqual(null, stubToken($res['body']), "$email got a link");
    }
});

test("over the per-email rate limit: same message, no link", function() {
    $email = 'busy@test-v7.ro';
    authorize($email);
    for ($i = 0; $i < 5; $i++) {
        seedToken(bin2hex(random_bytes(32)), $email, 900);
    }
    $res = (new PanelClient())->requestLink($email);
    assertTrue(str_contains($res['body'], 'Dacă adresa'));
    assertEqual(null, stubToken($res['body']));
});

test("GET on the login link does not consume the token", function() {
    $c = new PanelClient();
    $token = stubToken($c->requestLink(AUTHORIZED)['body']);
    $get = $c->request('GET', "/v7/panel/login.php?token=$token");
    assertEqual(200, $get['code']);
    assertTrue(str_contains($get['body'], 'Intră în panou'));
    assertEqual(null, mockGet('apikeys', hash('sha256', $token))['used_at'] ?? null);
    assertEqual(303, $c->login($token)['code']);
});

test("valid token logs in and redirects to keys.php; the token cannot be reused", function() {
    $c = new PanelClient();
    $token = stubToken($c->requestLink(AUTHORIZED)['body']);
    $res = $c->login($token);
    assertEqual(303, $res['code']);
    assertEqual('keys.php', $res['location']);
    assertEqual(400, (new PanelClient())->login($token)['code'], "reused token");
});

test("expired, used and unknown tokens are refused", function() {
    $expired = bin2hex(random_bytes(32));
    $used = bin2hex(random_bytes(32));
    seedToken($expired, 'seeded@test-v7.ro', -10);
    seedToken($used, 'seeded@test-v7.ro', 900, '2026-01-01T00:00:00Z');
    foreach ([$expired, $used, bin2hex(random_bytes(32))] as $t) {
        $res = (new PanelClient())->login($t);
        assertEqual(400, $res['code']);
        assertTrue(str_contains($res['body'], 'invalid, expirat'));
    }
    assertEqual(400, (new PanelClient())->request('GET', '/v7/panel/login.php?token=abc')['code'], "bad format");
});

test("keys.php without a session redirects to the email form", function() {
    $res = (new PanelClient())->request('GET', '/v7/panel/keys.php');
    assertEqual(303, $res['code']);
    assertEqual('./', $res['location']);
});

test("keys.php lists only this company's keys", function() {
    $res = loggedInClient()->request('GET', '/v7/panel/keys.php');
    assertEqual(200, $res['code']);
    assertTrue(str_contains($res['body'], 'TEST V7 SRL'));
    assertTrue(str_contains($res['body'], 'pv_live_11111111'));
    assertTrue(!str_contains($res['body'], 'pv_live_33333333'), "shows another company's key");
});

test("generating a key while the company has an active one is refused", function() {
    // the seed has pv_live_11111111 active for this company
    $res = loggedInClient()->keysAction('generate');
    assertTrue(!str_contains($res['body'], 'id="new-key"'), "second active key created");
    assertTrue(str_contains($res['body'], 'Firma are deja o cheie activă'));
});

test("revoking the own key makes it fail on /v7/jobs/", function() {
    $key = 'pv_live_' . str_repeat('1', 48);
    assertEqual(200, v7Status($key));
    $res = loggedInClient()->keysAction('revoke', ['prefix' => 'pv_live_11111111']);
    assertTrue(str_contains($res['body'], 'a fost revocată'));
    assertEqual(401, v7Status($key));
});

test("after revoking, a new key is generated, shown once, and works on /v7/jobs/", function() {
    $c = loggedInClient();
    $res = $c->keysAction('generate');
    assertTrue(preg_match('/id="new-key">(pv_live_[0-9a-f]{48})</', $res['body'], $m) === 1, "no key shown");
    assertEqual(200, v7Status($m[1]));
    assertTrue(str_ends_with((string)(mockGet('apikeys', hash('sha256', $m[1]))['created_by'] ?? ''), '@test-v7.ro'), "created_by not set");
    assertEqual(1, substr_count($res['body'], $m[1]), "key shown more than once (e.g. in the examples)");
    assertTrue(str_contains($res['body'], 'Salvează cheia acum'));
    assertTrue(!str_contains($c->request('GET', '/v7/panel/keys.php')['body'], $m[1]), "full key shown again");
    assertTrue(!str_contains($c->keysAction('generate')['body'], 'id="new-key"'), "second active key created");
});

test("keys page shows curl examples for GET, POST, PUT and DELETE without a key", function() {
    $body = loggedInClient()->request('GET', '/v7/panel/keys.php')['body'];
    foreach (['GET', 'POST', 'PUT', 'DELETE'] as $method) {
        assertTrue(str_contains($body, "<code>$method</code>"), $method);
    }
    assertTrue(str_contains($body, 'X-Api-Key: $PEVIITOR_API_KEY'));
    assertTrue(str_contains($body, 'href="postman.php"'));
});

test("Postman collection download needs a session and contains no key", function() {
    assertEqual(303, (new PanelClient())->request('GET', '/v7/panel/postman.php')['code']);
    $res = loggedInClient()->request('GET', '/v7/panel/postman.php');
    assertEqual(200, $res['code']);
    $collection = json_decode($res['body'], true);
    assertEqual(4, count($collection['item'] ?? []), substr($res['body'], 0, 200));
    assertTrue(str_ends_with($collection['variable'][0]['value'], '/v7/jobs/'), "baseUrl");
    assertTrue(!str_contains($res['body'], 'pv_live_'), "key in the collection");
});

test("revoking another company's key is refused", function() {
    $res = loggedInClient()->keysAction('revoke', ['prefix' => 'pv_live_33333333']);
    assertTrue(str_contains($res['body'], 'Cheia nu a fost găsită'));
    assertEqual(true, mockGet('apikeys', hash('sha256', 'pv_live_' . str_repeat('3', 48)))['active']);
});

test("logout ends the session", function() {
    $c = loggedInClient();
    assertEqual(303, $c->keysAction('logout')['code']);
    assertEqual(303, $c->request('GET', '/v7/panel/keys.php')['code']);
});

test("deauthorizing the email ends an open session", function() {
    $c = loggedInClient($email);
    authorize($email, false);
    assertEqual(303, $c->request('GET', '/v7/panel/keys.php')['code']);
});

finish();
