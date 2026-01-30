<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config.php';
require_once '../utils/env.php';

/* =========================
   ENV
========================= */
loadEnv('../../api.env');

$server   = getenv('PROD_SERVER') ?: ($_SERVER['PROD_SERVER'] ?? null);
$username = getenv('SOLR_USER')   ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS')   ?: ($_SERVER['SOLR_PASS'] ?? null);

if (!$server) {
    echo json_encode(['total' => 0, 'companies' => []]);
    exit;
}

/* =========================
   HELPERS
========================= */
function file_get_contents_auth(string $url, ?string $user, ?string $pass) {
    $opts = [
        "http" => [
            "header" => "Authorization: Basic " . base64_encode("$user:$pass")
        ]
    ];
    return @file_get_contents($url, false, stream_context_create($opts));
}

function normalizeKey(string $value): string {
    $value = trim(mb_strtolower($value, 'UTF-8'));
    $map = [
        'ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ş'=>'s','ț'=>'t','ţ'=>'t',
        'Ă'=>'a','Â'=>'a','Î'=>'i','Ș'=>'s','Ş'=>'s','Ț'=>'t','Ţ'=>'t',
    ];
    return strtr($value, $map);
}

function solrRequest(string $server, string $core, string $qs, $user, $pass): array {
    $url = "http://{$server}/solr/{$core}/select{$qs}";
    $raw = @file_get_contents_auth($url, $user, $pass);

    if ($raw === false) {
        throw new Exception("Solr request failed: $core");
    }

    $json = json_decode($raw, true);
    if (!$json) {
        throw new Exception("Invalid JSON from Solr: $core");
    }

    return $json;
}

/* =========================
   MAIN LOGIC
========================= */
$result = new stdClass();
$result->total = 0;
$result->companies = [];

try {
    /* =========================
       1️⃣ LOGO MAP (core: auth)
    ========================= */
    $qs = '?' . http_build_query([
        'q'           => 'logo:*',
        'rows'        => 1000000,
        'omitHeader'  => 'true',
        'indent'      => 'true',
    ]);

    $data = solrRequest($server, 'auth', $qs, $username, $password);
    $docs = $data['response']['docs'] ?? [];

    $logos = [];
    foreach ($docs as $doc) {
        if (!isset($doc['id'], $doc['logo'][0])) continue;
        $key = normalizeKey($doc['id']);
        $logos[$key] = $doc['logo'][0];
    }

    /* =========================
       2️⃣ COMPANIES (core: jobs)
    ========================= */
    $qs = '?' . http_build_query([
        'facet'        => 'true',
        'facet.field'  => 'company_str',
        'facet.limit'  => 10000,
        'facet.sort'   => 'index',
        'q'            => '*:*',
        'rows'         => 0,
        'indent'       => 'true',
    ]);

    $data = solrRequest($server, 'jobs', $qs, $username, $password);
    $companies = $data['facet_counts']['facet_fields']['company_str'] ?? [];

    if (is_array($companies) && count($companies) > 0) {
        $result->total = count($companies)/2;
        foreach ($companies as $i => $value) {
            if ($i % 2 !== 0) continue;
            $name = $value;
            $count = $companies[$i+1] ?? 0;

            $obj = new stdClass();
            $obj->name = $name;
            if (($_GET['count'] ?? null) === 'true') {
                $obj->jobs = $count;
            }
            $obj->logo = $logos[normalizeKey($name)] ?? null;

            $result->companies[] = $obj;
        }
    }

} catch (Exception $e) {
    $result->total = 0;
    $result->companies = [];
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);