<?php
/**
 * @file
 * Contains \Drupal\authority_search\Plugin\AuthoritySource\Lcnaf.
 */

namespace Drupal\authority_search\Plugin\AuthoritySource;

use \GuzzleHttp\Client;
use Drupal\Core\Form\FormStateInterface;
use Drupal\authority_search\AuthoritySourceBase;

/**
 * Provides an LCNAF Authority Source plugin.
 *
 * @AuthoritySource(
 *   id = "LCNAF",
 *   name = @Translation("LCNAF"),
 *   source_abbrev = "LCNAF",
 *   record_data_type = "XML",
 *   description = @Translation("LCNAF Authorities"),
 *   search_text = "",
 *   search_options = {}
 * )
 */
class Lcnaf extends AuthoritySourceBase {

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    // @todo
    // should use a container and configFactory instead?
    return \Drupal::config('authority_search.lcnaf-config')->get();
  }

  /**
   * Return a query string for searching the Authority Source
   *
   * @return string
   */
  public function buildQueryStringNameFirstLast($name) {

  	$first_name = (!empty($name['first'])) ? $name['first'] : '';
  	$last_name  = (!empty($name['last'])) ? $name['last'] : '';

    $query_string = 'local.FamilyName+%3D+%22' . $last_name . '%22+and+local.FirstName+%3D+%22' . $first_name . '%22';

    return $query_string;
  }

 /**
   * Return a query string to search the Authority Source with
   *
   * @return string
   */
  public function buildQueryStringAuthorityId($lccn) {

    $query_string = 'local.LCCN+%3D+%22' . $lccn . '%22';

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
 
    // build LCNAF query - search for First Name AND Family (Last) Name
    $query = array(
      'query'          => $query_string,
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
    $response = $client->request('GET', 'http://alcme.oclc.org/srw/search/lcnaf', array(
      'query' => $query,
    ));

    // @todo
    // no need to include SimpleXML dependency, since it's included with PHP by default?
 
    // convert XML response from LCNAF service
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
    // uri suffix defines format of record - can be ".html", ".marcxml.xml", etc. sample URIs:
    // http://id.loc.gov/authorities/names/n83026797.html
    // http://id.loc.gov/authorities/names/n83026797.marcxml.xml
    $uri_suffix = ".html";
    $uri = '';

    if (isset($record_xml->xpath('mx:datafield[@tag="010"]/mx:subfield[@code="a"]')[0])) {
      $datafield_010a = $record_xml->xpath('mx:datafield[@tag="010"]/mx:subfield[@code="a"]')[0]; // this is the LCCN
      // remove spaces from LCCN values (for example, "n 97072415" - should be "n97072415")
      $id_for_uri = $this->makeQueryReadyAuthorityId($datafield_010a);
    }

    if (!empty($id_for_uri)) {
      $uri = 'http://id.loc.gov/authorities/names/' . $id_for_uri . $uri_suffix;
    }

    return $uri;
  }

}
