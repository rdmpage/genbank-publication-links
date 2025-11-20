<?php

ini_set('memory_limit', '-1');

// Read list of accession numbers look for publications


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
		
		echo "$accession\n";
		
		// flag that we have done this accession 
		if (1)
		{
			$sql = 'UPDATE accession SET done=1 WHERE accession="' . $accession . '";';
			echo $sql . "\n";
			db_put($sql);	
		}
		
		// Do we have any PIDs for this accession?
		$pids = accession_to_bib_pids($accession);
		
		if (count($pids) > 0)
		{
			// Yes
			print_r($pids);
			
			echo "We have PIDs\n";			
			$obj = new stdclass;
				
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
				
				if (!isset($obj->id))
				{
					$obj->id = $pids['doi'];
				}
			}
			
			if (isset($obj->id))
			{
				// Store publication
				echo "Storing publication with PIDs\n";
				$sql = obj_to_sql($obj, 'publication');
				
				db_put($sql);
			
				echo "Storing link to publication\n";
				$link = new stdclass;
				
				$link->accession = $accession;
				$link->publication = $obj->id;
				$link->source = accession_to_bib_pids_url($accession);
								
				$sql = obj_to_sql($link, 'accession_publication');
				
				//echo $sql . "\n";
				db_put($sql);	
			}
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
				
				echo json_encode($obj->csl);
				
				echo "Storing publication\n";
				$sql = obj_to_sql($obj, 'publication');
				
				//echo $sql . "\n";
				db_put($sql);	
				
				echo "Storing link to publication\n";
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
// get accession numbers and look up
$filename = 'test.tsv';

$batch_size = 100;
$batch = array();

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$accession = trim(fgets($file_handle));
	
	$batch[] = $accession;
	
	if (count($batch) == $batch_size)
	{
		// do stuff
		print_r($batch);
		
		do_list($batch);
		
		// reset
		$batch = [];	
	}
}



?>
