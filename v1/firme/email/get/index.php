<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../../util/loadEnv.php';
require_once __DIR__ . '/../../../../util/http.php';
require_once __DIR__ . '/../../../../util/getEmails.php';

loadEnv(__DIR__ . '/../../../../api.env');

$emails = [];

$emails = getAllEmails();

echo json_encode([
    'total' => count($emails),
    'emails' => $emails
], JSON_UNESCAPED_UNICODE);
