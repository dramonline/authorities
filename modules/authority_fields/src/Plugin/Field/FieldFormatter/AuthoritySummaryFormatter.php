<?php

/**
 * @file
 * Contains Drupal\authority_fields\Plugin\Field\FieldFormatter\AuthoritySummaryFormatter.
 */

namespace Drupal\authority_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'field_authority_summary' formatter.
 *
 * @FieldFormatter(
 *   id = "field_authority_summary",
 *   module = "authority_fields",
 *   label = @Translation("Summary"),
 *   field_types = {
 *     "field_authority"
 *   }
 * )
 */
class AuthoritySummaryFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('The Authority field contains: Name = @name / Authority ID = @source_id / Source = @source / Rules = @rules / URI = @uri / Data (raw) = @data / Data Type = @data_type',
          array(
            '@name'      => $item->name,
            '@source_id' => $item->source_id,
            '@source'    => $item->source,
            '@rules'     => $item->rules,
            '@uri'       => $item->uri,
            '@data'      => $item->data,
            '@data_type' => $item->data_type,
          )
        ),
      );
    }

    return $elements;
  }

}
