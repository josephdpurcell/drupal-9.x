<?php

/**
 * @file
 * Definition of Drupal\entity_test\EntityTestStorageController.
 */

namespace Drupal\entity_test;

use PDO;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\DatabaseStorageControllerNG;

/**
 * Defines the controller class for the test entity.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for test entities.
 */
class EntityTestStorageController extends DatabaseStorageControllerNG {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::buildPropertyQuery().
   */
  protected function buildPropertyQuery(QueryInterface $entity_query, array $values) {
    // @todo We should not be using a condition to specify whether conditions
    // apply to the default language or not. We need to move this to a
    // separate parameter during the following API refactoring.
    // Default to the original entity language if not explicitly specified
    // otherwise.
    if (!array_key_exists('default_langcode', $values)) {
      $values['default_langcode'] = 1;
    }
    // If the 'default_langcode' flag is esplicitly not set, we do not care
    // whether the queried values are in the original entity language or not.
    elseif ($values['default_langcode'] === NULL) {
      unset($values['default_langcode']);
    }

    parent::buildPropertyQuery($entity_query, $values);
  }

  /**
   * Maps from storage records to entity objects.
   *
   * @return array
   *   An array of entity objects implementing the EntityInterface.
   */
  protected function mapFromStorageRecords(array $records, $load_revision = FALSE) {
    $records = parent::mapFromStorageRecords($records, $load_revision);

    // Load data of translatable properties.
    $this->attachPropertyData($records, $load_revision);
    return $records;
  }

  /**
   * Attaches property data in all languages for translatable properties.
   */
  protected function attachPropertyData(&$queried_entities, $load_revision = FALSE) {
    $query = db_select('entity_test_property_data', 'data', array('fetch' => PDO::FETCH_ASSOC))
      ->fields('data')
      ->condition('id', array_keys($queried_entities))
      ->orderBy('data.id');
    if ($load_revision) {
      // Get revision id's.
      $revision_ids = array();
      foreach ($queried_entities as $id => $entity) {
        $revision_ids[] = $entity->get('revision_id')->value;
      }
      $query->condition('revision_id', $revision_ids);
    }
    $data = $query->execute();

    foreach ($data as $values) {
      $id = $values['id'];
      // Field values in default language are stored with
      // LANGUAGE_DEFAULT as key.
      $langcode = empty($values['default_langcode']) ? $values['langcode'] : LANGUAGE_DEFAULT;

      $queried_entities[$id]->name[$langcode][0]['value'] = $values['name'];
      $queried_entities[$id]->user_id[$langcode][0]['value'] = $values['user_id'];
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::postSave().
   *
   * Stores values of translatable properties.
   */
  protected function postSave(EntityInterface $entity, $update) {
    $default_langcode = $entity->language()->langcode;

    // Delete and insert to handle removed values.
    db_delete('entity_test_property_data')
      ->condition('id', $entity->id())
      ->execute();

    $query = db_insert('entity_test_property_data');

    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      $translation = $entity->getTranslation($langcode);

      $values = array(
        'id' => $entity->id(),
        'revision_id' => $entity->getRevisionId(),
        'langcode' => $langcode,
        'default_langcode' => intval($default_langcode == $langcode),
        'name' => $translation->name->value,
        'user_id' => $translation->user_id->value,
      );

      $query
        ->fields(array_keys($values))
        ->values($values);
    }

    $query->execute();
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::postDelete().
   */
  protected function postDelete($entities) {
    db_delete('entity_test_property_data')
      ->condition('id', array_keys($entities))
      ->execute();
  }

  /**
   * Implements \Drupal\Core\Entity\DataBaseStorageControllerNG::baseFieldDefinitions().
   */
  public function baseFieldDefinitions() {
    $fields['id'] = array(
      'label' => t('ID'),
      'description' => t('The ID of the test entity.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $fields['revision_id'] = array(
      'label' => t('ID'),
      'description' => t('The version id of the test entity.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $fields['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The UUID of the test entity.'),
      'type' => 'string_field',
    );
    $fields['langcode'] = array(
      'label' => t('Language code'),
      'description' => t('The language code of the test entity.'),
      'type' => 'language_field',
    );
    $fields['default_langcode'] = array(
      'label' => t('Default language'),
      'description' => t('Flag to inditcate whether this is the default language.'),
      'type' => 'boolean_field',
    );
    $fields['name'] = array(
      'label' => t('Name'),
      'description' => t('The name of the test entity.'),
      'type' => 'string_field',
      'translatable' => TRUE,
    );
    $fields['user_id'] = array(
      'label' => t('User ID'),
      'description' => t('The ID of the associated user.'),
      'type' => 'entityreference_field',
      'settings' => array('entity type' => 'user'),
      'translatable' => TRUE,
    );
    return $fields;
  }
}
