<?php
header("Access-Control-Allow-Origin: *");
/**
 * @OA\Get(
 *     path="/v1/jobs/",
 *     tags={"machine learning"},
 *     operationId="getJobs",
 *     @OA\Parameter(
 *         in="query",
 *         name="start",
 *         @OA\Schema(type="string"),
 *         example="100"
 *     ),
 *     @OA\Response(
 *         response="200",
 *         description="Success"
 *     )
 * )
 */

if (isset($_GET["start"])) {
    $start = $_GET["start"];
    $qs .= "&start=" . $start;
}

$server = 'zimbor.go.ro:8985';
$core = "jobs";

$qs = '?';
$qs = $qs . 'facet=true';
$qs = $qs . '&';
$qs = $qs . 'indent=true';
$qs = $qs . '&';
$qs = $qs . 'q.op=OR';
$qs = $qs . '&';
$qs = $qs . 'q=*%3A*';
$qs = $qs . '&';
$qs = $qs . 'rows=100';
$qs = $qs . '&';
$qs = $qs . 'omitHeader=true';
$qs = $qs . '&';
$qs = $qs . 'useParams=';

$url = 'http://' . $server . '/solr/' . $core . '/select'. $qs;

$json = file_get_contents($url);
echo $json;