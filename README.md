# Genbank publication links

Linking GenBank accessions to publications. This is complementary to [rdmpage/genbank-pubmed-links](https://github.com/rdmpage/genbank-pubmed-links) which links GenBank accessions to PMIDs (PubMed ids).

This repository focuses on accessions that lack PMIDs but have other identifiers in GenBank (e.g., DOIs), or lack any identifiers.


## Cleaning matches to accessions that are not in our list

If we fetch all sequences linked to a publication, we retrieve many more accessions than those in our target list. These queries find these extras so we can delete them.

### Extra publications

```
select publication.id from publication left join accession_publication on publication.id = accession_publication.publication where accession_publication.publication is null;
``` 

### Extra accessions

```
select accession_publication.accession from accession_publication left join accession on accession.accession = accession_publication.accession where accession.accession is null;
```
