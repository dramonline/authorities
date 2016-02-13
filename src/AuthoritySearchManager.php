<?php
/**
 * @file
 * Contains AuthoritySearchManager.
 */

namespace Drupal\authority_search;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * AuthoritySearch plugin manager.
 */
class AuthoritySearchManager extends DefaultPluginManager {

  /**
   * Constructs an AuthoritySearchManager object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/AuthoritySource', $namespaces, $module_handler, 'Drupal\authority_search\AuthoritySourceInterface', 'Drupal\authority_search\Annotation\AuthoritySource');

    $this->alterInfo('authority_search_sources_info');
    $this->setCacheBackend($cache_backend, 'authority_search_sources');
  }
  
}
