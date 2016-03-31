<?php

/**
 * @file
 * Contains \Drupal\authority_fields\Plugin\field\widget\AuthorityWidget.
 */

namespace Drupal\authority_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_authority' widget.
 *
 * @FieldWidget(
 *   id = "field_authority",
 *   module = "authority_fields",
 *   label = @Translation("Authority Data"),
 *   field_types = {
 *     "field_authority"
 *   }
 * )
 */
class AuthorityWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $authority_name      = isset($items[$delta]->name) ? $items[$delta]->name : '';
    $source_id           = isset($items[$delta]->source_id) ? $items[$delta]->source_id : '';
    $source              = isset($items[$delta]->source) ? $items[$delta]->source : '';
    $authority_rules     = isset($items[$delta]->rules) ? $items[$delta]->rules : '';
    $authority_uri       = isset($items[$delta]->uri) ? $items[$delta]->uri : '';
    $authority_data      = isset($items[$delta]->data) ? $items[$delta]->data : '';
    $authority_data_type = isset($items[$delta]->data_type) ? $items[$delta]->data_type : '';
    
    $element['name'] = array(
      '#title' => $this->t('Authority Name'),
      '#type' => 'textfield',
      '#default_value' => $authority_name,
      '#size' => 30,
      '#maxlength' => 60,
    );

    $element['source_id'] = array(
      '#title' => $this->t('Authority Source ID'),
      '#type' => 'textfield',
      '#default_value' => $source_id,
      '#size' => 30,
      '#maxlength' => 60,
    );

    $element['source'] = array(
      '#title' => $this->t('Authority Source'),
      '#type' => 'textfield',
      '#default_value' => $source,
      '#size' => 20,
      '#maxlength' => 60,
    );

    $element['rules'] = array(
      '#title' => $this->t('Authority Rules'),
      '#type' => 'textfield',
      '#default_value' => $authority_rules,
      '#size' => 12,
      '#maxlength' => 60,
    );

    $element['uri'] = array(
      '#title' => $this->t('Authority uri'),
      '#type' => 'url',
      //'#type' => 'textfield',
      '#default_value' => $authority_uri,
      '#size' => 60,
      '#maxlength' => 255,
    );

    $element['data'] = array(
      '#title' => $this->t('Authority Data (raw)'),
      '#type' => 'textarea',
      '#default_value' => $authority_data,
      '#rows' => 10,
    );

    $element['data_type'] = array(
      '#title' => $this->t('Authority Data Type'),
      '#type' => 'textfield',
      '#default_value' => $authority_data_type,
      '#size' => 10,
      '#maxlength' => 60,
    );

    return $element;
  }

  /**
   * Validate the field.
   */
  public function validate($element, FormStateInterface $form_state) {
    
    // bypassing validation for now...
    return;
  }

}
