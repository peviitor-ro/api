<?php
header("Access-Control-Allow-Origin: *");
    /**
     * 
     * @OA\Post(
     *     path="/v1/clean/", 
     *     tags={"scraper"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="company",
     *                     type="string"
     *                 ),
     *                 example="company=Endava"
     *                      )
     *                  )
     *          ),
    
     *     @OA\Response(response="200", description="Success")
     * )
     */



     function get_server(){
        //get the IP of the server
        //we need a config file to know where is the SOLR
        require('../../_config/index.php');
        return $server;
    }


    function company_exist($company) {
        $url = 'https://api.peviitor.ro/v0/search/?https://solr.peviitor.ro/solr/shaqodoon/select?indent=true&q.op=OR&q=company%3A%22'.$company.'%22&rows=0&useParams=';
        $string = file_get_contents($url);
        $json = json_decode($string, true);
        
        if ($json['response']['numFound']==0) {return "new";}
        if ($json['response']['numFound']!=0) {return "existing";}
    
         }

    function discord_webhook($msg) {
       
            $msg .= ' CLEAN in TEST at '. date("l d-m-Y H:i:s"); 
            $method = 'POST';
            $url = "https://discord.com/api/webhooks/1127592366614786118/ZOcdq94sqxO4P8iOIkQdRLG9s_vwgRfg1DFxhybwpHkqyet0QTe33rQ7bSDS5AG5HP8n";
            $data = '{"content": "'.$msg.'"}';
        
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
           
    }         

$method = 'POST';


$server = get_server();;
$core  = 'shaqodoon';
$command ='/update';
$qs = '?_=1617366504771&commitWithin=1000&overwrite=true&wt=json';
$company='xtest';

$company = $_POST['company'];

$url =  $server.$core.$command.$qs;
 
$data = "{'delete': {'query': 'company:".$company."'}}";





$url = $server.$core.$command.$qs;



$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    )
);
$msg='';
    if (company_exist($company)=="new") {$msg.="!!! COMPLETELY NEW  !!!  ";}
    $msg .= $company;
    discord_webhook($msg);
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
if ($result === FALSE) { /* Handle error */ }

print_r($result);
?>