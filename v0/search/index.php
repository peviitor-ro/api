<?php
header("Access-Control-Allow-Origin: *");
$qs = $_SERVER['QUERY_STRING'];



    function get_master_server(){
      // File path
        $filePath = "../server/test/server.txt";

        // Read the contents of the file into a string
           $fileContents = file_get_contents($filePath);

            if ($fileContents !== false) {$server = $fileContents;
                     } else {  $server = 'https://solr.peviitor.ro/solr/';
                             }
                       return $server;
                                }



$core ="jobs";
$url =  'http://172.18.0.10:8983/solr/'.$core.'/select?'.$qs;
$json = file_get_contents($url);
echo $json;
?>
