<?php
header("Access-Control-Allow-Origin: *");
/**
 * @OA\Post(
 *     path="/v1/logo/{companyName}/delete",
 *     tags={"logo"},
 *     summary="Delete a logo by company name",
 *     @OA\Parameter(
 *         name="companyName",
 *         in="body",
 *         required=true,
 *         description="The name of the company whose logo will be deleted",
 *         example="ziramarketing",
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
 *     @OA\Response(
 *         response="200",
 *         description="Successful operation. The logo for the specified company has been deleted."
 *     ),
 *     @OA\Response(
 *         response="404",
 *         description="Company not found. The specified company name does not exist."
 *     ),
 *     @OA\Response(
 *         response="400",
 *         description="Bad request. The request was malformed or missing required parameters."
 *     )
 * )
 */

 function get_server(){
    //get the IP of the server
    //we need a config file to know where is the SOLR
    require('../../../_config/index.php');
    return $server;
}

$method = 'POST';
$server = get_server();
$core  = 'auth';
$command ='/update';
$qs = '?_=1617366504771&commitWithin=1000&overwrite=true&wt=json';


 
$company = $_POST['company'];
$data = "{'delete': {'query': 'id:".$company."'}}";

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    )
);
$context  = stream_context_create($options);

foreach ($server as $solrurl) {
$url =  $solrurl.$core.$command.$qs;
$result = file_get_contents($url, false, $context);
if ($result === FALSE) { echo $result; }
}

?>
