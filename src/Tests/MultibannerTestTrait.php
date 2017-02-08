<?php

namespace Drupal\multibanner\Tests;

use Drupal\multibanner\Entity\MultibannerBundle;

/**
 * Provides common functionality for multibanner test classes.
 */
trait MultibannerTestTrait {

  /**
   * Creates multibanner bundle.
   *
   * @param array $values
   *   The multibanner bundle values.
   * @param string $type_name
   *   (optional) The multibanner type provider plugin that is responsible for
   *   additional logic related to this multibanner).
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Returns newly created multibanner bundle.
   */
  protected function drupalCreateMultibannerBundle(array $values = [], $type_name = 'generic') {
    if (!isset($values['bundle'])) {
      $id = strtolower($this->randomMachineName());
    }
    else {
      $id = $values['bundle'];
    }
    $values += [
      'id' => $id,
      'label' => $id,
      'type' => $type_name,
      'type_configuration' => [],
      'field_map' => [],
      'new_revision' => FALSE,
    ];

    $bundle = MultibannerBundle::create($values);
    $status = $bundle->save();

    $this->assertEqual($status, SAVED_NEW, t('Created multibanner bundle %bundle.', ['%bundle' => $bundle->id()]));

    return $bundle;
  }

}
