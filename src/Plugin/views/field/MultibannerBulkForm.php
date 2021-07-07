<?php

namespace Drupal\multibanner\Plugin\views\field;

use Drupal\views\Plugin\views\field\BulkForm;

/**
 * Defines a multibanner operations bulk form element.
 *
 * @ViewsField("multibanner_bulk_form")
 */
class MultibannerBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No multibanner selected.');
  }

}
