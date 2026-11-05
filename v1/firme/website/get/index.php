<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode(["error" => "Only GET method allowed"]);
    exit;
}

require_once __DIR__ . '/../../../../util/loadEnv.php';
require_once __DIR__ . '/../../../../util/http.php';

try {
    $envFile = __DIR__ . '/../../../../api.env';
    if (!is_file($envFile)) {
        throw new Exception("The api.env file does not exist");
    }
    loadEnv($envFile);

    $SOLR_SERVER = trim(getenv('SOLR_SERVER') ?: '');
    $PROTOCOL = trim(getenv('PROTOCOL') ?: '');
    $SOLR_USER = trim(getenv('SOLR_USER') ?: '');
    $SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');

    if (!$SOLR_SERVER || !in_array($PROTOCOL, ['http', 'https'], true)) {
        throw new Exception("Solr configuration is missing or invalid");
    }

    $companies = [];
    $cursorMark = '*';
    do {
        $url = "$PROTOCOL://$SOLR_SERVER/solr/company/select?" . http_build_query([
            // The company schema uses the multi-valued website field and unique key id.
            'q' => 'website:[* TO *]',
            'fl' => '*',
            'sort' => 'id asc',
            'rows' => 1000,
            'cursorMark' => $cursorMark,
            'wt' => 'json'
        ]);
        $response = fetchJson($url, $SOLR_USER, $SOLR_PASS);

        if (isset($response['error']) ||
            ($response['responseHeader']['status'] ?? 0) !== 0 ||
            !in_array($response['responseHeader']['partialResults'] ?? false, [false, 'false'], true) ||
            !isset($response['response']['docs'], $response['nextCursorMark']) ||
            !is_array($response['response']['docs']) ||
            !is_string($response['nextCursorMark'])) {
            throw new Exception("Invalid or incomplete Solr response");
        }

        foreach ($response['response']['docs'] as $doc) {
            $websites = $doc['website'] ?? [];
            $websites = is_array($websites) ? $websites : [$websites];
            foreach ($websites as $website) {
                // Match company/add URL validation; field existence alone permits bad values.
                if (is_string($website) && trim($website) !== '' &&
                    filter_var($website, FILTER_VALIDATE_URL) !== false) {
                    $companies[] = $doc;
                    break;
                }
            }
        }

        $nextCursorMark = $response['nextCursorMark'];
        if ($nextCursorMark === $cursorMark) {
            break;
        }
        $cursorMark = $nextCursorMark;
    } while (true);

    // Encode before writing so a later-page failure never returns a partial success.
    echo json_encode($companies, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    error_log("FIRME WEBSITE GET FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode(['error' => 'Company core unavailable']);
}
