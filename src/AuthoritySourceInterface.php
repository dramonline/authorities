<?php
/**
 * @file
 * Provides Drupal\authority_search\AuthoritySourceInterface.
 */

namespace Drupal\authority_search;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Defines an interface for Authority Source plugins.
 */
interface AuthoritySourceInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Return a query for searching the Authority Source by a person's first and last name
   *
   * @return array
   */
  public function buildQueryNameFirstLast($search_text = '', $search_options = array());

  /**
   * Return a query for searching the Authority Source by authority id (LCCN, etc.)
   *
   * @return array
   */
  public function buildQueryAuthorityId($authority_id);


  /**
   * Return a query string for searching the Authority Source
   *
   * @return string
   */
  public function buildQueryStringNameFirstLast($name);

  /**
   * Get authority data for a particular record, specified by an authority id;
   * this data can be used to populate fields on a new or existing entity.
   *
   * @return array
   */
  public function buildQueryStringAuthorityId($authority_id);

  /**
   * Configure a query, using the query provided
   *
   * @return array
   */
  public function configQuery($query);

  /**
   * Return search results from the Authority Source search
   *
   * @return array
   */
  public function search($query);

  /**
   * Get authority data for a particular search result item - this data can be used to populate fields
   * for a new or existing entity.
   *
   * @return array
   */
  public function getAuthorityData($authority_id);

  /**
   * wrapper for getAuthorityData() - for use with authority search forms
   *
   * @return array
   */
  public function getAuthorityDataForm($form_state);

  /**
   * Get authority data for a particular search result item - this data can be used to populate fields
   * for a new or existing entity.
   *
   * @return array
   */
  public function getAuthorityDataByAuthorityId($authority_id);

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

  /**
   * Prepare authority id for query by removing whitespace and other extraneous characters
   *
   * @return string
   */
  public function makeQueryReadyAuthorityId($authority_id);
  
  /**
   * {@inheritdoc}
   */
  public function getConfiguration();

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration);

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration();

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies();

}
