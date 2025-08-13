<?php
// Load ENV helper & actual env variables
require_once __DIR__ . '/../util/loadEnv.php';
loadEnv(__DIR__ . '/../api.env');

// Load CORS helper & set CORS headers
require_once __DIR__ . '/../util/cors.php';
setCORSHeaders();

// Load Auth helper & build global HTTP auth header
require_once __DIR__ . '/../util/auth.php';
$username = getenv('SOLR_USER');
$password = getenv('SOLR_PASS');
$GLOBALS['authHeader'] = getAuthHeader($username, $password);

// Load Solr helper & make credentials available globally
require_once __DIR__ . '/../util/solr.php';
$GLOBALS['solr'] = getSolrCredentials();

