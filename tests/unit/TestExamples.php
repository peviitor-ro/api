<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../v7/lib/examples.php';
registerTestFile(__FILE__);

const JOBS_URL = 'https://api.peviitor.ro/v7/jobs/';

test("there is one example per method: GET, POST, PUT, DELETE", function() {
    assertEqual(['GET', 'POST', 'PUT', 'DELETE'], array_column(companyApiExamples(), 'method'));
});

test("curl examples read the key from PEVIITOR_API_KEY and contain no key", function() {
    foreach (companyApiExamples() as $ex) {
        $curl = exampleCurl($ex, JOBS_URL);
        assertTrue(str_contains($curl, '-H "X-Api-Key: $PEVIITOR_API_KEY"'), $curl);
        assertTrue(!str_contains($curl, 'pv_live_'), "key in example: $curl");
        assertTrue(str_contains($curl, JOBS_URL), $curl);
    }
});

test("curl bodies are valid JSON; DELETE sends the url in the body", function() {
    foreach (companyApiExamples() as $ex) {
        $curl = exampleCurl($ex, JOBS_URL);
        if ($ex['body'] !== null) {
            assertTrue(preg_match("/-d '(.*)'$/", $curl, $m) === 1, $curl);
            assertEqual($ex['body'], json_decode($m[1], true));
        }
    }
    $delete = exampleCurl(companyApiExamples()[3], JOBS_URL);
    assertTrue(str_starts_with($delete, 'curl -X DELETE "' . JOBS_URL . '"'), $delete);
    assertTrue(str_contains($delete, "-d '{\"url\":\"https://firma-ta.ro/cariere/java-developer\"}'"), $delete);
});

test("Postman collection: 4 requests, no tests, empty apiKey sent as X-Api-Key", function() {
    $c = examplesPostmanCollection(JOBS_URL, 'FIRMA SRL');
    assertEqual(4, count($c['item']));
    foreach ($c['item'] as $item) {
        assertTrue(!isset($item['event']), "request has scripts: {$item['name']}");
        assertEqual('{{baseUrl}}', $item['request']['url']['host'][0]);
    }
    $vars = array_column($c['variable'], 'value', 'key');
    assertEqual(JOBS_URL, $vars['baseUrl']);
    assertEqual('', $vars['apiKey']);
    $auth = array_column($c['auth']['apikey'], 'value', 'key');
    assertEqual(['key' => 'X-Api-Key', 'value' => '{{apiKey}}', 'in' => 'header'], $auth);
    assertTrue(!str_contains(json_encode($c), 'pv_live_'));
});

finish();
