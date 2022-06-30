<?php
header("Access-Control-Allow-Origin: *");

$url = 'https://api.peviitor.ro/v0/search/?facet.field=company&facet=on&q=*%3A*&rows=0';
$string = file_get_contents($url);
$json = json_decode($string, true);

$companies = $json['facet_counts']['facet_fields']['company'];



$obj = new stdClass();
$obj->total = new stdClass();
$obj->total -> jobs = ''.$json['response']['numFound'];
$obj->total -> companies = ''.count($companies)/2;

echo json_encode($obj);


?>