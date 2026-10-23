<?php
/**
 * ============================================================================
 * Company panel (/v7/panel/): email login link -> session -> manage API keys
 * ============================================================================
 *
 * STUB: an external system will authorize emails for companies and the login
 * link will be sent by email. Until then:
 *   - authorizations are 'authorization' docs in the Solr core 'apikeys',
 *     written by an admin with devops/apikeys/manage.php authorize ...
 *     -> replace findAuthorizedEmail() for the real integration
 *   - the link is shown on the page (PANEL_LINK_STUB=1) or only logged
 *     -> replace deliverLoginLink() for real email sending
 *
 * Login tokens are single use, valid 15 minutes, and only their SHA-256 is
 * stored ('token' docs in 'apikeys', next to the keys; see v7/lib/apikey.php).
 *
 * Emails in the 'firme'/'company' cores are NOT authorizations: they can be
 * added without authentication (firme/email/add).
 * ============================================================================
 */

require_once __DIR__ . '/apikey.php';

const LOGIN_TOKEN_TTL = 900;
const PANEL_SESSION_TTL = 3600;
const LINK_LIMIT_PER_EMAIL = 5;
const LINK_LIMIT_PER_IP = 20;
const LINK_LIMIT_WINDOW = 3600;

function normalizePanelEmail(string $email): ?string {
    $email = mb_strtolower(trim($email));
    if ($email === '' || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    return $email;
}

function generateLoginToken(): string {
    return bin2hex(random_bytes(32));
}

function hashLoginToken(string $token): string {
    return hash('sha256', $token);
}

function isValidLoginTokenFormat(string $token): bool {
    return (bool)preg_match('/^[0-9a-f]{64}$/', $token);
}

function isAuthorizationDocActive(?array $doc): bool {
    if (!$doc || solrScalar($doc['type'] ?? null) !== 'authorization') {
        return false;
    }
    $active = solrScalar($doc['active'] ?? false);
    return ($active === true || $active === 'true') && !empty(solrScalar($doc['cif'] ?? null));
}

/**
 * A token can be used once, before expires_at.
 */
function isTokenDocUsable(?array $doc, int $now): bool {
    if (!$doc || solrScalar($doc['type'] ?? null) !== 'token' || !empty($doc['used_at'])) {
        return false;
    }
    $expires = strtotime((string)solrScalar($doc['expires_at'] ?? ''));
    return $expires !== false && $expires > $now;
}

function isOverLinkRateLimit(int $recentForEmail, int $recentForIp): bool {
    return $recentForEmail >= LINK_LIMIT_PER_EMAIL || $recentForIp >= LINK_LIMIT_PER_IP;
}

/**
 * THE integration point with the external system: returns
 * ['email', 'cif', 'company'] when the email is authorized for a company, else null.
 */
function findAuthorizedEmail(string $solrBase, ?string $user, ?string $pass, string $email): ?array {
    $url = "$solrBase/" . API_KEY_CORE . "/get?id=" . rawurlencode($email) . "&wt=json";
    $doc = solrRequest('GET', $url, $user, $pass)['doc'] ?? null;
    if (!isAuthorizationDocActive($doc)) {
        return null;
    }
    return [
        'email'   => $email,
        'cif'     => (string)solrScalar($doc['cif']),
        'company' => (string)(solrScalar($doc['company'] ?? '') ?? ''),
    ];
}

function saveAuthorization(string $solrBase, ?string $user, ?string $pass, string $email, string $cif, string $company, bool $active): void {
    $doc = [
        'id'         => $email,
        'type'       => 'authorization',
        'email'      => $email,
        'cif'        => $cif,
        'company'    => $company,
        'active'     => $active,
        'created_at' => solrNow(),
    ];
    solrRequest('POST', "$solrBase/" . API_KEY_CORE . "/update?commit=true&wt=json", $user, $pass, json_encode([$doc], JSON_UNESCAPED_UNICODE));
}

function listAuthorizations(string $solrBase, ?string $user, ?string $pass, ?string $cif): array {
    $q = 'type:authorization' . ($cif === null ? '' : ' AND cif:"' . addcslashes($cif, '"\\') . '"');
    $query = http_build_query(['q' => $q, 'fl' => 'email,cif,company,active,created_at', 'sort' => 'email asc', 'rows' => 1000, 'wt' => 'json']);
    return solrRequest('GET', "$solrBase/" . API_KEY_CORE . "/select?$query", $user, $pass)['response']['docs'] ?? [];
}

function countRecentTokens(string $solrBase, ?string $user, ?string $pass, string $field, string $value): int {
    $query = http_build_query([
        'q'    => "type:token AND $field:\"" . addcslashes($value, '"\\') . '"',
        'fq'   => 'created_at:[NOW-' . LINK_LIMIT_WINDOW . 'SECONDS TO *]',
        'rows' => 0,
        'wt'   => 'json',
    ]);
    return (int)(solrRequest('GET', "$solrBase/" . API_KEY_CORE . "/select?$query", $user, $pass)['response']['numFound'] ?? 0);
}

function createLoginToken(string $solrBase, ?string $user, ?string $pass, string $email, string $ip): string {
    $token = generateLoginToken();
    $doc = [
        'id'         => hashLoginToken($token),
        'type'       => 'token',
        'email'      => $email,
        'ip'         => $ip,
        'created_at' => solrNow(),
        'expires_at' => gmdate('Y-m-d\TH:i:s\Z', time() + LOGIN_TOKEN_TTL),
    ];
    solrRequest('POST', "$solrBase/" . API_KEY_CORE . "/update?commit=true&wt=json", $user, $pass, json_encode([$doc]));
    return $token;
}

/**
 * Marks the token used and returns the authorization of its email, or null when the
 * token is unknown, expired, already used or its email is no longer authorized.
 */
function consumeLoginToken(string $solrBase, ?string $user, ?string $pass, string $token): ?array {
    if (!isValidLoginTokenFormat($token)) {
        return null;
    }
    $url = "$solrBase/" . API_KEY_CORE . "/get?id=" . hashLoginToken($token) . "&wt=json";
    $doc = solrRequest('GET', $url, $user, $pass)['doc'] ?? null;
    if (!isTokenDocUsable($doc, time())) {
        return null;
    }
    $doc['used_at'] = solrNow();
    unset($doc['_root_']);
    try {
        // _version_ from the read: of two parallel logins with the same token only one wins
        solrRequest('POST', "$solrBase/" . API_KEY_CORE . "/update?commit=true&wt=json", $user, $pass, json_encode([$doc]));
    } catch (Exception $e) {
        if ($e->getCode() === 409) {
            return null;
        }
        throw $e;
    }
    return findAuthorizedEmail($solrBase, $user, $pass, (string)solrScalar($doc['email']));
}

/**
 * THE integration point for email sending. Stub: returns the link so the page can
 * show it when PANEL_LINK_STUB=1, otherwise only logs it.
 */
function deliverLoginLink(string $email, string $link): ?string {
    if (getenv('PANEL_LINK_STUB') === '1') {
        return $link;
    }
    error_log("PANEL LINK (email sending not implemented): email=$email link=$link");
    return null;
}

function isHttpsRequest(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

/**
 * Absolute url of a panel page, e.g. https://api.peviitor.ro/v7/panel/login.php
 */
function panelUrl(string $page): string {
    $base = rtrim(trim(getenv('PANEL_BASE_URL') ?: ''), '/');
    if ($base === '') {
        $base = (isHttpsRequest() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
              . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    }
    return "$base/$page";
}

/**
 * Absolute url of /v7/jobs/, next to the panel, for the curl example.
 */
function panelJobsUrl(): string {
    return preg_replace('#/panel/x$#', '/jobs/', panelUrl('x'));
}

function clientIp(): string {
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// ----------------------------------------------------------------------------
// Session and CSRF
// ----------------------------------------------------------------------------

function panelStartSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('pv_panel');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/',
        'secure'   => isHttpsRequest(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

function panelCsrfToken(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function isValidCsrf(?string $expected, mixed $given): bool {
    return is_string($expected) && $expected !== '' && is_string($given) && hash_equals($expected, $given);
}

/**
 * Stops a POST whose csrf field does not match the session.
 */
function panelRequireCsrf(): void {
    if (!isValidCsrf($_SESSION['csrf'] ?? null, $_POST['csrf'] ?? null)) {
        http_response_code(400);
        echo "Invalid or expired form, reload the page and try again.";
        exit;
    }
}

function panelLogin(array $auth): void {
    session_regenerate_id(true);
    $_SESSION['user'] = $auth + ['login_at' => time()];
    unset($_SESSION['csrf']);
}

function panelLogout(): void {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Strict']);
        session_destroy();
    }
}

function isPanelSessionFresh(?array $user, int $now): bool {
    return $user && isset($user['login_at']) && $now - (int)$user['login_at'] < PANEL_SESSION_TTL;
}

/**
 * The logged-in user, re-checked against the authorization on every request
 * (an email withdrawn by the external system loses access at once). Null when
 * there is no valid session; the session is then destroyed.
 */
function panelCurrentUser(string $solrBase, ?string $user, ?string $pass): ?array {
    $current = $_SESSION['user'] ?? null;
    if (!isPanelSessionFresh($current, time())) {
        if ($current) {
            panelLogout();
        }
        return null;
    }
    $auth = findAuthorizedEmail($solrBase, $user, $pass, $current['email']);
    if (!$auth || $auth['cif'] !== $current['cif']) {
        panelLogout();
        return null;
    }
    return $auth + ['login_at' => $current['login_at']];
}

/**
 * Solr base url and credentials from api.env, as [base, user, pass].
 */
function panelSolrConfig(): array {
    $server = trim(getenv('SOLR_SERVER') ?: '');
    $protocol = trim(getenv('PROTOCOL') ?: '') ?: 'http';
    return [$server ? "$protocol://$server/solr" : '', trim(getenv('SOLR_USER') ?: '') ?: null, trim(getenv('SOLR_PASS') ?: '') ?: null];
}
