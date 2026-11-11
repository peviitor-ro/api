<?php
require_once __DIR__ . '/http.php';

function getAllEmails(): array {
    $server   = trim(getenv('SOLR_SERVER') ?: '');
    $user     = trim(getenv('SOLR_USER') ?: '');
    $pass     = trim(getenv('SOLR_PASS') ?: '');
    $protocol = trim(getenv('PROTOCOL') ?: '');
    $base     = "$protocol://$server/solr/company/select";

    $rows = 500; $start = 0; $emails = [];
    do {
        $qs = http_build_query([
            "q" => "*:*", "fq" => "email:[* TO *]", "fl" => "email",
            "rows" => $rows, "start" => $start, "sort" => "id asc", "wt" => "json"
        ]);
        $solr     = fetchJson("$base?$qs", $user, $pass, 10);
        $numFound = (int)($solr['response']['numFound'] ?? 0);
        $docs     = $solr['response']['docs'] ?? [];

        foreach ($docs as $doc) {
            foreach ((array)($doc['email'] ?? []) as $e) {
                $e = trim($e);
                if ($e !== '') $emails[] = $e;
            }
        }
        $start += $rows;
    } while ($start < $numFound && !empty($docs));

    return array_values(array_unique($emails));
}