co
<?php
header("Access-Control-Allow-Origin: *");

    function get_server(){
        //get the IP of the server
        //we need a config file to know where is the SOLR
        require('../../_config/index.php');
        return $server;
    }

function company_exist($company) {
     
 $method = 'GET';
    $server = get_server();
    $core  = 'jobs';
    $command ='/select';
     
    $qs = '?indent=true&q.op=OR&q=company%3A%22'.$company.'%22&useParams=&rows=0';
    $url =  $server.$core.$command.$qs;
   
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'GET',
            'content' => $data
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { /* Handle error */ }
    $json = json_encode($result);
   var_dump($json);
     $y = $json->response->numFound; 
  
   if ($json->response->numFound>0) {return "existing";} else {return "new";}    
 }

company_exist('endava');

?>
