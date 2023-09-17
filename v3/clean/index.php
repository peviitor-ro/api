<?php
header("Access-Control-Allow-Origin: *");

    function get_server(){
        //get the IP of the server
        //we need a config file to know where is the SOLR
        require('../../_config/index.php');
        return $server;
    }
 

 function validate_api_key($key) {
    $method = 'GET';
    $server = get_server();
    $core  = 'auth';
    $command ='/select';
    $qs = '?q.op=OR&q=apikey%3A"'.$key.'"&rows=0';
    $url =  $server[0].$core.$command.$qs;
   
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
    $json = json_decode($result);

     $y = $json->response->numFound; 
    if ($y==1) {$x = true;}
    if ($y==0) {$x = false;}
  return $x;
 }

 function get_company($token) {
     //go to database
     //get the company code based on $token



     $method = 'GET';
     $server = get_server();
     $core  = 'auth';
     $command ='/select';
     $qs = '?q.op=OR&q=apikey%3A"'.$token.'"%26rows%3D1';
     $url =  $server[0].$core.$command.$qs;
    
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
      $json = json_decode($result);
    
    $x=$json->response->docs[0]->company[0];
    return $x;
 }


 function clean($xcompany) {
   
    $method = 'POST';
    $server = get_server();
    $core  = 'jobs';
    $command ='/update';
    $qs = '?_=1617366504771&commitWithin=1000&overwrite=true&wt=json';
    $url =  $server[0].$core.$command.$qs;
    $data = "{'delete': {'query': 'company:";
        $data.=$xcompany;
    $data.="'}}";

    //echo $data;
    $url = $server[0].$core.$command.$qs;

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => $data
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { /* Handle error */ }

      $url = $server[1].$core.$command.$qs;
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { /* Handle error */ }
  
    var_dump($result);
 }
 // endpoint starts here
    foreach (getallheaders() as $name => $value) {
        if (($name=='apikey'))        {	
          if (validate_api_key($value)==true)
              {     
                    $company = get_company($value);
                    clean($company);
              } else {echo "apikey error";}
                                      }
    } 
?>
