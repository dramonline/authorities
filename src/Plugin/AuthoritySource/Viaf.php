<?php
/**
 * @file
 * Contains \Drupal\authority_search\Plugin\AuthoritySource\Viaf.
 */

namespace Drupal\authority_search\Plugin\AuthoritySource;

use \GuzzleHttp\Client;
use Drupal\Core\Form\FormStateInterface;
use Drupal\authority_search\AuthoritySourceBase;

/**
 * Provides an VIAF Authority Source plugin.
 *
 * @AuthoritySource(
 *   id = "VIAF",
 *   name = @Translation("VIAF"),
 *   description = @Translation("VIAF Authorities"),
 *   source_abbrev = "VIAF",
 *   record_data_type = "XML",
 *   search_text = "",
 *   search_options = {}
 * )
 */
class Viaf extends AuthoritySourceBase {

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    // @todo
    // should use a container and configFactory instead?
    return \Drupal::config('authority_search.viaf-config')->get();
  }

  /**
   * Return a query string for searching the Authority Source
   *
   * @return string
   */
  public function buildQueryStringNameFirstLast($name) {

  	$first_name = (!empty($name['first'])) ? $name['first'] : '';
  	$last_name  = (!empty($name['last'])) ? $name['last'] : '';

    $query_string = 'local.personalName all "' . $first_name . ' ' . $last_name . '"';

    return $query_string;
  }

  /**
   * Return a query string to search the Authority Source with
   *
   * @return string
   */
  public function buildQueryStringAuthorityId($authority_id) {

    $query_string = 'local.lccn exact "' . $authority_id . '"';

    return $query_string;
  }

  /**
   * Configure a generic query, using the query string provided
   *
   * @return string
   */
  public function configQuery($query_string) {

  	// @todo
    // incorporate search options
    
    // get any additional search options
    $options = $this->getSearchOptions();
 
    // build VIAF query - search for First Name AND Family (Last) Name
    $query = array(
      'query'          => $query_string,
      'httpAccept'     => 'application/xml',
      'maximumRecords' => 10, // results per page
      'startRecord'    => 1,  // result number to start with
    );

    return $query;
  }

  /**
   * Return search results from the Authority Source search
   *
   * @return array
   */
  public function search($query) {

  	// initialize http client
    $client = new Client();

    // send GET request with search query
    $response = $client->request('GET', 'http://viaf.org/processed/search/processed', array(
      'query' => $query,
    ));
 
    // convert XML response from VIAF service
    $xml_response = $response->getBody();
    $xml_data = new \SimpleXMLElement($xml_response);

    // convert search results XML data into simplified data array
    $items = $this->extractSearchResults($xml_data);

    return $items;
  }

  /**
   * Extract search results from Authority Source response data
   *
   * @return array
   */
  public function extractSearchResults($xml_data) {
    
    $items = array();
    $result_count = 1;

    if (isset($xml_data->records->record)) {
  	  // extract data from each XML record
  	  foreach($xml_data->records->record as $record) {
  		  // handle xml namespacing (example: "mx:record")
  		  $record_xml = $record->recordData->children('http://www.loc.gov/MARC21/slim');

  		  // get datafield values
  		  $item = array();
  		  $item['result_number'] = $result_count;

  		  // @todo
  		  // abstract this - plugin can just provide array of field labels, possibly correpsonding xpath
  		  // or json paths for getting field values for each? maybe stores human-readable name also.
  		  // could implement that as a yaml file and/or configuration entity.
  		
  		  if (isset($record_xml->xpath('mx:controlfield[@tag="001"]')[0])) {
  	      $item['controlfield_001'] = $record_xml->xpath('mx:controlfield[@tag="001"]')[0]; // OCoLC number
  		  }

  		  if (isset($record_xml->xpath('mx:datafield[@tag="010"]/mx:subfield[@code="a"]')[0])) {
  	      $item['datafield_010a'] = $record_xml->xpath('mx:datafield[@tag="010"]/mx:subfield[@code="a"]')[0]; // this is the LCCN
  		    // attempt to repair bad LCCN values (such as "n 97072415" - should be "n97072415")
  		    $item['datafield_010a'] = $this->makeQueryReadyAuthorityId($item['datafield_010a']);
  	    }

  	    if (isset($record_xml->xpath('mx:datafield[@tag="100"]/mx:subfield[@code="a"]')[0])) {
  	      $item['datafield_100a'] = $record_xml->xpath('mx:datafield[@tag="100"]/mx:subfield[@code="a"]')[0];
  	    }

  	    if (isset($record_xml->xpath('mx:datafield[@tag="100"]/mx:subfield[@code="d"]')[0])) {
  	      $item['datafield_100d'] = $record_xml->xpath('mx:datafield[@tag="100"]/mx:subfield[@code="d"]')[0];
  	    }

        $item['source']    = $this->getSourceAbbrev();
        $item['data_type'] = $this->getRecordDataType();
        $item['uri']       = $this->buildAuthorityUri($record_xml);

        // @todo
        // get 'rules' from $record_xml -- parse datafield 008
        $item['rules'] = 'RDA';
        
        // @todo
        // store XML record as-is or as serialized XML? can use drupal serialize fn for XML and JSON.
        // store entire data structure (as XML, JSON, etc.)
        $item['data'] = $xml_data->records->record->asXML();

  	    $items[] = $item;
  		  $result_count++;
  	  }
  	}
    
    return $items;
  }

  /**
   * Utility fn - returns a URI corresponding to the authority record.
   *
   * @return string
   */
  public function buildAuthorityUri($record_xml) {
    $uri = '';

    if (isset($record_xml->xpath('mx:datafield[@tag="010"]/mx:subfield[@code="a"]')[0])) {
      $datafield_010a = $record_xml->xpath('mx:datafield[@tag="010"]/mx:subfield[@code="a"]')[0]; // this is the LCCN
      // remove spaces from LCCN values (for example, "n 97072415" - should be "n97072415")
      $lccn = $this->makeQueryReadyAuthorityId($datafield_010a);
    }

    if (!empty($lccn)) {
      $uri = $this->translateLccnIdToViafUri($lccn);
    }

    return $uri;
  }

  /**
   * Utility fn - translate a LCCN id to a VIAF id.
   *
   * @return string
   */
  public function translateLccnIdToViafUri($lccn) {
    // use GET operation to retrieve VIAF uri for a given LC record id

    // initialize http client
    $client = new Client();

    // send GET request for LCCN converted to VIAF;
    // need to disable redirects, since VIAF uri is included as part of the 301 redirect header.
    $response = $client->request('GET', 'http://www.viaf.org/viaf/lccn/' . $lccn, array(
      'allow_redirects' => FALSE
    ));

    $viaf_id = $response->getHeaderLine('location');

    return $viaf_id;
  }

}
