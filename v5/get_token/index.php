<?php
require_once __DIR__ . '/../validator/validator.php';
$server = 'https://api.laurentiumarian.ro/get_token';

$body = file_get_contents('php://input');

$validator = new Validator($server);
$obj = $validator->post($body);

$response = $obj['response'];
$httpCode = $obj['httpCode'];

http_response_code($httpCode);
echo $response;
?>