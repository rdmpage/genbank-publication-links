<?php

require_once(dirname(__FILE__) . '/ncbi.php');
require_once(dirname(__FILE__) . '/sqlite.php');

//----------------------------------------------------------------------------------------
// Process a list of accessions
function do_list($accessions)
{
	$list = $accessions;
	
	while (count($list) > 0)
	{
		echo "-- [" . count($list) . "]\n";
		
		// Get next accession
		$accession = array_pop($list);
		
		echo "-- $accession\n";
		
		$sql = 'UPDATE accession SET done=1 WHERE accession="' . $accession . '";';
		echo $sql . "\n";
		db_put($sql);	
		
		
		// Do we have any PIDs for this accession?
		$pids = accession_to_bib_pids($accession);
		
		if (count($pids) > 0)
		{
			// Yes
			print_r($pids);
			
			echo "-- We have PIDs\n";
			
			$obj = new stdclass;
			
			// new plan, just score direct matches, attenmpts to "look ahead" and get
			// other matches generates too many matches that aren't for our target
			// accessions
			
			if (isset($pids['pmid']))
			{
				$obj->pmid = $pids['pmid'];
				
				if (!isset($obj->id))
				{
					$obj->id = $pids['pmid'];
				}
			}
			elseif (isset($pids['doi']))
			{
				$obj->doi = $pids['doi'];
				
				$obj->doi = preg_replace('/https?:\/\/(dx\.)?doi.org\//', '', $obj->doi);
				
				$obj->doi = preg_replace('/doi.org\//', '', $obj->doi);
				$obj->doi = preg_replace('/doi:/i', '', $obj->doi);
				
				if (!isset($obj->id))
				{
					$obj->id = $pids['doi'];
				}
			}
			
			if (isset($obj->id))
			{
				// Store publication
				$sql = obj_to_sql($obj, 'publication');
				
				db_put($sql);
				
				// Store link
				$link = new stdclass;
				
				$link->accession = $accession;
				$link->publication = $obj->id ;
				$link->source = accession_to_bib_pids_url($accession);
								
				$sql = obj_to_sql($link, 'accession_publication');
				
				echo $sql . "\n";
				db_put($sql);	
			}			
			
			
			/*
			$obj = new stdclass;
				
			// Get list of all accessions linked to these PIDs	
			$gis = array();
		
			// Get list of all accessions linked to these PIDs	
			if (isset($pids['pmid']))
			{
				$obj->pmid = $pids['pmid'];
				
				if (!isset($obj->id))
				{
					$obj->id = $pids['pmid'];
				}
			
				$gis = pmid_to_gi($pids['pmid']);
			}
			elseif (isset($pids['doi']))
			{
				$obj->doi = $pids['doi'];
				
				$obj->doi = preg_replace('/https?:\/\/(dx\.)?doi.org\//', '', $obj->doi);
				
				if (!isset($obj->id))
				{
					$obj->id = $pids['doi'];
				}
			
				$gis = doi_to_gi($pids['doi'], true);
			}
			
			if  (isset($obj->id))
			{
				// Store publication
				$sql = obj_to_sql($obj, 'publication');
				
				db_put($sql);
			}			
			
			
			// echo "GIs from PID\n";
			// print_r($gis);

			if (count($gis->hits) > 0)
			{
				echo "-- Convert GIs to accession numbers\n";
			
				// Convert NCBI GIs to accessions
				$linked_accessions = gi_to_accession($gis->hits);
				
				// print_r($linked_accessions);
				
				// Store link
				foreach ($linked_accessions as $accession)
				{					
					$link = new stdclass;
					
					$link->accession = $accession;
					$link->publication = $obj->id;
					$link->source = $gis->url;
									
					$sql = obj_to_sql($link, 'accession_publication');
					
					// echo $sql . "\n";	
					
					db_put($sql);			
				}
				
				foreach ($linked_accessions as $acc)
				{
					$sql = 'UPDATE accession SET done=1 WHERE accession="' . $acc . '";';
					echo $sql . "\n";
					db_put($sql);					
				}				
				
				// Remove these from the list of accessions so we don't try and look these up
				$list = array_diff($list, $linked_accessions);		
			}
			*/
		}
		else
		{
			echo "-- No PIDs\n";

			$references = accession_to_references($accession);
			
			// print_r($references);
			
			// store
			
			foreach ($references->hits as $reference)
			{
				$obj = new stdclass;
				$obj->id = $reference->id;
				
				if (isset($reference->DOI))
				{
					$obj->doi = $reference->DOI;
				}

				if (isset($reference->PMID))
				{
					$obj->pmid = $reference->PMID;
				}

				if (isset($reference->title))
				{
					$obj->title = $reference->title;
				}
				
				if (isset($reference->{'container-title'}))
				{
					$obj->container = $reference->{'container-title'};
				}				

				if (isset($reference->created))
				{
					$obj->created = $reference->created;
				}
				
				$obj->csl = $reference;
				unset($obj->csl->created); // don't want this in CSL
				
				$sql = obj_to_sql($obj, 'publication');
				
				//echo $sql . "\n";
				db_put($sql);	
				
				$link = new stdclass;
				
				$link->accession = $accession;
				$link->publication = $reference->id;
				$link->source = $references->url;
								
				$sql = obj_to_sql($link, 'accession_publication');
				
				//echo $sql . "\n";
				db_put($sql);	
				
			}
		}
		
		
	}		
}

//----------------------------------------------------------------------------------------

$sql = 'SELECT * FROM accession WHERE accession LIKE "JN%" LIMIT 100';

//$sql = 'SELECT * FROM accession WHERE accession LIKE "MN%" LIMIT 100';
$sql = 'SELECT * FROM accession WHERE accession LIKE "KP%" LIMIT 100';
$sql = 'SELECT * FROM accession WHERE accession LIKE "KR%" LIMIT 100';
$sql = 'SELECT * FROM accession WHERE accession LIKE "JN6600%" LIMIT 100';

$sql = 'SELECT * FROM accession WHERE accession LIKE "KP85%" LIMIT 100'; // has DOI

$sql = 'SELECT * FROM accession WHERE accession LIKE "PP49%" LIMIT 100';
$sql = 'SELECT * FROM accession WHERE accession LIKE "JN6600%" LIMIT 100'; // nothing
$sql = 'SELECT * FROM accession WHERE accession LIKE "MN369968%" LIMIT 100';

// last looked at AB607100

$page_size = 1000;
$page = 0;

$count = 1;

$done = false;
while (!$done)
{
	$pattern = 'O%';
	$pattern = 'P%';
	$pattern = 'M%';
	$pattern = 'L%';
	$pattern = 'N%';
	$pattern = 'K%';
	$pattern = 'J%';
	$pattern = 'H%';
	$pattern = 'G%';
	$pattern = 'F%';
	$pattern = 'E%';
	$pattern = 'D%';
	$pattern = 'C%';
	$pattern = 'B%';
	$pattern = 'A%';	
	
	$pattern = 'MK%';
	
	$pattern = 'M%';
	//$pattern = 'MK12%';	
	
	//$pattern = 'AAUJ02000001%';
	
	//$pattern = 'GQ200%';
	
	$pattern = 'GU99744%';

	$sql = 'SELECT accession 
	FROM accession 
	LEFT JOIN accession_publication USING(accession) 
	WHERE accession LIKE "' . $pattern . '"
	AND accession NOT LIKE "%-SUPPRESSED"';
	
	$sql .= ' AND done IS NULL';
	
	$sql .= ' LIMIT ' . $page_size . ' OFFSET ' . $page * $page_size;		
	
	echo $sql . "\n";
					
	$data = db_get($sql);
	
	$done = count($data) == 0;
	
	if (!$done)
	{
		$accessions = array();
		
		foreach ($data as $row)
		{
			$accessions[] = $row->accession;
		}
		
		if (count($accessions) > 0)
		{									
			do_list($accessions);
			
			// Give server a break every 10 items
			if (($count++ % 10) == 0)
			{
				$rand = rand(1000000, 3000000);
				echo "\n-- ...sleeping for " . round(($rand / 1000000),2) . ' seconds' . "\n\n";
				usleep($rand);
			}
			
		}
	}
	
	$page++;

}


?>
