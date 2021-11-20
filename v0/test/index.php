<?php

function city_fix($in){
   $output =  return str_replace("Bucharest","București",$in);
   $output =  str_replace("Brasov","Brașov",$in);
   
  return $output;
 }

 $oras = $_GET["city"];
echo city_fix($oras);

 ?>