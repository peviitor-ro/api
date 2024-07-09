<?php
header("Access-Control-Allow-Origin: *");

/**
 * @OA\Post(
 *     path="/v1/logo/delete/",
 *     tags={"logo"},
 *     summary="Delete a logo by company name",
 *     operationId="deleteLogoByCompany",
 *     @OA\Parameter(
 *         name="company",
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

$method = 'POST';
$server = 'zimbor.go.ro:8985';
$core  = 'auth';
$command ='/update';

$qs = '?';
$qs = $qs . '_=1617366504771';
$qs = $qs . '&';
$qs = $qs . 'commitWithin=1000';
$qs = $qs . '&';
$qs = $qs . 'overwrite=true';
$qs = $qs . '&';
$qs = $qs . 'wt=json';

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;
 
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

$result = file_get_contents($url, false, $context);
if ($result === FALSE) { echo $result; }

?>