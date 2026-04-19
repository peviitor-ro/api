<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

$PROD_SERVER = trim(getenv('PROD_SERVER') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Only GET method allowed"]);
    exit;
}

function fetchJson(string $url, ?string $user = null, ?string $pass = null, int $timeout = 5): array {
    $headers = [];
    if ($user && $pass) {
        $headers[] = "Authorization: Basic " . base64_encode("$user:$pass");
    }
    $context = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => implode("\r\n", $headers),
            'timeout' => $timeout
        ]
    ]);
    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        $err = error_get_last()['message'] ?? 'Unknown error';
        throw new Exception("FETCH FAILED: $url | $err");
    }
    $json = json_decode($data, true);
    if (!is_array($json)) {
        throw new Exception("Invalid JSON response");
    }
    return $json;
}

function postJson(string $url, array $data, ?string $user = null, ?string $pass = null, int $timeout = 10): array {
    $headers = ['Content-Type: application/json'];
    if ($user && $pass) {
        $headers[] = "Authorization: Basic " . base64_encode("$user:$pass");
    }
    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", $headers),
            'content' => json_encode($data),
            'timeout' => $timeout
        ]
    ]);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        $err = error_get_last()['message'] ?? 'Unknown error';
        throw new Exception("POST FAILED: $url | $err");
    }
    $json = json_decode($response, true);
    if (!is_array($json)) {
        throw new Exception("Invalid JSON response");
    }
    return $json;
}

function fetchFromDemoAnaf(string $cif): array {
    $url = "https://demoanaf.ro/api/company/" . rawurlencode($cif);
    $data = fetchJson($url, null, null, 5);
    if (empty($data['success']) || empty($data['data'])) {
        throw new Exception("DemoANAF returned no data for CIF: $cif");
    }
    return $data['data'];
}

function mapDemoAnafToCompany(array $anafData): array {
    $company = [
        'id' => $anafData['cui'] ?? '',
        'company' => $anafData['name'] ?? '',
        'registrationNumber' => $anafData['registrationNumber'] ?? '',
        'registrationDate' => $anafData['registrationDate'] ?? '',
        'legalForm' => $anafData['legalForm'] ?? ''
    ];

    $address = $anafData['address'] ?? '';
    $headquarters = $anafData['headquartersAddress'] ?? [];
    if (!empty($headquarters)) {
        $parts = array_filter([
            $headquarters['street'] ?? '',
            $headquarters['number'] ?? '',
            $headquarters['locality'] ?? '',
            $headquarters['county'] ?? ''
        ]);
        $address = implode(', ', $parts);
    }
    if (!empty($address)) {
        $company['address'] = $address;
    }

    $onrcStatus = $anafData['onrcStatus'] ?? null;
    $onrcStatusLabel = $anafData['onrcStatusLabel'] ?? '';
    if (!empty($onrcStatusLabel)) {
        $company['status'] = strtolower($onrcStatusLabel) === 'funcțiune' ? 'activ' : 'inactiv';
    }

    $website = $anafData['website'] ?? [];
    if (!empty($website)) {
        $company['website'] = is_array($website) ? $website : [$website];
    }

    $caenCodes = $anafData['authorizedCaenCodes'] ?? [];
    if (!empty($caenCodes)) {
        $company['caen'] = is_array($caenCodes) ? $caenCodes : [$caenCodes];
    }

    return $company;
}

function updateCompanyInSolr(string $cif, array $companyData, string $prodServer, ?string $solrUser, ?string $solrPass): void {
    $url = "http://$prodServer/solr/company/update?commit=true";

    $doc = ['id' => $cif];
    foreach ($companyData as $field => $value) {
        $doc[$field] = $value;
    }

    postJson($url, [$doc], $solrUser, $solrPass, 10);
}

try {
    if (!$PROD_SERVER) {
        throw new Exception("PROD_SERVER not set");
    }

    $cif = $_GET['cif'] ?? '';
    $name = $_GET['name'] ?? '';
    $page = isset($_GET['page']) ? max(0, (int)$_GET['page']) : 0;
    $rows = isset($_GET['rows']) ? min(100, max(1, (int)$_GET['rows'])) : 100;

    if (empty($cif) && empty($name)) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required field: cif or name"]);
        exit;
    }

    $core = 'company';
    $base = "http://$PROD_SERVER/solr/$core/select";

    if (!empty($name)) {
        $qs = http_build_query([
            "q" => "company:*" . rawurlencode($name) . "*",
            "start" => $page * $rows,
            "rows" => $rows,
            "indent" => "true"
        ]);
    } else {
        $qs = http_build_query([
            "q" => "id:" . rawurlencode($cif),
            "rows" => 1,
            "indent" => "true"
        ]);
    }

    $url = "$base?$qs";
    error_log("COMPANY URL: $url");

    $solr = fetchJson($url, $SOLR_USER, $SOLR_PASS, 4);

    $numFound = $solr['response']['numFound'] ?? 0;
    $needsUpdate = false;
    $updateSource = null;

    if ($numFound > 0 && !empty($cif)) {
        $doc = $solr['response']['docs'][0] ?? [];
        $doc = array_map(function($value) {
            if ($value === '-' || $value === '' || $value === null) {
                return '';
            }
            return $value;
        }, $doc);

        if (empty($doc['id']) || empty($doc['company'])) {
            $needsUpdate = true;
            $updateSource = 'demoanaf';
        }
    } else {
        $needsUpdate = true;
        $updateSource = $numFound === 0 ? 'demoanaf_new' : 'demoanaf';
    }

    if ($needsUpdate && !empty($cif) && $updateSource) {
        try {
            $anafData = fetchFromDemoAnaf($cif);
            $companyData = mapDemoAnafToCompany($anafData);
            updateCompanyInSolr($cif, $companyData, $PROD_SERVER, $SOLR_USER, $SOLR_PASS);

            $doc = ['id' => $cif] + $companyData;
            error_log("COMPANY UPDATED from DemoANAF: $cif");

            echo json_encode([
                'company' => $doc,
                '_updateSource' => $updateSource
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Exception $e) {
            error_log("DemoANAF fetch failed: " . $e->getMessage());
            if ($numFound === 0) {
                http_response_code(404);
                echo json_encode(["error" => "Company not found"]);
                exit;
            }
        }
    }

    if (!empty($name)) {
        $docs = $solr['response']['docs'] ?? [];
        $docs = array_map(function($doc) {
            return array_map(function($value) {
                if ($value === '-' || $value === '' || $value === null) {
                    return '';
                }
                return $value;
            }, $doc);
        }, $docs);

        echo json_encode([
            'total' => $numFound,
            'page' => $page,
            'rows' => $rows,
            'companies' => $docs
        ], JSON_UNESCAPED_UNICODE);
    } else {
        $doc = $solr['response']['docs'][0] ?? [];
        $doc = array_map(function($value) {
            if ($value === '-' || $value === '' || $value === null) {
                return '';
            }
            return $value;
        }, $doc);

        echo json_encode([
            'company' => $doc
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    error_log("COMPANY FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Company core unavailable',
        'details' => $e->getMessage()
    ]);
}
