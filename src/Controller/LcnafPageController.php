<?php

/**
 * @file
 * Contains \Drupal\lcnaf\Controller\LcnafPageController.
 */

namespace Drupal\lcnaf\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for lcnaf routes.
 */
class LcnafPageController extends ControllerBase {
  
  /**
   * simple hard-coded search example
   */
  public function simple_search_example() {

    // initialize
    $client = new \GuzzleHttp\Client();
    
    $first_name = 'jane';
    $last_name  = 'austen';
    $items = array();
    $result_count = 1;
    
    // send GET request - same query as LCNAF API example
    $response = $client->request('GET', 'http://alcme.oclc.org/srw/search/lcnaf', array(
      'query' => array(
        'query' => 'local.FamilyName+%3D+%22' . $last_name . '%22+and+local.FirstName+%3D+%22' . $first_name . '%22'
      ),
    ));
    
    // convert XML response from LCNAF service
    $xml_response = $response->getBody();
    $lcnaf_data_xml = new \SimpleXMLElement($xml_response);
    
    // extract data from each XML record  
    foreach($lcnaf_data_xml->records->record as $record) {
      // handle xml namespacing (example: "mx:record")
      $lcnaf_record_xml = $record->recordData->children('http://www.loc.gov/MARC21/slim');
      // get value of datafield 100a
      $item = $lcnaf_record_xml->xpath('//mx:datafield[@tag="100"]/mx:subfield[@code="a"]');
      $items[] = "<strong>Record $result_count (100a):</strong> " . $item[0];
      $result_count++;
    }
    
    $output = implode('<br />', $items);
    
    return array(
      '#markup' => t($output),
    );
    
  }
  
  /**
   * this page is an expanded version of simple_search_example() above;
   * accepts URL query parameters for first and last names. also uses twig template for displaying results.
   */
  public function custom_query_example() {
    
    // initialize
    $client = new \GuzzleHttp\Client();
    
    $first_name_default = 'harrison';
    $last_name_default  = 'ford';
    $items = array();
    $result_count = 1;
    
    // get query parameters from current page URL
    $current_url_string = \Drupal::request()->getRequestUri();
    $current_url = \Drupal\Core\Url::fromUserInput($current_url_string);
    $current_url_options = $current_url->getOptions();
    $first_name = (!empty($current_url_options['query']['first_name'])) ? $current_url_options['query']['first_name'] : $first_name_default;
    $last_name  = (!empty($current_url_options['query']['last_name'])) ? $current_url_options['query']['last_name'] : $last_name_default;
    
    // send GET request - same query as LCNAF API example
    $response = $client->request('GET', 'http://alcme.oclc.org/srw/search/lcnaf', array(
      'query' => array(
        'query' => 'local.FamilyName+%3D+%22' . $last_name . '%22+and+local.FirstName+%3D+%22' . $first_name . '%22'
      ),
    ));
    
    // convert XML response from LCNAF service
    $xml_response = $response->getBody();
    $lcnaf_data_xml = new \SimpleXMLElement($xml_response);
    
    // extract data from each XML record
    foreach($lcnaf_data_xml->records->record as $record) {
      // handle xml namespacing (example: "mx:record")
      $lcnaf_record_xml = $record->recordData->children('http://www.loc.gov/MARC21/slim');
      
      // get datafield values
      $item = array();
      $item['result_number']    = $result_count;
      $item['controlfield_001'] = $lcnaf_record_xml->xpath('//mx:controlfield[@tag="001"]')[0];
      $item['datafield_100a']   = $lcnaf_record_xml->xpath('//mx:datafield[@tag="100"]/mx:subfield[@code="a"]')[0];
      $item['datafield_100d']   = $lcnaf_record_xml->xpath('//mx:datafield[@tag="100"]/mx:subfield[@code="d"]')[0];
      
      $items[] = $item;
      $result_count++;
    }
    
    return array(
      '#theme' => 'results_table',
      '#title' => $this->t('Search Results'),
      '#items' => $items,
    );
     
  }

}
