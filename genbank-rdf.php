<?php

// Export accession to publication links

ini_set('memory_limit', '-1');

require_once(dirname(__FILE__) . '/sqlite.php');

//----------------------------------------------------------------------------------------
// simplify CSL to just those elements we need to create a formatted record
function simplify_csl($csl)
{	
	foreach ($csl as $k => $v)
	{
		switch ($k)
		{
			case 'title': 
			case 'type':
			case 'container-title': 
			case 'volume': 
			case 'issue': 
			case 'page': 
			case 'issued': 
			case 'DOI': 
			case 'URL':
				break;
		
			case 'author': 
				$n = count($csl->$k);
				
				$authors = array();
				for ($i = 0; $i < $n; $i++)
				{
					$author = new stdclass;
					
					if (isset($csl->$k[$i]->family))
					{
						$author->family = $csl->$k[$i]->family;
					}
					if (isset($csl->$k[$i]->given))
					{
						$author->given = $csl->$k[$i]->given;
					}
					if (isset($csl->$k[$i]->literal))
					{
						$author->literal = $csl->$k[$i]->literal;
					}
					
					$authors[] = $author;
				}
				
				$csl->author = $authors;
				break;
		
			default:
				unset($csl->$k);
				break;
		}
	}

	return $csl;
}

//----------------------------------------------------------------------------------------
// Query for when we have DOIs
$sql = 'SELECT * FROM accession_dois';

$data = db_get($sql);

$datasets = array();

foreach ($data as $row)
{
	$triples = array();
	
	if (isset($row->doi))
	{
		$doi = $row->doi;
		
		// clean DOI
		
		// badness		
		$doi = str_replace('https://www.tandfonline.com/doi/full/', '', $doi);
		$doi = str_replace('https://onlinelibrary.wiley.com/doi/', '', $doi);
		$doi = str_replace('https://www.onlinelibrary.wiley.com/doi/', '', $doi);
		$doi = str_replace('https://doi-org.eres.qnl.qa/', '', $doi);
		$doi = str_replace('https://onlinelibrary.wiley.com/doi/abs/', '', $doi);
		$doi = str_replace('https://zookeys.pensoft.net/article/32257/', '', $doi);
		
		$doi = preg_replace('/^DOI:?\s+/', '', $doi);
		$doi = preg_replace('/\s+/', '', $doi);
				
		$doi = strtolower($doi);
		$doi = str_replace('<', '%3c', $doi);
		$doi = str_replace('>', '%3e', $doi);
		$doi = str_replace('[', '%5b', $doi);
		$doi = str_replace(']', '%5d', $doi);
					
		$triple = array(
			'<https://doi.org/' . $doi . '>',
			'<http://schema.org/citation>',
			'<https://identifiers.org/insdc/' . $row->accession . '>',
		);
		
		echo join(" ", $triple) . " .\n";
	
		
		/*
		// name for ease of display
		if (isset($row->title))
		{
			$name = $row->title;
			
			// clean
			$name = preg_replace('/\R/u', ' ', $name);	
			$name = preg_replace('/\s\s+/', ' ', $name);	
			$name = addcslashes($name, '"');
			
			$triple = array(
				'<https://identifiers.org/pubmed/' . $row->pmid . '>',
				'<http://schema.org/name>',
				'"' . $name . '"',
			);
			
			$triples[] = $triple;
		}	
		
		// link to DOI
		if (isset($row->doi))
		{
			$doi = $row->doi;
			
			// clean DOI
			$doi = str_replace('<', '%3c', $doi);
			$doi = str_replace('>', '%3e', $doi);
			$doi = str_replace('[', '%5b', $doi);
			$doi = str_replace(']', '%5d', $doi);
		
			$triple = array(
				'<https://identifiers.org/pubmed/' . $row->pmid . '>',
				'<http://schema.org/sameAs>',
				'<https://doi.org/' . $doi . '>',
			);
			
			$triples[] = $triple;
		}
		
		// CSL 
		if (isset($row->csl))
		{
			$csl = json_decode($row->csl);
			
			if ($csl)
			{
				
				$cleaned_csl = simplify_csl($csl);
				$text = json_encode($cleaned_csl);
				
				$text = preg_replace('/\R/u', ' ', $text);	
				$text = preg_replace('/\s\s+/', ' ', $text);	
				
				$text = str_replace(['\\', '"'], ['\\\\', '\\"'], $text);
				
				
				// $encoding_id = '_:' . md5($csl_json);
				
				$triple = array(
					'<https://identifiers.org/pubmed/' . $row->pmid . '>',
					'<http://schema.org/description>',
					'"' . $text . '"'
				);
				
				$triples[] = $triple;
			}
		}	
		
		foreach ($triples as $triple)
		{
			echo join(" ", $triple) . " .\n";
		}
	}
	else
	{
		// to do: URL
	
	}
}

?>
