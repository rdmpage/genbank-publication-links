<?php

// CrossRef search (text and/or OpenURL)

error_reporting(E_ALL);

require_once('sqlite.php');

//----------------------------------------------------------------------------------------
function get($url, $content_type = '')
{	
	$data = null;

	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE,
	  
	  CURLOPT_SSL_VERIFYHOST => FALSE,
	  CURLOPT_SSL_VERIFYPEER => FALSE,
	  
	  CURLOPT_TIMEOUT 		=> 3
	  
	);

	if ($content_type != '')
	{
		$opts[CURLOPT_HTTPHEADER] = array(
			"Accept: " . $content_type 
		);		
	}
	
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$data = curl_exec($ch);
	$info = curl_getinfo($ch); 
	
	//print_r($info);
	
	curl_close($ch);
	
	return $data;
}



//----------------------------------------------------------------------------------------

$sql = 'SELECT * FROM no_doi_but_with_metadata LIMIT 10000';

$sql = 'SELECT * FROM no_doi_but_with_metadata WHERE container="Afr. Invertebr." LIMIT 100';

//$sql = 'SELECT * FROM no_doi_but_with_metadata WHERE container="PeerJ" LIMIT 100';

//$sql = 'SELECT * FROM publication WHERE id="da8ce141acb4c0e23a25a32d6ca9d8c7"';

//$sql = 'SELECT * FROM no_doi_but_with_metadata WHERE container LIKE "V%" LIMIT 100';
$sql = 'SELECT * FROM no_doi_but_with_metadata LIMIT 10000';

$container = 'Proc. R. Soc. Lond., B, Biol. Sci.';
$container = 'Proc. Entomol. Soc. Wash.';
$sql = 'SELECT * FROM publication WHERE container="' . $container  . '" AND doi IS NULL AND rdmp_doi IS NULL AND rdmp_url IS NULL;';


//$sql = 'SELECT * FROM publication WHERE id="d2b835fd126b5972be0e4907abd84bb4"';


$data = db_get($sql);

foreach ($data as $obj)
{
	// print_r($obj);
	
	$keys = ['author', 'issued', 'title', 'container-title', 'volume', 'issue', 'page'];
	
	$have_keys = array();
	
	$keys = ['issued', 'title', 'container-title', 'volume', 'issue', 'page'];

	$csl = json_decode($obj->csl);

	$terms = array();	
	foreach ($keys as $k)
	{
		if (isset($csl->{$k}))
		{
			switch ($k)
			{
				case 'author':
					$authors = [];
					foreach ($csl->author as $author)
					{
						$authors[] = $author->literal;
					}
					$terms[] = join(", ", $authors);
					
					$have_keys[] = $k;
					break;
			
				case 'issued':
					$terms[] = ' ' . $csl->{$k}->{'date-parts'}[0][0] . ' ';
					
					$have_keys[] = $k;
					break;
					
				case 'title':
					$terms[] = $csl->{$k} . '.';
					
					$have_keys[] = $k;
					break;
					
				case 'container-title':
					$container = $csl->{$k};
					switch ($container)
					{
						case 'Proc. R. Soc. Lond., B, Biol. Sci.':
							$container = 'Proceedings of the Royal Society of London. Series B: Biological Sciences';
							break;

						case 'Proc. Entomol. Soc. Wash.':
							$container = 'Proceedings of the Entomological Society of Washington';
							break;
					
						default:
							break;							
					}
					$terms[] = $container . '.';
					$have_keys[] = $k;
					break;
					
				default:
					$terms[] = $csl->{$k};
					
					$have_keys[] = $k;
					break;
			}
		}
	}
	
	// print_r($terms);
	
	$q = join(' ', $terms);
		
	echo "-- $obj->id $q\n";
			
	$url = 'http://localhost/citation-matching/api/crossref.php?q=' . urlencode($q);
	
	$url .= '&keys=' . urlencode(json_encode($have_keys));
	
	echo "-- $url\n";
	
	$json = get($url);

	$response = json_decode($json);
	
	//print_r($response);
	
	if ($response)
	{
		if (isset($response->DOI))
		{
			echo 'UPDATE `publication` SET rdmp_doi="' . $response->DOI . '" WHERE id="' . $obj->id . '";' . "\n";
		}	
		echo 'UPDATE `publication` SET rdmp_doi_search=1 WHERE id="' . $obj->id . '";' . "\n";		
	}
	
	
	
}

?>
