<?php

/**
 * @file
 * Contains Drupal\authority_fields\Plugin\Field\FieldFormatter\AuthoritySourceIdFormatter.
 */

namespace Drupal\authority_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'field_authority_source_id' formatter.
 *
 * @FieldFormatter(
 *   id = "field_authority_source_id",
 *   module = "authority_fields",
 *   label = @Translation("Source ID"),
 *   field_types = {
 *     "field_authority"
 *   }
 * )
 */
class AuthoritySourceIdFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      if (isset($item->source_id) && !empty($item->source_id)) {
        $elements[$delta] = array(
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $item->source_id,
        );
      }
    }

    return $elements;
  }

}
