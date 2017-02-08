<?php

namespace Drupal\multibanner;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the multibanner type.
 */
class MultibannerViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['multibanner_field_data']['table']['wizard_id'] = 'multibanner';
    $data['multibanner_field_revision']['table']['wizard_id'] = 'multibanner_revision';
    $data['multibanner']['multibanner_bulk_form'] = [
      'title' => $this->t('Multibanner operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple multibanner entities.'),
      'field' => [
        'id' => 'multibanner_bulk_form',
      ],
    ];

    return $data;
  }

}
