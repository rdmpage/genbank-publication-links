<?php

error_reporting(E_ALL);

require_once('sqlite.php');


$sql = "select * from publication where container LIKE 'Zool Stud%' AND doi IS NULL AND rdmp_doi IS NULL";

$data = db_get($sql);

$keys = [
'id',
'title',
'journal',
'volume',
'spage',
'epage',
'year',
'doi',
];

echo join("\t", $keys) . "\n";

foreach ($data as $row)
{
	$csl = json_decode($row->csl);

	$values = array();
	
	$values[] = $csl->id;
	
	if (isset($csl->title))
	{
		$values[] = $csl->title;
	}
	else
	{
		$values[] = '';
	}
	
	if (isset($csl->{'container-title'}))
	{
		$values[] = $csl->{'container-title'};
	}
	else
	{
		$values[] = '';
	}	
	
	if (isset($csl->volume))
	{
		$values[] = $csl->volume;
	}
	else
	{
		$values[] = '';
	}

	if (isset($csl->page))
	{
		if (preg_match('/(\d+)-(\d+)/', $csl->page, $m))
		{
			$values[] = $m[1];
			$values[] = $m[2];				
		}
		else
		{
			$values[] = $csl->page;
			$values[] = '';		
		}		
	}
	else
	{
		$values[] = '';
		$values[] = '';
	}


	if (isset($csl->issued))
	{
		$values[] = $csl->issued->{'date-parts'}[0][0];
	}
	else
	{
		$values[] = '';
	}
	
	if (isset($csl->DOI))
	{
		$values[] = $csl->DOI;
	}
	else
	{
		$values[] = '';
	}
	
		
	//print_r($values);

	echo join("\t", $values) . "\n";
}

?>
