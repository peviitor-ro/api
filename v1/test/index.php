<?php



function callSOLR($solrServer,$coreName, $qs) {
	$solrEndpoint = $solrServer . '/' . $coreName . '/select';
	$filterQuery = $qs;
	
	 // Construirea URL-ului final pentru apelul către Solr
	$solrUrl = $solrEndpoint . '?' . $qs;
	
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


function findExcessJobs($inputJobLinks, $solrJobLinks) {
    // Găsește job-urile care sunt în solrJobLinks, dar nu sunt în inputJobLinks
    $excessJobs = array_diff($solrJobLinks, $inputJobLinks);

    return $excessJobs;
}


function getJobsByJobLinksAndCompany($jobLinks, $query, $filterQuery) {
 
 // Configurarea detaliilor despre serverul Solr
    $solrServer = 'https://solr.peviitor.ro/solr'; 
    $coreName = 'jobs'; 
    $qs =  $query . '&' . $filterQuery;
   
   
    
    // Realizarea apelului către Solr
    $solrResponse = callSOLR($solrServer,$coreName, $qs);
    // Afiseaza rezultatele
    //print_r($solrResponse);
	
	// Obține job-urile de la Solr
    $jobsFromSolr = callSOLR($solrServer,$coreName, $filterQuery);
	
	// Extrage doar job_link-urile din răspunsul Solr
      $solrJobLinks = array_map(function ($job) {  return $job['job_link']; }, $jobsFromSolr);

		// Găsește job-urile excedentare
		$excessJobs = findExcessJobs($jobLinks, $solrJobLinks);
		echo json_encode($excessJobs);
}

// Exemplu de folosire a funcției
$jobLinksToCheck = [
    '"https://bitloop.tech/microsoft-dynamics-365-business-central-developers"'
    
    ];
$companyToFilter = $_GET['company'];


 // Construirea query-ului Solr
    $query = 'q=job_link:(' . implode(' OR ', array_map('urlencode', $jobLinksToCheck)) . ')';
    $filterQuery = 'fq=company:' . urlencode($companyToFilter);
   
   getJobsByJobLinksAndCompany($jobLinksToCheck,$query,$filterQuery);

?>
