<?php

namespace Drupal\multibanner;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityForm;

/**
 * Form controller for the multibanner edit forms.
 */
class MultibannerForm extends ContentEntityForm {

  /**
   * Default settings for this multibanner bundle.
   *
   * @var array
   */
  protected $settings;

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\multibanner\Entity\Multibanner
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    parent::prepareEntity();
    $multibanner = $this->entity;

    // If this is a new multibanner, fill in the default values.
    if ($multibanner->isNew()) {
      $multibanner->setPublisherId($this->currentUser()->id());
      $multibanner->setCreatedTime(\Drupal::time()->getRequestTime());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Multibanner author information for administrators.
    if (isset($form['uid']) || isset($form['created'])) {
      $form['author'] = [
        '#type' => 'details',
        '#title' => $this->t('Authoring information'),
        '#group' => 'advanced',
        '#attributes' => [
          'class' => ['node-form-author'],
        ],
        '#attached' => [
          'library' => ['node/drupal.node'],
        ],
        '#weight' => 90,
        '#optional' => TRUE,
      ];
    }

    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'author';
    }

    if (isset($form['created'])) {
      $form['created']['#group'] = 'author';
    }

    $form['#attached']['library'][] = 'node/form';

    $form['#entity_builders']['update_status'] = [$this, 'updateStatus'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $multibanner = $this->entity;

    // Add a "Publish" button.
    $element['publish'] = $element['submit'];
    // If the "Publish" button is clicked, we want to update the status to
    // "published".
    $element['publish']['#published_status'] = TRUE;
    $element['publish']['#dropbutton'] = 'save';
    if ($multibanner->isNew()) {
      $element['publish']['#value'] = $this->t('Save and publish');
    }
    else {
      $element['publish']['#value'] = $multibanner->isPublished() ? $this->t('Save and keep published') : $this->t('Save and publish');
    }
    $element['publish']['#weight'] = 0;

    // Add a "Unpublish" button.
    $element['unpublish'] = $element['submit'];
    // If the "Unpublish" button is clicked, we want to update the status to
    // "unpublished".
    $element['unpublish']['#published_status'] = FALSE;
    $element['unpublish']['#dropbutton'] = 'save';
    if ($multibanner->isNew()) {
      $element['unpublish']['#value'] = $this->t('Save as unpublished');
    }
    else {
      $element['unpublish']['#value'] = !$multibanner->isPublished() ? $this->t('Save and keep unpublished') : $this->t('Save and unpublish');
    }
    $element['unpublish']['#weight'] = 10;

    // If already published, the 'publish' button is primary.
    if ($multibanner->isPublished()) {
      unset($element['unpublish']['#button_type']);
    }
    // Otherwise, the 'unpublish' button is primary and should come first.
    else {
      unset($element['publish']['#button_type']);
      $element['unpublish']['#weight'] = -10;
    }

    // Remove the "Save" button.
    $element['submit']['#access'] = FALSE;

    $element['delete']['#access'] = $multibanner->access('delete');
    $element['delete']['#weight'] = 100;

    return $element;
  }

  /**
   * Entity builder updating the multibanner status with the submitted value.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\multibanner\MultibannerInterface $multibanner
   *   The multibanner updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\multibanner\MultibannerForm::form()
   */
  public function updateStatus($entity_type_id, MultibannerInterface $multibanner, array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if (isset($element['#published_status'])) {
      $multibanner->setPublished($element['#published_status']);
    }
  }

}
