<?php
header("Access-Control-Allow-Origin: *");

    function get_server(){
        //get the IP of the server
        //we need a config file to know where is the SOLR
        require('../../_config/index.php');
        return $server;
    }

function company_exist($company) {
     

    $url = 'https://api.peviitor.ro/v0/search/?https://solr.peviitor.ro/solr/jobs/select?indent=true&q.op=OR&q=company%3A%22'.$company.'%22&rows=0&useParams=';
    $string = file_get_contents($url);
    $json = json_decode($string, true);
    
    if ($json['response']['numFound']==0) {echo "new";}
    if ($json['response']['numFound']!=0) {echo "existing";}

    return $json['response']['numFound']




}

echo company_exist('endava');

?>
