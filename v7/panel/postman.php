<?php
/**
 * /v7/panel/postman.php - downloads a minimal Postman collection with the request
 * examples (GET/POST/PUT/DELETE, no tests). Logged-in companies only. It never
 * contains a key: the company fills in the apiKey variable itself.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../lib/examples.php';

try {
    $user = panelCurrentUser($SOLR_BASE, $SOLR_USER, $SOLR_PASS);
} catch (Exception $ex) {
    solrUnavailable($ex);
}
if (!$user) {
    redirect('./');
}

header('Content-Type: application/json; charset=UTF-8');
header('Content-Disposition: attachment; filename="peviitor-joburi.postman_collection.json"');
echo json_encode(examplesPostmanCollection(panelJobsUrl(), $user['company']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
