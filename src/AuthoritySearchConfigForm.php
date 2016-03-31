<?php
/**
 * @file
 * Contains \Drupal\authority_search\AuthoritySearchConfigForm.
 */
namespace Drupal\authority_search;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for this module.
 */
class AuthoritySearchConfigForm extends ConfigFormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'authority_search_config_form';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'authority_search.lcnaf-config',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    // @todo
    // should iterate through all available Authority Source plugins and add
    // form elements for each. do the same in submitForm() below. should use
    // plugin method for getting configuration object for each plugin.
    // 
    // use this for global module settings, not plugin-specific settings
    //$config = $this->config('authority_search.lcnaf-config');
    
    // @todo
    // add ajax callback to subtype select - should update based on entity type selected.
    // hide it initially, if no entity type selected.
    
    // @todo
    // map form element label text from plugin's schema.yml
    
    // LCNAF Plugin
    
    $config_lcnaf = \Drupal::config('authority_search.lcnaf-config'); // use immutable form, because we're not writing to config
    
    $form['authsearch_lcnaf_target_entity_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('LCNAF - Target Entity Type'),
      '#options' => list_content_entity_types(),
      '#default_value' => $config_lcnaf->get('target_entity_type'),
    );

    $form['authsearch_lcnaf_target_entity_subtype'] = array(
      '#type' => 'select',
      '#title' => $this->t('LCNAF - Target Entity Bundle/Subtype'),
      '#options' => list_subtypes_for_entity_type(),
      '#default_value' => $config_lcnaf->get('target_entity_subtype'),
    );

    // VIAF Plugin
    
    $config_viaf = \Drupal::config('authority_search.viaf-config');

    $form['authsearch_viaf_target_entity_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('VIAF - Target Entity Type'),
      '#options' => list_content_entity_types(),
      '#default_value' => $config_viaf->get('target_entity_type'),
    );

    $form['authsearch_viaf_target_entity_subtype'] = array(
      '#type' => 'select',
      '#title' => $this->t('VIAF - Target Entity Bundle/Subtype'),
      '#options' => list_subtypes_for_entity_type(),
      '#default_value' => $config_viaf->get('target_entity_subtype'),
    );

    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // LCNAF Plugin
    $config_lcnaf = \Drupal::service('config.factory')->getEditable('authority_search.lcnaf-config'); // use mutable form, because we're writing to config
    $config_lcnaf
      ->set('target_entity_type', $form_state->getValue('authsearch_lcnaf_target_entity_type'))
      ->set('target_entity_subtype', $form_state->getValue('authsearch_lcnaf_target_entity_subtype'))
      ->save();
    
    // VIAF Plugin
    $config_viaf = \Drupal::service('config.factory')->getEditable('authority_search.viaf-config');
    $config_viaf
      ->set('target_entity_type', $form_state->getValue('authsearch_viaf_target_entity_type'))
      ->set('target_entity_subtype', $form_state->getValue('authsearch_viaf_target_entity_subtype'))
      ->save();
   
    parent::submitForm($form, $form_state);
  }

}
