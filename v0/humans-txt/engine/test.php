<?php
$url = 'https://humanstxt.org/humans.txt';
$headers =[];


$ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
     if ($httpCode == 200) {
        echo $response;
     }

     else {

    if (strpos($response, 'humans.txt') !== false) {
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
    $response = curl_exec($ch);
    
    $lastEffectiveURL = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
   echo $httpCode;
  
    if ($httpCode == 404) {
        http_response_code(404);
        echo 'The requested URL returned a 404 error.';
    } elseif (strpos($response, 'humans.txt') !== false) {
        echo $response;
    } else {
        http_response_code(404);
        echo "The redirection did not lead to a 'humans.txt' URL.";
    }}

     }
    curl_close($ch);
?>

