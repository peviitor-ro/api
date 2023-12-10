<?php



function callSOLR($solrServer,$coreName, $qs) {
	$solrEndpoint = $solrServer . '/' . $coreName . '/select';
	$filterQuery = $qs;
	
	 // Construirea URL-ului final pentru apelul către Solr
	$solrUrl = $solrEndpoint . '?' . $qs;
	echo "   ". $solrUrl;
	$solrResponse = file_get_contents($solrUrl);

    // Procesarea răspunsului JSON de la Solr
    $result = json_decode($solrResponse, true);

    // Extrage job-urile din răspunsul Solr
    $jobs = [];
    if (isset($result['response']['docs'])) {
        $jobs = $result['response']['docs'];
    }

    return $jobs;
}




function getJobsByJobLinksAndCompany($jobLinks, $query, $filterQuery) {
 
 // Configurarea detaliilor despre serverul Solr
    $solrServer = 'https://solr.peviitor.ro/solr'; 
    $coreName = 'jobs'; 
    $qs =  $query . '&' . $filterQuery. '&' .'fl=job_link';
   
   
    
    // Realizarea apelului către Solr
    $solrResponse = callSOLR($solrServer,$coreName, $qs);
    // Extrage doar job_link-urile din răspunsul Solr
    $toKeep =  array_map(function ($job) {  return $job['job_link']; }, $solrResponse);
 // Afiseaza rezultatele
 
echo "<br>";
echo "<br>";
 var_dump($toKeep);
	
	// Obține job-urile de la Solr
	$qs = 'q=*:*&'.$filterQuery. '&' .'fl=job_link';
    $jobsFromSolr = callSOLR($solrServer,$coreName, $qs);
	
	// Extrage doar job_link-urile din răspunsul Solr
      $solrJobLinks = array_map(function ($job) {  return $job['job_link']; }, $jobsFromSolr);
  


// Extrage link-urile din $toKeep
$jobLinksToKeep = array_map(function ($item) {
    return $item[0];
}, $toKeep);

// Extrage link-urile din $solrJobLinks
$solrJobLinksArray = array_map(function ($item) {
    return $item[0];
}, $solrJobLinks);

// Găsește link-urile care sunt în $solrJobLinksArray, dar nu sunt în $jobLinksToKeep
$toDelete = array_diff($solrJobLinksArray, $jobLinksToKeep);

// Numărul de elemente lipsă
$countMissingJobLinks = count($toDelete);


echo "<br>";
echo "<br>";
echo "\nNumărul de elemente care sunt în \$solrJobLinks și nu sunt în \$toKeep: $countMissingJobLinks\n";


// Afisează link-urile lipsă
var_dump($toDelete);

// Găsește link-urile care sunt în $jobLinksToCheck, dar nu sunt în $toKeep
$toInsert = array_diff($jobLinksToCheck, $jobLinksToKeep);

echo "<br>";
echo "<br>";
var_dump($toInsert);

}




//AICI incepe CODUL
// JSON primit prin POST
$payload = file_get_contents('php://input');
// Decodifică JSON-ul într-un array asociativ
$jobsArray = json_decode($payload, true);


// Extrage toate link-urile de job din $jobsArray pentru $jobLinksToCheck
$jobLinksToCheck = array_map(function ($job) { return $job['job_link'];}, $jobsArray);



// Extrage informația despre companie doar din primul element al $jobsArray
$companyToFilter = isset($jobsArray[0]['company']) ? $jobsArray[0]['company'] : null;


 // Construirea query-ului Solr
    //$query = 'q=job_link:(' . implode(' OR ', array_map('urlencode', $jobLinksToCheck)) . ')';
	$query = 'q=job_link:("' . implode('" OR "', array_map('urlencode', $jobLinksToCheck)) . '")';

    $filterQuery = 'fq=company:' . urlencode($companyToFilter);
   
   getJobsByJobLinksAndCompany($jobLinksToCheck,$query,$filterQuery);

?>
