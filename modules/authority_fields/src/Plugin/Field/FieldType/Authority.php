<?php

/**
 * @file
 * Contains Drupal\authority_fields\Plugin\Field\FieldType\Authority.
 */

namespace Drupal\authority_fields\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_authority' field type.
 *
 * @FieldType(
 *   id = "field_authority",
 *   label = @Translation("Authority"),
 *   module = "authority_fields",
 *   description = @Translation("A field for storing remote authority data."),
 *   default_widget = "field_authority",
 *   default_formatter = "field_authority"
 * )
 */
class Authority extends FieldItemBase {

  // @todo
  // review sizes and types used in schema definition below.
  // https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Database!database.api.php/group/schemaapi/8
  
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'name' => array(
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
        'source_id' => array(
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
        'source' => array(
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
        'rules' => array(
          'type' => 'text',
          'size' => 'normal',
          'not null' => FALSE,
        ),
        'uri' => array(
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
        'data' => array(
          'type' => 'blob', // serialized data needs to be stored as blob, not text
          'size' => 'normal',
          'not null' => FALSE,
        ),
        'data_type' => array(
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    
    $name      = $this->get('name')->getValue();
    $source_id = $this->get('source_id')->getValue();
    $source    = $this->get('source')->getValue();
    $rules     = $this->get('rules')->getValue();
    $uri       = $this->get('uri')->getValue();
    $data      = $this->get('data')->getValue();
    $data_type = $this->get('data_type')->getValue();

    // field is empty if name subfield is empty AND both source_id and source subfields are empty. 
    return ($name === NULL || $name === '') && ($source_id === NULL || $source_id === '') && ($source === NULL || $source === '');
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['name'] = DataDefinition::create('string')
      ->setLabel(t('Authority Name'));
    $properties['source_id'] = DataDefinition::create('string')
      ->setLabel(t('Authority Source ID'));
    $properties['source'] = DataDefinition::create('string')
      ->setLabel(t('Authority Source'));
    $properties['rules'] = DataDefinition::create('string')
      ->setLabel(t('Authority Rules'));
    $properties['uri'] = DataDefinition::create('uri')
      ->setLabel(t('Authority URI'));
    $properties['data'] = DataDefinition::create('string')
      ->setLabel(t('Authority Data (raw)'));
    $properties['data_type'] = DataDefinition::create('string')
      ->setLabel(t('Authority Data Type'));
    
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  /*
  public static function mainPropertyName() {
    return 'name';
  }
  */
 
  /**
   * generate random field values - for use with tests and devel_generate
   * 
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {

    $random = new Random();
    $source_id_unique = TRUE;

    $values['name'] = self::generateRandomAuthorityName($field_definition);
    $values['source_id'] = $random->string(mt_rand(8, 12), $source_id_unique);
    $values['source'] = strtoupper($random->word(mt_rand(2, 4)));
    $values['rules'] = strtoupper($random->word(mt_rand(3, 8)));
    $values['uri'] = self::generateRandomAuthorityUri($field_definition);
    $values['data'] = $random->string(mt_rand(40, 1500));
    $values['data_type'] = strtoupper($random->word(mt_rand(3, 4)));

    return $values;
  }

  /*
   * generate random authority name - used by generateSampleValue()
   */
  public function generateRandomAuthorityName(FieldDefinitionInterface $field_definition) {
    $random = new Random();

    $name_first = ucfirst($random->word(mt_rand(1, 20)));
    $name_last = ucfirst($random->word(mt_rand(1, 20)));
    $name_middle = ucfirst($random->word(mt_rand(0, 15)));

    // add period if using middle initial
    if (strlen($name_middle) == 1) {
      $name_middle .= '.';
    }

    $name = $name_middle . ', ' . $name_first;
    $name .= (!empty($name_middle)) ? ' ' . $name_middle : '';

    return $name;
  }

  /*
   * generate random authority uri - used by generateSampleValue()
   */
  public function generateRandomAuthorityUri(FieldDefinitionInterface $field_definition) {
    $random = new Random();

    // adapted from core/lib/Drupal/Core/Field/Plugin/Field/FieldType/UriItem.php
    $uri = 'http://' . $random->word(mt_rand(2, 60));

    return $uri;
  }

}
