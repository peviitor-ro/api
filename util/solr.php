<?php
function getSolrCredentials($env) {
    if ($env == 'PROD')
    {
        return [
        'server'   => getenv('PROD_SERVER') ?: ($_SERVER['PROD_SERVER'] ?? null),
        'username' => getenv('SOLR_USER')    ?: ($_SERVER['SOLR_USER'] ?? null),
        'password' => getenv('SOLR_PASS')    ?: ($_SERVER['SOLR_PASS'] ?? null),
    ];
    }
    if ($env == 'LOCAL')
    {
    return [
        'server'   => getenv('LOCAL_SERVER') ?: ($_SERVER['LOCAL_SERVER'] ?? null),
        'username' => getenv('SOLR_USER')    ?: ($_SERVER['SOLR_USER'] ?? null),
        'password' => getenv('SOLR_PASS')    ?: ($_SERVER['SOLR_PASS'] ?? null),
    ];
}
}
