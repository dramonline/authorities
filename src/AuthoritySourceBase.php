<?php
/**
 * @file
 * Provides Drupal\authority_search\AuthoritySource.
 */

namespace Drupal\authority_search;

use \GuzzleHttp\Client;
//use Drupal\node\Entity\Node;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\PluginBase;

class AuthoritySourceBase extends PluginBase implements AuthoritySourceInterface {
  
  // get properties from annotation docblock 
  
  public function getName() {
    return $this->pluginDefinition['name'];
  }
  
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  public function getSearchText() {
    return $this->pluginDefinition['search_text'];
  }

  public function setSearchText($search_text = '') {
    $this->pluginDefinition['search_text'] = $search_text;
  }

  public function getSearchOptions() {
    return $this->pluginDefinition['search_options'];
  }

  public function setSearchOptions($search_options = array()) {
    $this->pluginDefinition['search_options'] = $search_options;
  }
  
  /**
   * Return a query to search the Authority Source with
   *
   * @return string
   */
  public function buildQueryNameFirstLast($search_text = '', $search_options = array()) {

  	// fn parameters override variables passed in on plugin instantiation
  	if (!empty($search_text)) {
  	  $this->setSearchText($search_text);
  	}
  	
  	if (!empty($search_options)) {
  	  $this->setSearchText($search_options);
  	}

  	// split full name into first_name / last_name - seems to be only way to search LCNAF by full name
  	$full_name = $this->getSearchText();
    $name = $this->splitFullName($full_name);
    
    // finish building the query, adding in any search options
    $query_string = $this->buildQueryStringNameFirstLast($name);
    $query = $this->configQuery($query_string);

    return $query;
  }

  /**
   * wraps getAuthorityData() - for use with authority search forms
   */
  public function getAuthorityDataForm($form_state) {
    
    // @todo
    // need to use class to sanitize user input data, before passing into LCNAF query (same for main search query)
 
  	// get authority id from the form
    $user_input = $form_state->getUserInput();
    $authority_id = trim($user_input['authority_id']);
    
    // get authority data for this authority id
    $authority_data = $this->getAuthorityData($authority_id);

    return $authority_data;
  }

  /**
   * Build a table of renderable search results
   *
   * @return array
   */
  public function buildSearchResultsTable($form, $items) {
    
    //
    // build up the search results as rows in a FAPI table element
    //
    
    // @todo
    // add clean/safe filter to strings below (static fn, not t())
    
    $form['results_container']['results'] = array(
      '#type'    => 'table',
      '#headers' => array('Name', 'Birth/Death','OCLC #', 'Operation'),
      '#empty'   => t('No results found matching that name.'),
      '#tree'    => TRUE,
      '#weight'  => 200,
      '#attributes' => array(
        'class' =>  array(
          'table-search-results',
        ),
      ),
    );
    
    // add title to search results table (if not empty)
    //if (!empty($items)) {
      $form['results_container']['results']['#prefix'] = '<h3 class="results-table-title">' . t('Matching authority names') . '</h3>';
    //}
    
    // add a row of child form elements to the table element (parent) for each search result item
    foreach($items as $item) {
      if (isset($item['controlfield_001'])) {
        
        $row = $item['result_number'];
        $authority_name = (isset($item['datafield_100a'])) ? (string) $item['datafield_100a'] : '';
        $birth_death = (isset($item['datafield_100d'])) ? (string) $item['datafield_100d'] : '';
        $oclc = (isset($item['controlfield_001'])) ? (string) $item['controlfield_001'] : '';
        $authority_id = (isset($item['datafield_010a'])) ? (string) $item['datafield_010a'] : '';
        
        $form['results_container']['results'][$row]['result_name'] = array(
          '#type'   => 'markup',
          '#markup' => $authority_name,
          '#wrapper_attributes' => array(
            'class' =>  array(
              'result-name',
            ),
          ),
        );
        
        $form['results_container']['results'][$row]['result_birth_death'] = array(
          '#type'   => 'markup',
          '#markup' => $birth_death,
          '#wrapper_attributes' => array(
            'class' =>  array(
              'result-birth-death',
            ),
          ),
        );
        
        $form['results_container']['results'][$row]['result_authority_id'] = array(
          '#type'   => 'markup',
          '#markup' => $authority_id,
          '#wrapper_attributes' => array(
            'class' =>  array(
              'result-authority-id',
            ),
          ),
        );
                
        $form['results_container']['results'][$row]['authority_id'] = array(
          '#type'   => 'hidden',
          '#value'  => $authority_id,
          '#wrapper_attributes' => array(
            'class' =>  array(
              'authority-id',
            ),
          ),
        );
        
        $form['results_container']['results'][$row]['result_link_add_authority'] = array(
          '#type' => 'submit',
          '#name' => 'add-authority-' . $authority_id,
          '#value' => t('Add this Authority Name'),
          '#submit' => array('::submitFormAddAuthority'),
          '#wrapper_attributes' => array(
            'class' =>  array(
              'result-link-add-authority',
            ),
          ),
        );
      }
    }
    
    return $form['results_container'];
  }

  /**
   * Create a new authority entity
   *
   * @return bool
   */
  public function createAuthorityEntity($authority_data) {
     
    $result = FALSE;

    // get authority name data
    $authority_id = (isset($authority_data['authority_id'])) ? $authority_data['authority_id'] : '';
    $title = (isset($authority_data['name_full'])) ? $authority_data['name_full'] : '';
    
    // authority_id and title are required to create node
    if (!empty($authority_id) && !empty($title)) {
      
      // @todo
      // add admin form for setting a default entity type and entity bundle for each available Authority Source plugin.
      // also, make sure that all entity types support a 'title' property, as well as other properties set below.
      
      $entity_type   = 'node';
      $entity_bundle = 'page';
      $entity_storage = \Drupal::entityManager()->getStorage($entity_type)->create(array(
        'type'     => $entity_bundle,
        'title'    => $title,
        'langcode' => 'en',
        'uid'      => '1',
        'status'   => 1,
        'body'     => array(t("The Authority ID for this $entity_type is ") . $authority_id),
        // custom fields use this format:
        //'field_fields' => array(),
      ));
      $entity_storage->save();

      drupal_set_message("Created a new Authority Name node for " . $authority_data['name_full']);

      // @todo
      // return status based on if entity was actually created
    
      $result = TRUE;
    }
    else {
      drupal_set_message("FAILED to create Authority Name node for " . $authority_data['name_full'] . " - missing data.");
    }

    return $result;
  }

  /**
   * Add authority data to an existing authority entity
   *
   * @return bool
   */
  public function updateAuthorityEntity($authority_data) {

  	$result = FALSE;

 	/*
 	// get $entity_id and $entity_type from fn parameters (along with authority data).
 	// don't need to supply entity bundle here? just entity type.
 	$storage = \Drupal::entityManager()->getStorage($entity_type);
 	$entity = $storage->load($entity_id);
 	//
 	// modify title and store authority data in fields prior to saving this entity
 	//
	$entity->save();
 	*/
 	
    // @todo
    // return status based on if entity was properly updated
    
    $result = TRUE;

    return $result;
  }

  // shared util methods below - all plugins can use these (for splitting name string, etc.)
 
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
    
    // need to break up full name into parts for LCNAF search query, possibly others too
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

  //
  // methods below do not have default implementations
  //

  /**
   * Return a query string for searching the Authority Source
   *
   * @return string
   */
  public function buildQueryStringNameFirstLast($name) {

  	$query_sting = '';

    // use $name['first'] and $name['last'] to create a query string
    // specific to the plugin.
    
    return $query_string;
  }

  /**
   * Configure a generic query, using the query string provided
   *
   * @return string
   */
  public function configQuery($query) {
  	
  	// add code here
  	
  	return $query;
  }

  /**
   * Return search results from the Authority Source search
   *
   * @return array
   */
  public function search($query) {

  	// add code here
  	$items = array();

    return $items;
  }

  /**
   * Extract search results from Authority Source response data
   *
   * @return array
   */
  public function extractSearchResults($data) {

  	// add code here
  	$items = array();
    
    return $items;
  }

  /**
   * Get authority data for a particular search result item - this data can be used to populate fields
   * on a new or existing entity.
   *
   * @return array
   */
  public function getAuthorityData($authority_id) {

  	// add code here
  	$authority_data = array();

    return $authority_data;
  }

}
