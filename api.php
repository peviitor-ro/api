<?php
require($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');
$openapi = \OpenApi\scan($_SERVER['DOCUMENT_ROOT'].'/v1/');
header('Content-Type: application/json');
echo $openapi->toJSON();

?>