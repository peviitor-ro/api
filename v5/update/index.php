<?php
header("Access-Control-Allow-Origin: *");


  


    $method = 'POST';
    $server = 'http://peviitor.go.ro/solr/';
    $core  = 'jobs';
    $command ='/update';
    $qs = '?_=1617366504771&commitWithin=1000&overwrite=true&wt=json';
    
    
     
    $data = file_get_contents('php://input');
    
    $json = json_decode($data);
  
    foreach ($json as $item) {
        
        $item->job_title  = html_entity_decode($item->job_title);
        $item->country    = str_replace("Romania","România",$item->country);
	$item->city    = str_replace("Cluj Napoca","Cluj-Napoca",$item->city);
	$item->city    = str_replace("Bucharest","București",$item->city);
	$item->city    = str_replace("Bucuresti","București",$item->city);
        $item->id = md5($item->job_link)."";
        $company = $item->company; 
    }
    

    $data = json_encode($json);
    
  
    
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => $data
        )
    );
    
    $context  = stream_context_create($options);
     
  
     $url = $server.$core.$command.$qs;
     $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { /* Handle error */ }
     
         

 



?>
