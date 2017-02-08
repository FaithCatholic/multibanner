<?php

namespace Drupal\multibanner\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\inline_entity_form\Form\EntityInlineForm;

/**
 * Multibanner inline form handler.
 */
class MultibannerInlineForm extends EntityInlineForm {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getTableFields($bundles) {
    $fields = parent::getTableFields($bundles);

    unset($fields['name']);

    $fields['thumbnail'] = [
      'type' => 'field',
      'label' => $this->t('Thumbnail'),
      'weight' => 1,
      'display_options' => [
        'type' => 'image',
        'settings' => [
          'image_style' => 'thumbnail',
        ],
      ],
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function entityFormSubmit(array &$entity_form, FormStateInterface $form_state) {
    parent::entityFormSubmit($entity_form, $form_state);

    /** @var \Drupal\multibanner\MultibannerInterface $entity */
    $entity = $entity_form['#entity'];

    // Make sure multibanner thumbnail is set correctly.
    $entity->automaticallySetThumbnail();

    if ($entity_form['#save_entity']) {
      $entity->save();
    }
  }

}
