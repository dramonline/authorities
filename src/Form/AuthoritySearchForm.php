<?php
/**
 * @file
 * Contains \Drupal\authority_search\Form\AuthoritySearchForm.
 */
namespace Drupal\authority_search\Form;

use \GuzzleHttp\Client;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for this module.
 */
class AuthoritySearchForm extends FormBase {
  
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'authority_search_form';
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    // Authority Source plugin to search with
    $form['authority_source'] = array(
      '#type' => 'select',
      '#title' => t('Source'),
      '#options' => list_authority_source_plugins(),
      //'#default_value' => 'LCNAF',
    );

    // Full Name textfield
    $form['full_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#placeholder' => t('Enter full name to search for...'),
      '#default_value' => '',
      '#autocomplete_route_name' => 'authority_search.autocomplete',
      '#autocomplete_route_parameters' => array(),
    );
    
    // hidden field - stores the authority ID (LCCN, etc.)
    $form['authority_id'] = array(
      '#type' => 'hidden',
      '#value' => 0,
      '#attributes' => array(
        'class' =>  array(
          'authority-id-value',
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
      '#prefix' => '<div id="authority-search-results">',
      '#suffix' => '</div>',
      '#tree'   => TRUE,
      '#weight' => 200,
    );
    
    // button to trigger ajax callback
    $form['do_search'] = array(
      '#type' => 'button',
      '#name' => 'search-authority-source',
      '#value' => $this->t('Search Authority Source'),
      '#ajax'  => array(
        'callback' => array($this, 'getSearchResultsCallback'), // use callback defined w/n this class 
        'event' => 'click',
        'wrapper' => 'authority-search-results',
        'method' => 'replace',
      ),
    );
    
    // add custom classes to this form
    $form['#attributes'] = array(
      'class' =>  array(
        'clearfix',
      ),
    );
    
    // attach module css and js to this form
    $form['#attached'] = array(
      'library' =>  array(
        'authority_search/authority-search-results',
      ),
    );
   
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // not validating anything in this form yet
  }
  
  /**
   * main ajax callback - gets authority search results
   */
  public function getSearchResultsCallback(array &$form, FormStateInterface $form_state) {
    
    // get name to search for
    $full_name = $form_state->getValue('full_name');

    // get Authority Source plugin to use for search
    $authority_source_plugin_id = $form_state->getValue('authority_source');
    
    // get Authority Source plugin manager
    //$manager = \Drupal::service('plugin.manager.authority_search');
    // create instance of the selected Authority Source plugin
    //$authority_source = $manager->createInstance($authority_source_plugin_id);
    $authority_source = get_authority_source_plugin_instance($authority_source_plugin_id);

    // build query and search Authority Source with it
    $query = $authority_source->buildQueryNameFirstLast($full_name);
    $items = $authority_source->search($query);
    $search_results = $authority_source->buildSearchResultsTable($form, $items);

    return $search_results;
  }
  
  /** 
   * default submit handler
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    //\Drupal::logger('authority_search')->info("Thank you - default submit handler 'submitForm' was triggered.");
        
    // prevent form from redirecting
    //$form_state->setRebuild();
  }
  
  /**
   * custom submit handler
   */ 
  public function submitFormAddAuthority(array &$form, FormStateInterface $form_state) {
        
    // get Authority Source plugin to use for search
    $authority_source_plugin_id = $form_state->getValue('authority_source');
    
    // get data for selected authority record;
    // get Authority Source plugin manager and create instance of the selected Authority Source plugin
    //$manager = \Drupal::service('plugin.manager.authority_search');
    //$authority_source = $manager->createInstance($authority_source_plugin_id);
    $authority_source = get_authority_source_plugin_instance($authority_source_plugin_id);
    $authority_data = $authority_source->getAuthorityDataForm($form_state);

    // create a new entity for selected authority record
    $authority_source->createAuthorityEntity($authority_data);

    // prevent form from redirecting
    $form_state->setRebuild();
  }
  
}
