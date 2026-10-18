<?php
/**
 * ============================================================================
 * Admin tool for company API keys (used by /v7/)
 * ============================================================================
 *
 * Runs ONLY from the command line, on an admin's machine, with an api.env that
 * holds the Solr credentials (SOLR_SERVER, PROTOCOL, SOLR_USER, SOLR_PASS).
 * Whoever can run it already has write access to Solr, so no extra admin
 * secret is needed. It is never reachable over HTTP (CLI check + .htaccess).
 *
 * USAGE:
 *   php devops/apikeys/manage.php create --cif=12345678 [--note="contact: hr@firma.ro"]
 *   php devops/apikeys/manage.php list   [--cif=12345678]
 *   php devops/apikeys/manage.php revoke --prefix=pv_live_3f9a0c1d
 *
 *   Panel access (stub for the external system that authorizes company emails):
 *   php devops/apikeys/manage.php authorize   --email=hr@firma.ro --cif=12345678
 *   php devops/apikeys/manage.php deauthorize --email=hr@firma.ro
 *   php devops/apikeys/manage.php authorizations [--cif=12345678]
 *
 *   --env=path/to/api.env   use another env file (default: api.env in the repo root)
 *   --force                 create a key / authorize an email although the company is
 *                           missing or inactive in the 'company' core
 *                           (then --company="Name" is required)
 *
 * The full key is printed ONCE, at creation. Only its SHA-256 hash is stored.
 * ============================================================================
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../../util/loadEnv.php';
require_once __DIR__ . '/../../v7/lib/panel.php';

function fail(string $msg): never {
    fwrite(STDERR, "Error: $msg\n");
    exit(1);
}

function parseArgs(array $argv): array {
    $args = ['_command' => $argv[1] ?? ''];
    foreach (array_slice($argv, 2) as $arg) {
        if (preg_match('/^--([a-z]+)(?:=(.*))?$/s', $arg, $m)) {
            $args[$m[1]] = $m[2] ?? true;
        } else {
            fail("Unknown argument: $arg");
        }
    }
    return $args;
}

function normalizeCif(string $cif): string {
    $cif = preg_replace('/^RO/i', '', trim($cif));
    if (!preg_match('/^\d{2,10}$/', $cif)) {
        fail("CIF must be 2-10 digits (optionally prefixed with RO)");
    }
    return $cif;
}

/**
 * CIF and company name for create/authorize: the CIF must exist in the 'company'
 * core and not be 'inactiv', unless --force (then --company is required).
 */
function resolveCompany(string $solrBase, ?string $user, ?string $pass, array $args): array {
    $cif = normalizeCif((string)($args['cif'] ?? ''));
    $force = isset($args['force']);

    $company = solrRequest('GET', "$solrBase/company/get?id=" . rawurlencode($cif) . "&wt=json", $user, $pass)['doc'] ?? null;
    $companyName = $company ? (string)solrScalar($company['company'] ?? '') : '';
    if (!$company && !$force) {
        fail("CIF $cif not found in the 'company' core. Check the CIF, or use --force --company=\"Name\".");
    }
    if ($company && solrScalar($company['status'] ?? null) === 'inactiv' && !$force) {
        fail("Company $cif ($companyName) is marked 'inactiv'. Use --force anyway.");
    }
    if (is_string($args['company'] ?? null)) {
        $companyName = trim($args['company']);
    }
    if ($companyName === '') {
        fail("Company name unknown, pass --company=\"Name\"");
    }
    return [$cif, $companyName];
}

$args = parseArgs($argv);
$envFile = is_string($args['env'] ?? null) ? $args['env'] : __DIR__ . '/../../api.env';
if (!file_exists($envFile)) {
    fail("env file not found: $envFile");
}
loadEnv($envFile);

$server = trim(getenv('SOLR_SERVER') ?: '');
$user = trim(getenv('SOLR_USER') ?: '');
$pass = trim(getenv('SOLR_PASS') ?: '');
$protocol = trim(getenv('PROTOCOL') ?: '') ?: 'http';
if (!$server) {
    fail("SOLR_SERVER is not set in $envFile");
}
$solrBase = "$protocol://$server/solr";

try {
    switch ($args['_command']) {
        case 'create':
            [$cif, $companyName] = resolveCompany($solrBase, $user, $pass, $args);
            $active = array_filter(listApiKeys($solrBase, $user, $pass, $cif), 'isApiKeyDocUsable');
            if (count($active) >= MAX_ACTIVE_KEYS_PER_COMPANY) {
                fail("CIF $cif already has an active key (" . implode(', ', array_map(fn($d) => solrScalar($d['prefix'] ?? ''), $active)) . "). Revoke it first.");
            }
            $note = is_string($args['note'] ?? null) ? trim($args['note']) : null;
            $key = createApiKey($solrBase, $user, $pass, $cif, $companyName, 'cli', $note);

            echo "API key created for $companyName (CIF $cif)

";
            echo "  $key

";
            echo "Give it to the company over a private channel. It is shown only now;
";
            echo "to revoke it later use: --prefix=" . apiKeyDisplayPrefix($key) . "
";
            break;

        case 'list':
            $docs = listApiKeys($solrBase, $user, $pass, isset($args['cif']) ? normalizeCif((string)$args['cif']) : null);
            if (!$docs) {
                echo "No keys.
";
                break;
            }
            foreach ($docs as $d) {
                $state = isApiKeyDocUsable($d) ? 'active ' : 'revoked';
                printf("%-17s %s  %-10s %-40s %s  %-25s%s
",
                    solrScalar($d['prefix'] ?? ''), $state, solrScalar($d['cif'] ?? ''),
                    mb_substr((string)solrScalar($d['company'] ?? ''), 0, 40),
                    substr((string)solrScalar($d['created_at'] ?? ''), 0, 10),
                    solrScalar($d['created_by'] ?? ''),
                    isset($d['note']) ? '  ' . solrScalar($d['note']) : '');
            }
            break;

        case 'revoke':
            try {
                $doc = revokeApiKey($solrBase, $user, $pass, (string)($args['prefix'] ?? ''));
            } catch (InvalidArgumentException $e) {
                fail($e->getMessage() . " (see 'list')");
            }
            echo "Revoked {$doc['prefix']} (" . solrScalar($doc['company'] ?? '') . ", CIF " . solrScalar($doc['cif'] ?? '') . ")
";
            break;

        case 'authorize':
            $email = normalizePanelEmail((string)($args['email'] ?? '')) ?? fail("--email must be a valid email");
            [$cif, $companyName] = resolveCompany($solrBase, $user, $pass, $args);
            saveAuthorization($solrBase, $user, $pass, $email, $cif, $companyName, true);
            echo "$email can now log in to the panel for $companyName (CIF $cif)
";
            break;

        case 'deauthorize':
            $email = normalizePanelEmail((string)($args['email'] ?? '')) ?? fail("--email must be a valid email");
            $doc = solrRequest('GET', "$solrBase/" . API_KEY_CORE . "/get?id=" . rawurlencode($email) . "&wt=json", $user, $pass)['doc'] ?? null;
            if (!$doc || solrScalar($doc['type'] ?? null) !== 'authorization') {
                fail("$email is not authorized");
            }
            saveAuthorization($solrBase, $user, $pass, $email, (string)solrScalar($doc['cif']), (string)solrScalar($doc['company'] ?? ''), false);
            echo "$email can no longer log in to the panel. Its company's API keys stay active.
";
            break;

        case 'authorizations':
            $docs = listAuthorizations($solrBase, $user, $pass, isset($args['cif']) ? normalizeCif((string)$args['cif']) : null);
            if (!$docs) {
                echo "No authorizations.
";
                break;
            }
            foreach ($docs as $d) {
                printf("%-40s %s  %-10s %s
", solrScalar($d['email'] ?? ''),
                    isAuthorizationDocActive($d + ['type' => 'authorization']) ? 'active  ' : 'inactive',
                    solrScalar($d['cif'] ?? ''), solrScalar($d['company'] ?? ''));
            }
            break;

        default:
            fwrite(STDERR, "Usage: php devops/apikeys/manage.php create|list|revoke|authorize|deauthorize|authorizations [options]  (see the header of this file)\n");
            exit(1);
    }
} catch (Exception $e) {
    fail($e->getMessage());
}
