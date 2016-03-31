<?php

/**
 * @file
 * Contains Drupal\authority_fields\Plugin\Field\FieldFormatter\AuthorityUriFormatter.
 */

namespace Drupal\authority_fields\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'field_authority_uri' formatter.
 *
 * @FieldFormatter(
 *   id = "field_authority_uri",
 *   module = "authority_fields",
 *   label = @Translation("URI"),
 *   field_types = {
 *     "field_authority"
 *   }
 * )
 */
class AuthorityUriFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      if (isset($item->uri) && !empty($item->uri) && UrlHelper::isValid($item->uri)) {
        // render link for given uri
        $link = Link::fromTextAndUrl($item->uri, Url::fromUri($item->uri));
        $link_rendered = $link->toString();

        $elements[$delta] = array(
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $link_rendered,
        );
      }
    }

    return $elements;
  }

}
