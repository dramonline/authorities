<?php
/**
 * @file
 * Contains \Drupal\lcnaf\Form\SimpleRequestForm.
 */
namespace Drupal\lcnaf\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for this module.
 */
class SimpleRequestForm extends FormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lcnaf_simple_request';
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    // First Name textfield
    $form['lcnaf_simple_request_first_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#default_value' => 'jane',
    );
    
    // Last Name textfield
    $form['lcnaf_simple_request_last_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#default_value' => 'austen',
    );
    
    // Submit button
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search LCNAF'),
      '#button_type' => 'primary',
    );
    
    $form['results'] = array(
      '#markup' => t(''),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // not validating anything in the form
    // should be able to modify $form_state here, if necessary
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    // get form values
    $first_name = $form_state->getValue('lcnaf_simple_request_first_name');
    $last_name = $form_state->getValue('lcnaf_simple_request_last_name');
    
    // simple redirect format
    //$form_state->setRedirect('route-name-goes-here');
    
    // prevents form from redirecting below
    //$form_state->setRebuild();
    
    // redirect to test page, with URL query populated with first_name and last_name to search LCNAF for
    $route_parameters = array();
    $form_state->setRedirect('lcnaf.custom_query_example', $route_parameters, array(
      'query' => array(
        'first_name' => $first_name,
        'last_name' => $last_name,
      ),
    ));
  }
}
