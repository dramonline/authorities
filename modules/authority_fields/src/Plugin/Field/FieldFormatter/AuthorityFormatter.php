<?php

/**
 * @file
 * Contains Drupal\authority_fields\Plugin\Field\FieldFormatter\AuthorityFormatter.
 */

namespace Drupal\authority_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'field_authority' formatter.
 *
 * @FieldFormatter(
 *   id = "field_authority",
 *   module = "authority_fields",
 *   label = @Translation("Default formatter"),
 *   field_types = {
 *     "field_authority"
 *   }
 * )
 */
class AuthorityFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        // Create a render array to produce the desired markup,
        // "<p>The text pair field contains ... valueA = xxxx / valueB = xxxx</p>".
        // See theme_html_tag().
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $item->name,
      );
    }

    return $elements;
  }

}
