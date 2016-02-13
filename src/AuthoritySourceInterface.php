<?php
/**
 * @file
 * Provides Drupal\authority_search\AuthoritySourceInterface.
 */

namespace Drupal\authority_search;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Authority Source plugins.
 */
interface AuthoritySourceInterface extends PluginInspectionInterface {
  
  /**
   * Return a query for searching the Authority Source by a person's first and last name
   *
   * @return array
   */
  public function buildQueryNameFirstLast($search_text = '', $search_options = array());

  /**
   * Return a query string for searching the Authority Source
   *
   * @return string
   */
  public function buildQueryStringNameFirstLast($name);

  /**
   * Configure a query, using the query string provided
   *
   * @return string
   */
  public function configQuery($query);

  /**
   * Return search results from the Authority Source search
   *
   * @return array
   */
  public function search($query);

  /**
   * Extract search results from Authority Source response data
   *
   * @return array
   */
  public function extractSearchResults($data);

  /**
   * Build a table of renderable search results
   *
   * @return array
   */
  public function buildSearchResultsTable($form, $items);

  /**
   * Extract data from a search result item for populating an authority entity's fields
   *
   * @return array
   */
  public function getAuthorityData($authority_id);

  /**
   * wraps getAuthorityData() - for use with authority search forms
   *
   * @return array
   */
  public function getAuthorityDataForm($form_state);

  /**
   * Create a new authority entity
   *
   * @return bool
   */
  public function createAuthorityEntity($authority_data);

  /**
   * Add authority data to an existing authority entity
   *
   * @return bool
   */
  public function updateAuthorityEntity($authority_data);
  
}
