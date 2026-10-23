<?php
/**
 * ============================================================================
 * API keys for companies (used by /v7/)
 * ============================================================================
 *
 * A key belongs to exactly one company (CIF). Only the SHA-256 hash of the key
 * is stored, in the Solr core 'apikeys' (the hash is the document id), so a
 * leaked Solr dump does not leak usable keys.
 *
 * The 'apikeys' core holds everything for this feature, told apart by 'type':
 *   key            an API key (this file)
 *   authorization  an email allowed into the panel for a company (v7/lib/panel.php)
 *   token          a single-use panel login link (v7/lib/panel.php)
 *
 * Key format: pv_live_<48 hex chars>   e.g. pv_live_3f9a0c...
 * The first 16 characters (the "prefix") are stored in clear so a key can be
 * identified in logs and revoked without knowing the full key.
 *
 * Keys are created/listed/revoked by an admin with devops/apikeys/manage.php,
 * or by the company itself in the panel (/v7/panel/).
 * ============================================================================
 */

const API_KEY_PREFIX = 'pv_live_';
const API_KEY_CORE = 'apikeys';
const API_KEY_DOC_TYPE = 'key';
// One active key per company: to get a new key, the current one must be revoked first
// (enforced in the panel and in manage.php)
const MAX_ACTIVE_KEYS_PER_COMPANY = 1;

function generateApiKey(): string {
    return API_KEY_PREFIX . bin2hex(random_bytes(24));
}

function hashApiKey(string $key): string {
    return hash('sha256', $key);
}

function apiKeyDisplayPrefix(string $key): string {
    return substr($key, 0, 16);
}

function isValidApiKeyFormat(string $key): bool {
    return (bool)preg_match('/^' . API_KEY_PREFIX . '[0-9a-f]{48}$/', $key);
}

/**
 * A key document is usable only if it is active and bound to a company.
 */
function isApiKeyDocUsable(?array $doc): bool {
    if (!$doc || solrScalar($doc['type'] ?? null) !== API_KEY_DOC_TYPE) {
        return false;
    }
    $active = $doc['active'] ?? false;
    if (is_array($active)) {
        $active = $active[0] ?? false;
    }
    return ($active === true || $active === 'true') && !empty($doc['cif']);
}

/**
 * Solr may return single-valued fields as arrays depending on the schema.
 */
function solrScalar(mixed $value): mixed {
    return is_array($value) ? ($value[0] ?? null) : $value;
}

function solrRequest(string $method, string $url, ?string $user, ?string $pass, ?string $payload = null, string $contentType = 'application/json'): array {
    $headers = [];
    if ($user && $pass) {
        $headers[] = "Authorization: Basic " . base64_encode("$user:$pass");
    }
    if ($payload !== null) {
        $headers[] = "Content-Type: $contentType";
    }
    $context = stream_context_create([
        'http' => [
            'method'  => $method,
            'header'  => implode("\r\n", $headers),
            'content' => $payload ?? '',
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        $err = error_get_last()['message'] ?? 'Unknown error';
        throw new Exception("SOLR REQUEST FAILED: $url | $err");
    }
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('#HTTP/[0-9.]+ (\d+)#', $http_response_header[0], $m)) {
        $status = (int)$m[1];
    }
    $json = json_decode($data, true);
    if ($status >= 400) {
        // The code is the Solr HTTP status, e.g. 409 on an optimistic-concurrency (_version_) conflict
        throw new Exception("SOLR ERROR $status: $url | " . ($json['error']['msg'] ?? substr($data, 0, 200)), $status);
    }
    if (!is_array($json)) {
        throw new Exception("Invalid JSON response from Solr");
    }
    return $json;
}

/**
 * Real-time get by hash: sees new or revoked keys immediately, without waiting for a commit.
 */
function fetchApiKeyDoc(string $solrBase, ?string $user, ?string $pass, string $key): ?array {
    $url = "$solrBase/" . API_KEY_CORE . "/get?id=" . rawurlencode(hashApiKey($key)) . "&wt=json";
    $json = solrRequest('GET', $url, $user, $pass);
    return $json['doc'] ?? null;
}

function solrNow(): string {
    return gmdate('Y-m-d\TH:i:s\Z');
}

/**
 * Creates a key for a company and returns it. The full key exists only in the
 * return value; Solr gets its hash. $createdBy is the panel email or 'cli'.
 */
function createApiKey(string $solrBase, ?string $user, ?string $pass, string $cif, string $company, string $createdBy, ?string $note = null): string {
    $key = generateApiKey();
    $doc = [
        'id'         => hashApiKey($key),
        'type'       => API_KEY_DOC_TYPE,
        'prefix'     => apiKeyDisplayPrefix($key),
        'cif'        => $cif,
        'company'    => $company,
        'active'     => true,
        'created_at' => solrNow(),
        'created_by' => $createdBy,
    ];
    if ($note !== null && $note !== '') {
        $doc['note'] = $note;
    }
    solrRequest('POST', "$solrBase/" . API_KEY_CORE . "/update?commit=true&wt=json", $user, $pass, json_encode([$doc], JSON_UNESCAPED_UNICODE));
    return $key;
}

/**
 * Keys of one company (or all, with $cif null), newest first. Never contains the key hash.
 */
function listApiKeys(string $solrBase, ?string $user, ?string $pass, ?string $cif): array {
    $query = http_build_query([
        'q'    => 'type:' . API_KEY_DOC_TYPE . ($cif === null ? '' : ' AND cif:"' . addcslashes($cif, '"\\') . '"'),
        'fl'   => 'type,prefix,cif,company,active,created_at,created_by,revoked_at,note',
        'sort' => 'created_at desc',
        'rows' => 1000,
        'wt'   => 'json',
    ]);
    return solrRequest('GET', "$solrBase/" . API_KEY_CORE . "/select?$query", $user, $pass)['response']['docs'] ?? [];
}

function countActiveApiKeys(array $docs): int {
    return count(array_filter($docs, 'isApiKeyDocUsable'));
}

function isValidApiKeyPrefix(string $prefix): bool {
    return (bool)preg_match('/^' . API_KEY_PREFIX . '[0-9a-f]{8}$/', $prefix);
}

/**
 * Revokes the key with this display prefix and returns its document.
 * With $requiredCif (panel), a key of another company is treated as not found.
 * Throws InvalidArgumentException with a message safe to show to the caller.
 */
function revokeApiKey(string $solrBase, ?string $user, ?string $pass, string $prefix, ?string $requiredCif = null): array {
    if (!isValidApiKeyPrefix($prefix)) {
        throw new InvalidArgumentException("Prefix must look like " . API_KEY_PREFIX . "3f9a0c1d");
    }
    $query = http_build_query(['q' => 'type:' . API_KEY_DOC_TYPE . " AND prefix:\"$prefix\"", 'rows' => 2, 'wt' => 'json']);
    $docs = solrRequest('GET', "$solrBase/" . API_KEY_CORE . "/select?$query", $user, $pass)['response']['docs'] ?? [];
    if ($requiredCif !== null) {
        $docs = array_values(array_filter($docs, fn($d) => (string)solrScalar($d['cif'] ?? '') === $requiredCif));
    }
    if (count($docs) !== 1) {
        throw new InvalidArgumentException(count($docs) === 0 ? "No key with prefix $prefix" : "Several keys share prefix $prefix, revoke by hand in Solr");
    }
    $doc = $docs[0];
    unset($doc['_version_'], $doc['_root_']);
    $doc['active'] = false;
    $doc['revoked_at'] = solrNow();
    solrRequest('POST', "$solrBase/" . API_KEY_CORE . "/update?commit=true&wt=json", $user, $pass, json_encode([$doc], JSON_UNESCAPED_UNICODE));
    return $doc;
}

/**
 * Stops the request with 401 unless the X-Api-Key header holds an active company key.
 * Returns ['cif' => ..., 'company' => ..., 'prefix' => ...] for the calling company.
 */
function requireCompanyApiKey(string $solrBase, ?string $user, ?string $pass): array {
    $key = trim($_SERVER['HTTP_X_API_KEY'] ?? '');

    if ($key === '') {
        http_response_code(401);
        echo json_encode(["error" => "Missing X-Api-Key header"]);
        exit;
    }

    if (!isValidApiKeyFormat($key)) {
        http_response_code(401);
        echo json_encode(["error" => "Invalid API key"]);
        exit;
    }

    try {
        $doc = fetchApiKeyDoc($solrBase, $user, $pass, $key);
    } catch (Exception $e) {
        error_log("APIKEY LOOKUP FAILED: " . $e->getMessage());
        http_response_code(503);
        echo json_encode(["error" => "Authentication service unavailable"]);
        exit;
    }

    if (!isApiKeyDocUsable($doc)) {
        error_log("APIKEY REJECTED: prefix=" . apiKeyDisplayPrefix($key));
        http_response_code(401);
        echo json_encode(["error" => "Invalid API key"]);
        exit;
    }

    return [
        'cif'     => (string)solrScalar($doc['cif']),
        'company' => (string)(solrScalar($doc['company'] ?? '') ?? ''),
        'prefix'  => (string)(solrScalar($doc['prefix'] ?? '') ?? apiKeyDisplayPrefix($key)),
    ];
}
