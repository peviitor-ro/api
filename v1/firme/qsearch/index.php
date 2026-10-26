<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode(['error' => 'Only GET method is allowed']);
    exit;
}
require_once __DIR__ . '/lib/search.php';
require_once __DIR__ . '/../../../util/loadEnv.php';
try {
    $input = \Peviitor\CompanySearch\validateInput($_GET);
    loadEnv(__DIR__ . '/../../../api.env');
    $server = trim(getenv('SOLR_SERVER') ?: '');
    $protocol = trim(getenv('PROTOCOL') ?: 'http');
    if ($server === '' || !in_array($protocol, ['http', 'https'], true)) {
        throw new \Peviitor\CompanySearch\SearchFailure('Company core is not configured', 503);
    }
    $http = new \Peviitor\CompanySearch\JsonHttp();
    $repository = new \Peviitor\CompanySearch\CompanyRepository(
        $http, "$protocol://$server/solr/company", getenv('SOLR_USER') ?: '', getenv('SOLR_PASS') ?: ''
    );
    $client = new \Peviitor\CompanySearch\CuiscanClient($http, getenv('CUISCAN_API_URL') ?: 'https://cuiscan.ro/api.php');
    echo json_encode(\Peviitor\CompanySearch\searchAndImport($input, $client, $repository), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (\Peviitor\CompanySearch\SearchFailure $e) {
    http_response_code($e->getCode());
    echo json_encode(['error' => $e->getMessage(), 'import' => $e->import]);
} catch (Throwable $e) {
    error_log('Company qsearch failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Company search failed']);
}
