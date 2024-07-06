<?php
header("Access-Control-Allow-Origin: *");

/**
 * @OA\Post(
 *     path="/v0/logo/add/",
 *     tags={"logo"},
 *     summary="Add new logos",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="string", example="Nova"),
 *                     @OA\Property(property="logo", type="string", example="https://e-infra.ro/wp-content/uploads/2023/02/logotype-nova.jpg")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response="200",
 *         description="Success"
 *     )
 * )
 */

$method = 'POST';
$server = '172.18.0.10:8983';
$core  = 'auth';
$command = '/update';

$qs = '?';
$qs = $qs . '_=1617366504771';
$qs = $qs . '&';
$qs = $qs . 'commitWithin=1000';
$qs = $qs . '&';
$qs = $qs . 'overwrite=true';
$qs = $qs . '&';
$qs = $qs . 'wt=json';

$url = 'http://' . $server . $core . $command . $qs;

$data = file_get_contents('php://input');

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    )
);

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo $result;
}