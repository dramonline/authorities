<?php
/**
 * @file
 * Contains \Drupal\authority_search\Annotation\AuthoritySource.
 */

namespace Drupal\authority_search\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an AuthoritySource item annotation object.
 *
 * Plugin Namespace: Plugin\authority_search\authority_source
 *
 * @see \Drupal\authority_search\Plugin\AuthoritySearchManager
 * @see plugin_api
 *
 * @Annotation
 */
class AuthoritySource extends Plugin {
  
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;
  
  /**
   * The short name of the Authority Source plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;
  
  /**
   * Describes the Authority Source plugin with longer text than $name above.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The text to search the Authority Source for.
   *
   * @var string
   */
  public $search_text;
  
  /**
   * The name to search the Authority Source for.
   *
   * @var array
   */
  public $search_options;

}
