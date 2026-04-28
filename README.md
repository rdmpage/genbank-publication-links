# Genbank publication links

Linking GenBank accessions to publications. This is complementary to [rdmpage/genbank-pubmed-links](https://github.com/rdmpage/genbank-pubmed-links) which links GenBank accessions to PMIDs (PubMed ids).

This repository focuses on accessions that lack PMIDs but have other identifiers in GenBank (e.g., DOIs), or lack any identifiers.

## Adding references

The core approach is to retrieve a GenBank record via the API, extract a PMID and DOI (if they are present), and bibliographic details which are converted to CSL-JSON. By default GenBank records have a reference “Direct Submission” which we ignore.

Two approaches are available. `simple.php` takes a TSV file of accession numbers, retrieves that record from GenBank, extracts identifiers and metadata.

The file `go.php` takes a slightly different approach. It queries the local database for accessions that have not been matched (`accession.done = NULL`) and uses the API to retrieve those sequences. Originally it tried to accelerate the search by fetching all sequences linked to a bibliographic identifier, but this generated lots of additional accession numbers that are not directly relevant to this project. This code is currently commented out.

## Matching references to DOIs

### CrossRef

For references that don’t have DOIs in GenBank and which do have  metadata we attempt to match the references to a DOI in CrossRef. Matches found are added to the column `rdmp_doi` with `rdmp_doi_method` set to `crossref`, and `rdmp_doi_search` set to 1. If the search failed `rdmp_doi_search` is set to 0. 

### Manual

In cases where we haven’t matched to a CrossRef DOI (e.g., lack of metadata, DOIs available but not in CrossRef, or other reasons) but we can find a DOI manually, those are added to the column `rdmp_doi` with `rdmp_doi_method` set to `manual`.

If we don’t have a DOI but there is a URL (e.g., an article online or a PDF) then we add that to `rdmp_url`.

## Cleaning matches to accessions that are not in our list

Originally the code tried to accelerate linking to references by querying GenBank by bibliographic identifier. However, this would often fetch many more accessions than those in our target list (e.g., non-barcode sequences linked to a publication). The queries below find these extras that have been added to the database so that we can delete them as (currently) out of scope.

### Extra publications

```
select publication.id from publication left join accession_publication on publication.id = accession_publication.publication where accession_publication.publication is null;
``` 

### Extra accessions

```
select accession_publication.accession from accession_publication left join accession on accession.accession = accession_publication.accession where accession.accession is null;
```
