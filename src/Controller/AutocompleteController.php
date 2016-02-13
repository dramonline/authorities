<?php

/**
 * @file
 * Contains \Drupal\authority_search\Controller\AutocompleteController.
 */

namespace Drupal\authority_search\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Unicode;

/**
 * Returns autocomplete responses for authority names.
 */
class AutocompleteController {

  public function autocomplete(Request $request) {
    $matches = array();
    $string = $request->query->get('q');
    if ($string) {
      
      // get matching authority names from LCNAF query
      $authority_names = $this->_getMatchingAuthorityNames($string);
      
      // collect matching authority name results and format these for json autocomplete response;
      // make sure search string occurs with that name before ading name to list of matches.
      foreach ($authority_names as $authority_name) {
        if (strpos(Unicode::strtolower($authority_name), Unicode::strtolower($string)) !== FALSE) {
          $matches[] = array('value' => $authority_name, 'label' => $authority_name);
        }
      }
    }
    return new JsonResponse($matches);
  }
  
  public function _getMatchingAuthorityNames($string) {
          
    //
    // @todo
    // add to code to query LCNAF for matching authority names.
    // (autocomplete working fine with hard-coded values)
    //
    
    // TEMP
    $authority_names = array(
      'Donald Fagen',
      'Walter Becker',
      'Tony Visconti',
    );
    
    return $authority_names;
  }
}
