<?php

function city_fix($in){
   return str_replace("Bucharest","București",$in);
   return str_replace("Brasov","Brașov",$in);
   

 }

 $oras = $_GET["city"];
echo city_fix($oras);

 ?>