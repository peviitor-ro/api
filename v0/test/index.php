<?php

function city_fix($in){
   return str_replace("Bucharest","București",$in);
   return str_replace("Brasov","Brașov",$in);
   

 }


echo city_fix($_GET('city'));

 ?>