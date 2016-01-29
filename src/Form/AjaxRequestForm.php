<?php
/**
 * @file
 * Contains \Drupal\lcnaf\Form\AjaxRequestForm.
 */
namespace Drupal\lcnaf\Form;

use \GuzzleHttp\Client;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for this module.
 */
class AjaxRequestForm extends FormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lcnaf_ajax_request';
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    // Full Name textfield
    $form['lcnaf_ajax_request_full_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#placeholder' => t('Enter full name to search for...'),
      '#default_value' => '',
    );
    
    // search results container - ajax callback replaces this with search results content
    $form['results'] = array(
      '#prefix' => '<div id="lcnaf-search-results">',
      '#suffix' => '</div>',
      '#tree'   => TRUE,
      '#weight' => 100,
      //'#markup' => t('No results to display yet.'),
    );
    
    // button to trigger ajax callback
    $form['lcnaf_ajax_request_do_search'] = array(
      '#type' => 'button',
      '#value' => $this->t('Search the LCNAF'),
      '#ajax' => array(
        'callback' => array($this, 'LCNAFgetSearchResultsCallback'), // use callback defined w/n this class 
        'event' => 'click',
      ),
    );
  
    // Submit button
    /*
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit this form'),
      '#button_type' => 'primary',
    );
    */
    
    // add custom classes to this form
    $form['#attributes'] = array(
      'class' =>  array(
        'clearfix',
      ),
    );
    
    // attach module css to this form
    $form['#attached'] = array(
      'library' =>  array(
        'lcnaf/lcnaf-search-results',
      ),
    );
    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // not validating anything in this form
  }
  
  /**
   * custom ajax callback
   *
   * @todo
   * abstract code below even further
   */
  public function LCNAFgetSearchResultsCallback(array &$form, FormStateInterface $form_state) {
    
    // get form values
    $full_name = $form_state->getValue('lcnaf_ajax_request_full_name');
    
    // split full name into first_name / last_name - seems to be only way to search LCNAF by name
    $name = $this->splitFullName($full_name);
    
    //
    // search LCNAF and format search results;
    // an abstracted version of code used in simple search examples (LcnafPageController.php)
    //
    $query_config = array(
      'query' => 'local.FamilyName+%3D+%22' . $name['last'] . '%22+and+local.FirstName+%3D+%22' . $name['first'] . '%22',
      'maximumRecords' => 10, // results per page
      'startRecord'    => 1,  // result number to start with
    );
    $lcnaf_data_xml = $this->searchLCNAF($query_config);
    $search_results_html = $this->formatResultsLCNAF($lcnaf_data_xml);
    
    // instantiate an AjaxResponse Object for this callback to return.
    $ajax_response = new AjaxResponse();
    
    // replace content of <div id="lcnaf-search-results"> - HtmlCommand uses jquery .html() method
    $ajax_response->addCommand(new HtmlCommand('#lcnaf-search-results', $search_results_html));
    
    // return the AjaxResponse Object - don't use 'wrapper' in ajax form element with this
    return $ajax_response;
  }

  /*
   * split full name into first name / last name
   * (works with "Sol Lewitt" or "Lewitt, Sol")
   */
  public function splitFullName($full_name) {
    
    $full_name = trim($full_name);
    $name = array(
      'first' => '',
      'last'  => '',  
    );
    
    // need to break up full name into parts for LCNAF search query
    if (preg_match("/^\w+,\s*\w+/", $full_name)) {
      $name['first'] = preg_replace("/.*[,\s](\w+)$/", "$1", $full_name);
      $name['last']  = preg_replace("/^(\w+)[,\s].*/", "$1", $full_name);
    }
    else {
      $name['first'] = preg_replace("/^(\w+).*/", "$1", $full_name);
      $name['last']  = preg_replace("/.*\s(\w+)$/", "$1", $full_name);
    }
    
    return $name;  
  }
  
  /**
   * do LCNAF search - returns XML data from response as SimpleXMLElement
   */ 
  public function searchLCNAF($query_config) {
    
    // initialize http client
    $client = new Client();
    
    // send GET request - same query as LCNAF API example
    $response = $client->request('GET', 'http://alcme.oclc.org/srw/search/lcnaf', array(
      'query' => $query_config,
    ));
    
    // convert XML response from LCNAF service
    $xml_response = $response->getBody();
    $lcnaf_data_xml = new \SimpleXMLElement($xml_response);
    
    return $lcnaf_data_xml;
  }
  
  /**
   * process and format LCNAF search results (from XML), render as html using twig template
   */ 
  public function formatResultsLCNAF($lcnaf_data_xml) {
    
    $items = array();
    $result_count = 1;
    
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
    
    //
    // create rendered html for search results, using twig template: results_table.html.twig
    //
    
    // build up render array
    $search_results = array(
      '#theme' => 'results_table',
      '#items' => $items,
    );
    
    // use Renderer service to render the render array as HTML
    // (could also use drupal_render() but that will be deprecated soon)
    $renderer = \Drupal::service('renderer');
    $search_results_html = $renderer->render($search_results);
    
    return $search_results_html;
  }
  
  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    // submitForm will only be used to add authority nodes - search results now handled by ajax.
    
    // get form values
    $full_name = $form_state->getValue('lcnaf_ajax_request_full_name');
    
    // prevents form from redirecting below
    $form_state->setRebuild();
  }
}
