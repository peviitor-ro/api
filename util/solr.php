<?php
function getSolrCredentials() {
    return [
        'server'   => getenv('LOCAL_SERVER') ?: ($_SERVER['LOCAL_SERVER'] ?? null),
        'username' => getenv('SOLR_USER')    ?: ($_SERVER['SOLR_USER'] ?? null),
        'password' => getenv('SOLR_PASS')    ?: ($_SERVER['SOLR_PASS'] ?? null),
    ];
}
