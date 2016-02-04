<?php
/**
 * @file
 * Contains \Drupal\lcnaf\Form\AjaxRequestForm.
 */
namespace Drupal\lcnaf\Form;

use \GuzzleHttp\Client;
use Drupal\node\Entity\Node;
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
      '#autocomplete_route_name' => 'lcnaf.autocomplete',
      '#autocomplete_route_parameters' => array(),
    );
    
    // hidden field - LCCN
    $form['lcnaf_ajax_request_lccn'] = array(
      '#type' => 'hidden',
      '#value' => 0,
      '#attributes' => array(
        'class' =>  array(
          'lcnaf-lccn-value',
        ),
      ),
    );
    
    // container for primary submit element
    $form['actions'] = array(
      '#type' => 'actions',  
    );
    
    // this Submit button gets hidden w/ css;
    // apparently, this primary submit button must exist for submit buttons returned by the
    // ajax callback to work. also, the submit handler for these submit buttons seems to 
    // be determined by this primary submit button (regardless of how their #submit keys are set).
    
    // primary submit button
    $form['actions']['submit'] = array(
      '#type'  => 'submit',
      '#value' => $this->t('Submit'),
      '#submit' => array('::submitFormAddAuthority'),
    );
    
    // container for search results - ajax callback replaces contents of this element    
    $form['results_container'] = array(
      '#type'   => 'container',
      '#prefix' => '<div id="lcnaf-search-results">',
      '#suffix' => '</div>',
      '#tree'   => TRUE,
      '#weight' => 200,
    );
    
    // button to trigger ajax callback
    $form['lcnaf_ajax_request_do_search'] = array(
      '#type' => 'button',
      '#name' => 'search-lcnaf',
      '#value' => $this->t('Search the LCNAF'),
      '#ajax'  => array(
        'callback' => array($this, 'getSearchResultsCallbackLCNAF'), // use callback defined w/n this class 
        'event' => 'click',
        'wrapper' => 'lcnaf-search-results',
        'method' => 'replace',
      ),
    );
    
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
   */
  public function getSearchResultsCallbackLCNAF(array &$form, FormStateInterface $form_state) {
    
    // get form values
    $full_name = $form_state->getValue('lcnaf_ajax_request_full_name');
    
    // split full name into first_name / last_name - seems to be only way to search LCNAF by full name
    $name = $this->splitFullName($full_name);
    
    //
    // search LCNAF and format search results
    //
    $query_config = array(
      'query' => 'local.FamilyName+%3D+%22' . $name['last'] . '%22+and+local.FirstName+%3D+%22' . $name['first'] . '%22',
      'maximumRecords' => 10, // results per page
      'startRecord'    => 1,  // result number to start with
    );
    $lcnaf_data_xml = $this->searchLCNAF($query_config);
    $search_results = $this->buildResultsTableLCNAF($form, $lcnaf_data_xml);
    
    return $search_results;
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
   * process and format LCNAF search results (from XML), then populate form's render array with this data
   *
   * @todo
   * abstract XML processing code below, so we can reuse it for generic LCNAF searches
   */ 
  public function buildResultsTableLCNAF($form, $lcnaf_data_xml) {
    
    // convert search results XML into simplified data array
    $items = $this->extractXMLRecordDataLCNAF($lcnaf_data_xml);
    
    //
    // build up the search results as rows in a FAPI table element
    //
    
    // @todo
    // add clean/safe filter to strings below (static fn, not t())
    
    $form['results_container']['results'] = array(
      '#type'    => 'table',
      '#headers' => array('Name', 'Birth/Death','OCLC #', 'Operation'),
      '#empty'   => $this->t('No results found matching that name.'),
      '#tree'    => TRUE,
      '#weight'  => 200,
      '#attributes' => array(
        'class' =>  array(
          'lcnaf-table-results',
        ),
      ),
    );
    
    // add title to search results table (if not empty)
    //if (!empty($items)) {
      $form['results_container']['results']['#prefix'] = '<h3 class="results-table-title">' . $this->t('Matching authority names') . '</h3>';
    //}
    
    // add a row of child form elements to the table element (parent) for each search result item
    foreach($items as $item) {
      if (isset($item['controlfield_001'])) {
        
        $row = $item['result_number'];
        $authority_name = (isset($item['datafield_100a'])) ? (string) $item['datafield_100a'] : '';
        $birth_death = (isset($item['datafield_100d'])) ? (string) $item['datafield_100d'] : '';
        $oclc = (isset($item['controlfield_001'])) ? (string) $item['controlfield_001'] : '';
        $lccn = $oclc;
        
        $form['results_container']['results'][$row]['name'] = array(
          '#type'   => 'markup',
          '#markup' => $authority_name,
          '#wrapper_attributes' => array(
            'class' =>  array(
              'lcnaf-result-name',
            ),
          ),
        );
        
        $form['results_container']['results'][$row]['birth_death'] = array(
          '#type'   => 'markup',
          '#markup' => $birth_death,
          '#wrapper_attributes' => array(
            'class' =>  array(
              'lcnaf-result-birth-death',
            ),
          ),
        );
        
        $form['results_container']['results'][$row]['oclc'] = array(
          '#type'   => 'markup',
          '#markup' => $oclc,
          '#wrapper_attributes' => array(
            'class' =>  array(
              'lcnaf-result-oclc',
            ),
          ),
        );
                
        $form['results_container']['results'][$row]['lccn'] = array(
          '#type'   => 'hidden',
          '#value'  => $lccn,
          '#wrapper_attributes' => array(
            'class' =>  array(
              'lcnaf-result-lccn',
            ),
          ),
        );
        
        $form['results_container']['results'][$row]['link_add_authority'] = array(
          '#type' => 'submit',
          '#name' => 'add-authority-' . $lccn,
          '#value' => $this->t('Add this Authority Name'),
          '#submit' => array('::submitFormAddAuthority'),
          '#wrapper_attributes' => array(
            'class' =>  array(
              'lcnaf-result-link-add-authority',
            ),
          ),
        );
      }
    }
    
    return $form['results_container'];
  }

  /**
   * convert XML records from LCNAF response into simplified data array
   */
  public function extractXMLRecordDataLCNAF($lcnaf_data_xml) {
    
    $items = array();
    $result_count = 1;
    
    // extract data from each XML record
    foreach($lcnaf_data_xml->records->record as $record) {
      // handle xml namespacing (example: "mx:record")
      $lcnaf_record_xml = $record->recordData->children('http://www.loc.gov/MARC21/slim');
            
      // get datafield values
      $item = array();
      $item['result_number']    = $result_count;      
      $item['controlfield_001'] = $lcnaf_record_xml->xpath('mx:controlfield[@tag="001"]')[0];
      $item['datafield_100a']   = $lcnaf_record_xml->xpath('mx:datafield[@tag="100"]/mx:subfield[@code="a"]')[0];
      $item['datafield_100d']   = $lcnaf_record_xml->xpath('mx:datafield[@tag="100"]/mx:subfield[@code="d"]')[0];
            
      $items[] = $item;
      $result_count++;
    }
    
    return $items;
  }
  
  /** 
   * default submit handler
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    \Drupal::logger('lcnaf')->info("Thank you - default submit handler 'submitForm' was triggered.");
        
    // prevent form from redirecting
    //$form_state->setRebuild();
  }
  
  /**
   * custom submit handler
   */ 
  public function submitFormAddAuthority(array &$form, FormStateInterface $form_state) {
    
    // gather LCNAF data for selected record
    $authority_name_data = $this->_getAuthorityDataLCNAF($form_state);
    
    // create a new entity for selected authority name
    $this->_createAuthorityNameEntity($authority_name_data);
  
    // prevent form from redirecting
    $form_state->setRebuild();
    
    //\Drupal::logger('lcnaf')->info("Authority name added.");
  }
  
  /**
   * utility fn to gather data required for building Authority Name entity
   */
  public function _getAuthorityDataLCNAF($form_state) {
    
    $authority_name_data = array();
    
    // get LLCN and other data to be added to Authority Name entity
    $user_input = $form_state->getUserInput();
    $authority_name_data['lccn'] = $user_input['lcnaf_ajax_request_lccn'];
    
    // @todo
    // use LLCN to get data from LCNAF for corresponding record (do additional REST query)
    
    // TEMP
    $authority_name_data['name_full'] = $user_input['lcnaf_ajax_request_full_name']; // TEMP - should be name from record
    
    return $authority_name_data;
  }
  
  /**
   * utility fn to create a new Authority Name node
   */
  public function _createAuthorityNameEntity($authority_name_data) {
    
    // get authority name data
    $lccn = (isset($authority_name_data['lccn'])) ? $authority_name_data['lccn'] : '';
    $title = (isset($authority_name_data['name_full'])) ? $authority_name_data['name_full'] : '';
    
    // LCCN required to create node
    if (!empty($lccn) && !empty($title)) {
      
      $node = Node::create(array(
        'type' => 'page',
        'title' => $title,
        'langcode' => 'en',
        'uid' => '1',
        'status' => 1,
        'body' => array('The LCCN# for this node is ' . $lccn),
        // custom fields use this format:
        //'field_fields' => array(),
      ));
      
      $node->save();
      
      drupal_set_message("Created a new Authority Name node for " . $authority_name_data['name_full']);
    }
    else {
      drupal_set_message("FAILED to create Authority Name node for " . $authority_name_data['name_full'] . " - missing data.");
    }
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
  
}
