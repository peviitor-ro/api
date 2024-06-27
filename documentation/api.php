<?php
require($_SERVER['DOCUMENT_ROOT'].'/apipeviitorro/vendor/autoload.php');
$openapi = \OpenApi\scan($_SERVER['DOCUMENT_ROOT'].'/apipeviitorro/v1/');
header('Content-Type: application/json');
echo $openapi->toJSON();

