<?php
/**
 * @file
 * Provides Drupal\authority_search\AuthoritySource.
 */

namespace Drupal\authority_search;

use \GuzzleHttp\Client;
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

  public function getSourceAbbrev() {
    return $this->pluginDefinition['source_abbrev'];
  }

  public function getRecordDataType() {
    return $this->pluginDefinition['record_data_type'];
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
   * @return array
   */
  public function buildQueryNameFirstLast($search_text = '', $search_options = array()) {

  	// fn parameters override variables passed in on plugin instantiation
  	if (!empty($search_text)) {
  	  $this->setSearchText($search_text);
  	}
  	
  	if (!empty($search_options)) {
  	  $this->setSearchOptions($search_options);
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
   * Return a query for searching the Authority Source by authority id (LCCN, etc.)
   *
   * @return array
   */
  public function buildQueryAuthorityId($authority_id) {
    // cleanup LCCN value for query
    $authority_id = $this->makeQueryReadyAuthorityId($authority_id);

    // finish building the query, adding in any search options
    $query_string = $this->buildQueryStringAuthorityId($authority_id);
    $query = $this->configQuery($query_string);

    return $query;
  }

  /**
   * Return a query string for searching the Authority Source
   *
   * @return string
   */
  public function buildQueryStringNameFirstLast($name) {
  	//
  	// add code here
  	//
  	$query_sting = '';

    // use $name['first'] and $name['last'] to create a query string
    // specific to the plugin.
    
    return $query_string;
  }

  /**
   * Get authority data for a particular record, specified by an authority id;
   * this data can be used to populate fields on a new or existing entity.
   *
   * @return array
   */
  public function buildQueryStringAuthorityId($authority_id) {
  	//
  	// add code here
  	//
    $query_string = '';
    
    return $query_string;
  }

  /**
   * Configure a generic query, using the query provided
   *
   * @return array
   */
  public function configQuery($query) {
  	//
  	// add code here
  	//
  	return $query;
  }

  /**
   * Return search results from the Authority Source search
   *
   * @return array
   */
  public function search($query) {
  	//
  	// add code here
  	//
  	$items = array();

    return $items;
  }

  /**
   * Get authority data for a particular search result item - this data can be used to populate fields
   * for a new or existing entity.
   *
   * @return array
   */
  public function getAuthorityData($authority_id) {

    $authority_id = $authority_id;
    $authority_data = $this->getAuthorityDataByAuthorityId($authority_id);

    return $authority_data;
  }

  /**
   * wrapper for getAuthorityData() - for use with authority search forms
   *
   * @return array
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
   * Get authority data for a particular search result item - this data can be used to populate fields
   * for a new or existing entity.
   *
   * @return array
   */
  public function getAuthorityDataByAuthorityId($authority_id) {
        
    $authority_data = array();
    
    // use LCCN to query VIAF for corresponding record, then extract data from it
    if (!empty($authority_id)) {
      $query = $this->buildQueryAuthorityId($authority_id);
      $items = $this->search($query);
      
      if (!empty($items)) {
        $item = reset($items);
        $authority_data['authority_id'] = $authority_id;
        $authority_data['name_full'] = (isset($item['datafield_100a'])) ? (string) $item['datafield_100a'] : '';
        $authority_data['source']    = (isset($item['source'])) ? (string) $item['source'] : '';
        $authority_data['rules']     = (isset($item['rules'])) ? (string) $item['rules'] : '';
        $authority_data['uri']       = (isset($item['uri'])) ? (string) $item['uri'] : '';
        $authority_data['data']      = (isset($item['data'])) ? (string) $item['data'] : '';
        $authority_data['data_type'] = (isset($item['data_type'])) ? (string) $item['data_type'] : '';
      }
    }
    
    return $authority_data;
  }

  /**
   * Extract search results from Authority Source response data
   *
   * @return array
   */
  public function extractSearchResults($data) {
  	//
  	// add code here
  	//
  	$items = array();
    
    return $items;
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
      // make sure that all entity types support a 'title' property, as well as other properties set below.
      
      $configuration = $this->getConfiguration();
      //kint($this->getConfiguration());

      $entity_type   = $configuration['target_entity_type'];
      $entity_bundle = $configuration['target_entity_subtype'];
      $authority_field_name = $this->getAuthorityFieldName($entity_type, $entity_bundle);

      $entity_data = array(
        'type'     => $entity_bundle,
        'title'    => $title,
        'langcode' => 'en',
        'uid'      => '1',
        'status'   => 1,
        'body'     => array(t("The Authority ID for this $entity_type is ") . $authority_id),
      );

      // can use $entity->hasField('field_article_some_text') to check if a field exists.

      if (!empty($authority_field_name)) {
        $entity_data[$authority_field_name] = array(
          'name'      => $title,
          'source_id' => $authority_id,
          'source'    => $authority_data['source'],
          'rules'     => $authority_data['rules'],
          'uri'       => $authority_data['uri'],
          'data'      => $authority_data['data'],
          'data_type' => $authority_data['data_type'],
        );
      }

      $entity_storage = \Drupal::entityManager()->getStorage($entity_type)->create($entity_data);
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

    // @todo
    // how to update an existing field when its cardinality is > 1? seems like we just append to it?
    
    // @todo
    // return status based on if entity was properly updated
    
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
 	 
    $result = TRUE;

    return $result;
  }

  /**
   * Prepare authority id for query by removing whitespace and other extraneous characters
   *
   * @return string
   */
  public function makeQueryReadyAuthorityId($authority_id) {
    // remove all spaces from LCCN
    $authority_id = preg_replace("/\s+/", "", $authority_id);
    
    return $authority_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    //
    // add code here
    //
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    //
    // add code here
    //
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    //
    // add code here
    //
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    //
    // add code here
    //
    return $dependencies;
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

  /*
   * get all field definitions for the given $entity_type and $entity_bundle
   */
  public function getAuthorityFieldName($entity_type, $entity_bundle) {

    $authority_field_type = 'field_authority';
    $authority_field_name = '';

    $entityManager = \Drupal::service('entity.manager');
    $fields = $entityManager->getFieldDefinitions($entity_type, $entity_bundle);

    // look for one or more instances of the Authority field type
    foreach($fields as $field) {
      $field_type = $field->getType();

      if ($field_type == $authority_field_type) {
        $authority_field_name = $field->getName();
        // use first instance found of the Authority field type
        break;
      }
    }

    return $authority_field_name;
  }

}
